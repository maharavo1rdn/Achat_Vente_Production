-- =============================================================================
-- SYSTEME DE VALORISATION DE STOCK (CMUP / FIFO / LIFO)
-- =============================================================================
-- Ce script crée les fonctions et le trigger pour gérer automatiquement
-- la valorisation du stock selon la méthode définie par dépôt
-- =============================================================================


-- =============================================================================
-- FONCTION 1 : Gestion CMUP (Coût Moyen Pondéré)
-- =============================================================================
CREATE OR REPLACE FUNCTION calculer_cmup()
RETURNS TRIGGER AS $$
DECLARE
    v_stock RECORD;
    ancien_cmup     NUMERIC(15,2) := 0;
    ancienne_qte    NUMERIC(15,2) := 0;
    ancienne_valeur NUMERIC(15,2) := 0;
    nouvelle_qte    NUMERIC(15,2);
    nouvelle_valeur NUMERIC(15,2);
    nouveau_cmup    NUMERIC(15,2);
BEGIN
    SELECT quantite_actuelle, cmup_actuel, valeur_stock_total
      INTO v_stock
      FROM stock
     WHERE article_id = NEW.article_id
       AND depot_id = NEW.depot_id;

    IF FOUND THEN
        ancien_cmup     := COALESCE(v_stock.cmup_actuel, 0);
        ancienne_qte    := COALESCE(v_stock.quantite_actuelle, 0);
        ancienne_valeur := COALESCE(v_stock.valeur_stock_total, 0);

        IF NEW.quantite_entree > 0 THEN
            nouvelle_qte    := ancienne_qte + NEW.quantite_entree;
            nouvelle_valeur := ancienne_valeur + (NEW.quantite_entree * COALESCE(NEW.prix_unitaire_mouvement, 0));

            IF nouvelle_qte > 0 THEN
                nouveau_cmup := nouvelle_valeur / nouvelle_qte;
            ELSE
                nouveau_cmup := ancien_cmup;
            END IF;

            UPDATE stock
               SET cmup_actuel = nouveau_cmup,
                   valeur_stock_total = nouvelle_valeur,
                   quantite_actuelle = nouvelle_qte,
                   date_maj = NOW()
             WHERE article_id = NEW.article_id
               AND depot_id = NEW.depot_id;

            RAISE NOTICE 'CMUP ENTREE Article % Ancien % Nouveau %', NEW.article_id, ancien_cmup, nouveau_cmup;

        ELSIF NEW.quantite_sortie > 0 THEN
            nouvelle_qte    := ancienne_qte - NEW.quantite_sortie;
            nouvelle_valeur := nouvelle_qte * ancien_cmup;

            UPDATE stock
               SET valeur_stock_total = nouvelle_valeur,
                   quantite_actuelle = nouvelle_qte,
                   date_maj = NOW()
             WHERE article_id = NEW.article_id
               AND depot_id = NEW.depot_id;

            RAISE NOTICE 'CMUP SORTIE Article % CMUP % Valeur %', NEW.article_id, ancien_cmup, nouvelle_valeur;
        END IF;

    ELSE
        IF NEW.quantite_entree > 0 THEN
            INSERT INTO stock (article_id, depot_id, quantite_actuelle, cmup_actuel, valeur_stock_total, methode_valorisation_stock_id)
            VALUES (
                NEW.article_id,
                NEW.depot_id,
                NEW.quantite_entree,
                COALESCE(NEW.prix_unitaire_mouvement, 0),
                NEW.quantite_entree * COALESCE(NEW.prix_unitaire_mouvement, 0),
                1
            );

            RAISE NOTICE 'CMUP CREATION Article % CMUP initial %', NEW.article_id, COALESCE(NEW.prix_unitaire_mouvement, 0);
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- =============================================================================
-- FONCTION 2 : Gestion FIFO
-- =============================================================================
CREATE OR REPLACE FUNCTION gerer_fifo()
RETURNS TRIGGER AS $$
DECLARE
    v_lot           RECORD;
    qte_a_sortir    NUMERIC(15,2);
    qte_prelevee    NUMERIC(15,2);
    valeur_sortie   NUMERIC(15,2) := 0;
BEGIN
    IF NEW.quantite_entree > 0 THEN
        INSERT INTO lot_stock (
            numero_lot,
            article_id,
            depot_id,
            date_entree,
            quantite_initiale,
            quantite_restante,
            prix_unitaire_achat,
            statut
        ) VALUES (
            'LOT-' || NEW.article_id || '-' || TO_CHAR(NOW(), 'YYYYMMDDHH24MISS'),
            NEW.article_id,
            NEW.depot_id,
            NEW.date_mouvement,
            NEW.quantite_entree,
            NEW.quantite_entree,
            COALESCE(NEW.prix_unitaire_mouvement, 0),
            'ACTIF'
        );

        UPDATE stock
           SET quantite_actuelle = COALESCE(quantite_actuelle, 0) + NEW.quantite_entree,
               date_maj = NOW()
         WHERE article_id = NEW.article_id
           AND depot_id = NEW.depot_id;

        IF NOT FOUND THEN
            INSERT INTO stock (article_id, depot_id, quantite_actuelle, methode_valorisation_stock_id)
            VALUES (NEW.article_id, NEW.depot_id, NEW.quantite_entree, 2); -- 2 = FIFO
        END IF;

        RAISE NOTICE 'FIFO ENTREE Article % Qté %', NEW.article_id, NEW.quantite_entree;

    ELSIF NEW.quantite_sortie > 0 THEN
        qte_a_sortir := NEW.quantite_sortie;

        FOR v_lot IN
            SELECT id, quantite_restante, prix_unitaire_achat
              FROM lot_stock
             WHERE article_id = NEW.article_id
               AND depot_id = NEW.depot_id
               AND statut = 'ACTIF'
               AND quantite_restante > 0
          ORDER BY date_entree ASC
        LOOP
            qte_prelevee := LEAST(qte_a_sortir, v_lot.quantite_restante);

            INSERT INTO sortie_lot_detail (
                mouvement_stock_id,
                lot_stock_id,
                quantite_sortie,
                prix_unitaire_lot
            ) VALUES (
                NEW.id,
                v_lot.id,
                qte_prelevee,
                v_lot.prix_unitaire_achat
            );

            UPDATE lot_stock
               SET quantite_restante = quantite_restante - qte_prelevee,
                   statut = CASE
                       WHEN quantite_restante - qte_prelevee <= 0 THEN 'EPUISE'
                       ELSE 'ACTIF'
                   END
             WHERE id = v_lot.id;

            valeur_sortie := valeur_sortie + (qte_prelevee * v_lot.prix_unitaire_achat);
            qte_a_sortir  := qte_a_sortir - qte_prelevee;

            EXIT WHEN qte_a_sortir <= 0;
        END LOOP;

        UPDATE stock
           SET quantite_actuelle  = quantite_actuelle - NEW.quantite_sortie,
               valeur_stock_total = valeur_stock_total - valeur_sortie,
               date_maj           = NOW()
         WHERE article_id = NEW.article_id
           AND depot_id = NEW.depot_id;

        IF qte_a_sortir > 0 THEN
            RAISE WARNING 'FIFO SORTIE - Stock insuffisant, manquant: %', qte_a_sortir;
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


-- =============================================================================
-- FONCTION 3 : Gestion LIFO
-- =============================================================================
CREATE OR REPLACE FUNCTION gerer_lifo()
RETURNS TRIGGER AS $$
DECLARE
    v_lot           RECORD;
    qte_a_sortir    NUMERIC(15,2);
    qte_prelevee    NUMERIC(15,2);
    valeur_sortie   NUMERIC(15,2) := 0;
BEGIN
    -- Même logique d'entrée que FIFO
    IF NEW.quantite_entree > 0 THEN
        INSERT INTO lot_stock (
            numero_lot,
            article_id,
            depot_id,
            date_entree,
            quantite_initiale,
            quantite_restante,
            prix_unitaire_achat,
            statut
        ) VALUES (
            'LOT-' || NEW.article_id || '-' || TO_CHAR(NOW(), 'YYYYMMDDHH24MISS'),
            NEW.article_id,
            NEW.depot_id,
            NEW.date_mouvement,
            NEW.quantite_entree,
            NEW.quantite_entree,
            COALESCE(NEW.prix_unitaire_mouvement, 0),
            'ACTIF'
        );

        UPDATE stock
           SET quantite_actuelle = COALESCE(quantite_actuelle, 0) + NEW.quantite_entree,
               date_maj = NOW()
         WHERE article_id = NEW.article_id
           AND depot_id = NEW.depot_id;

        IF NOT FOUND THEN
            INSERT INTO stock (article_id, depot_id, quantite_actuelle, methode_valorisation_stock_id)
            VALUES (NEW.article_id, NEW.depot_id, NEW.quantite_entree, 3); -- 3 = LIFO
        END IF;

        RAISE NOTICE 'LIFO ENTREE Article % Qté %', NEW.article_id, NEW.quantite_entree;

    ELSIF NEW.quantite_sortie > 0 THEN
        qte_a_sortir := NEW.quantite_sortie;

        FOR v_lot IN
            SELECT id, quantite_restante, prix_unitaire_achat
              FROM lot_stock
             WHERE article_id = NEW.article_id
               AND depot_id = NEW.depot_id
               AND statut = 'ACTIF'
               AND quantite_restante > 0
          ORDER BY date_entree DESC
        LOOP
            qte_prelevee := LEAST(qte_a_sortir, v_lot.quantite_restante);

            INSERT INTO sortie_lot_detail (
                mouvement_stock_id,
                lot_stock_id,
                quantite_sortie,
                prix_unitaire_lot
            ) VALUES (
                NEW.id,
                v_lot.id,
                qte_prelevee,
                v_lot.prix_unitaire_achat
            );

            UPDATE lot_stock
               SET quantite_restante = quantite_restante - qte_prelevee,
                   statut = CASE
                       WHEN quantite_restante - qte_prelevee <= 0 THEN 'EPUISE'
                       ELSE 'ACTIF'
                   END
             WHERE id = v_lot.id;

            valeur_sortie := valeur_sortie + (qte_prelevee * v_lot.prix_unitaire_achat);
            qte_a_sortir  := qte_a_sortir - qte_prelevee;

            EXIT WHEN qte_a_sortir <= 0;
        END LOOP;

        UPDATE stock
           SET quantite_actuelle  = quantite_actuelle - NEW.quantite_sortie,
               valeur_stock_total = valeur_stock_total - valeur_sortie,
               date_maj           = NOW()
         WHERE article_id = NEW.article_id
           AND depot_id = NEW.depot_id;

        IF qte_a_sortir > 0 THEN
            RAISE WARNING 'LIFO SORTIE - Stock insuffisant, manquant: %', qte_a_sortir;
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


-- =============================================================================
-- FONCTION ROUTAGE : Sélectionne la bonne méthode selon le dépôt
-- =============================================================================
CREATE OR REPLACE FUNCTION traiter_valorisation_stock()
RETURNS TRIGGER AS $$
DECLARE
    v_methode_id INTEGER;
BEGIN
    SELECT methode_valorisation_stock_id
      INTO v_methode_id
      FROM depot
     WHERE id = NEW.depot_id;

    IF v_methode_id IS NULL THEN
        v_methode_id := 1;  -- CMUP par défaut
        RAISE NOTICE 'Aucune méthode pour dépôt %, utilisation CMUP par défaut', NEW.depot_id;
    END IF;

    CASE v_methode_id
        WHEN 1 THEN PERFORM calculer_cmup();        RAISE NOTICE 'CMUP traité mouvement %', NEW.id;
        WHEN 2 THEN PERFORM gerer_fifo();           RAISE NOTICE 'FIFO traité mouvement %', NEW.id;
        WHEN 3 THEN PERFORM gerer_lifo();           RAISE NOTICE 'LIFO traité mouvement %', NEW.id;
        ELSE RAISE WARNING 'Méthode inconnue : %', v_methode_id;
    END CASE;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


-- =============================================================================
-- TRIGGER PRINCIPAL
-- =============================================================================
DROP TRIGGER IF EXISTS trigger_valorisation_stock ON mouvement_stock;

CREATE TRIGGER trigger_valorisation_stock
    AFTER INSERT ON mouvement_stock
    FOR EACH ROW
    EXECUTE FUNCTION traiter_valorisation_stock();


-- =============================================================================
-- DOCUMENTATION
-- =============================================================================
COMMENT ON FUNCTION calculer_cmup() IS 'Calcule et maintient le CMUP (coût moyen pondéré) pour entrées et sorties';
COMMENT ON FUNCTION gerer_fifo()    IS 'Gère la valorisation FIFO (premier entré, premier sorti) via lots';
COMMENT ON FUNCTION gerer_lifo()    IS 'Gère la valorisation LIFO (dernier entré, premier sorti) via lots';
COMMENT ON FUNCTION traiter_valorisation_stock() IS 'Route le traitement vers la méthode appropriée selon le dépôt';


-- FIN DU SCRIPT
-- Exécutez ce bloc dans un client UTF-8 (pgAdmin, DBeaver, psql avec client_encoding=UTF8)