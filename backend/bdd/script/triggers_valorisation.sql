-- =============================================================================
-- SYSTEME DE VALORISATION DE STOCK (CMUP / FIFO / LIFO)
-- =============================================================================
-- Ce script cree le trigger pour gerer automatiquement
-- la valorisation du stock selon la methode definie par depot
-- =============================================================================


-- =============================================================================
-- FONCTION PRINCIPALE : Gestion de la valorisation
-- =============================================================================
CREATE OR REPLACE FUNCTION traiter_valorisation_stock()
RETURNS TRIGGER AS $$
DECLARE
    v_methode_id    INTEGER;
    v_stock         RECORD;
    v_lot           RECORD;
    qte_a_sortir    NUMERIC(15,2);
    qte_prelevee    NUMERIC(15,2);
    valeur_sortie   NUMERIC(15,2) := 0;
    ancien_cmup     NUMERIC(15,2) := 0;
    ancienne_qte    NUMERIC(15,2) := 0;
    ancienne_valeur NUMERIC(15,2) := 0;
    nouvelle_qte    NUMERIC(15,2);
    nouvelle_valeur NUMERIC(15,2);
    nouveau_cmup    NUMERIC(15,2);
BEGIN
    -- Priorite 1: chercher la methode dans stock (par article + depot)
    SELECT methode_valorisation_stock_id
      INTO v_methode_id
      FROM stock
     WHERE article_id = NEW.article_id
       AND depot_id = NEW.depot_id;

    -- Priorite 2: si non trouve, utiliser la methode du depot
    IF v_methode_id IS NULL THEN
        SELECT methode_valorisation_stock_id
          INTO v_methode_id
          FROM depot
         WHERE id = NEW.depot_id;
    END IF;

    -- Priorite 3: si toujours null, utiliser CMUP par defaut
    IF v_methode_id IS NULL THEN
        v_methode_id := 1;  -- CMUP par defaut
        RAISE NOTICE 'Aucune methode pour depot %, utilisation CMUP par defaut', NEW.depot_id;
    END IF;

    -- ==========================================================================
    -- CMUP (Cout Moyen Pondere)
    -- ==========================================================================
    IF v_methode_id = 1 THEN
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

    -- ==========================================================================
    -- FIFO (Premier Entre, Premier Sorti)
    -- ==========================================================================
    ELSIF v_methode_id = 2 THEN
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
                'LOT-' || nextval('lot_stock_id_seq') || '-A' || NEW.article_id || '-D' || NEW.depot_id,
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
                VALUES (NEW.article_id, NEW.depot_id, NEW.quantite_entree, 2);
            END IF;

RAISE NOTICE 'FIFO ENTREE Article % Qte %', NEW.article_id, NEW.quantite_entree;

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
                    mouvement_sortie_id,
                    lot_stock_id,
                    quantite_prelevee,
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

    -- ==========================================================================
    -- LIFO (Dernier Entre, Premier Sorti)
    -- ==========================================================================
    ELSIF v_methode_id = 3 THEN
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
                'LOT-' || nextval('lot_stock_id_seq') || '-A' || NEW.article_id || '-D' || NEW.depot_id,
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
                VALUES (NEW.article_id, NEW.depot_id, NEW.quantite_entree, 3);
            END IF;

RAISE NOTICE 'LIFO ENTREE Article % Qte %', NEW.article_id, NEW.quantite_entree;

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
                    mouvement_sortie_id,
                    lot_stock_id,
                    quantite_prelevee,
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

    ELSE
        RAISE WARNING 'Methode de valorisation inconnue : %', v_methode_id;
    END IF;

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
-- TRIGGER : Recalcul automatique de valeur_stock_total
-- =============================================================================
CREATE OR REPLACE FUNCTION recalculer_valeur_stock_total()
RETURNS TRIGGER AS $$
DECLARE
    v_methode_id INTEGER;
    v_nouvelle_valeur NUMERIC(15,2);
BEGIN
    -- Récupérer la méthode de valorisation
    v_methode_id := NEW.methode_valorisation_stock_id;
    
    -- CMUP : valeur_stock_total = quantite_actuelle × cmup_actuel
    IF v_methode_id = 1 THEN
        v_nouvelle_valeur := NEW.quantite_actuelle * NEW.cmup_actuel;
        
    -- FIFO/LIFO : valeur_stock_total = SUM des lots
    ELSIF v_methode_id IN (2, 3) THEN
        SELECT COALESCE(SUM(quantite_restante * prix_unitaire_achat), 0)
        INTO v_nouvelle_valeur
        FROM lot_stock
        WHERE article_id = NEW.article_id
          AND depot_id = NEW.depot_id
          AND quantite_restante > 0;
    ELSE
        -- Par défaut, garder la valeur existante
        v_nouvelle_valeur := NEW.valeur_stock_total;
    END IF;
    
    -- Mettre à jour la valeur calculée
    NEW.valeur_stock_total := v_nouvelle_valeur;
    NEW.date_maj := CURRENT_TIMESTAMP;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Créer le trigger BEFORE UPDATE sur la table stock
DROP TRIGGER IF EXISTS trigger_recalcul_valeur_stock ON stock;
CREATE TRIGGER trigger_recalcul_valeur_stock
    BEFORE UPDATE OF quantite_actuelle, cmup_actuel
    ON stock
    FOR EACH ROW
    WHEN (OLD.quantite_actuelle IS DISTINCT FROM NEW.quantite_actuelle 
       OR OLD.cmup_actuel IS DISTINCT FROM NEW.cmup_actuel)
    EXECUTE FUNCTION recalculer_valeur_stock_total();


-- =============================================================================
-- DOCUMENTATION
-- =============================================================================
COMMENT ON FUNCTION traiter_valorisation_stock() IS 'Gere automatiquement la valorisation du stock selon la methode (CMUP/FIFO/LIFO) definie par depot ou article';
COMMENT ON FUNCTION recalculer_valeur_stock_total() IS 'Recalcule automatiquement valeur_stock_total lors de modifications de quantite_actuelle ou cmup_actuel';


-- FIN DU SCRIPT
