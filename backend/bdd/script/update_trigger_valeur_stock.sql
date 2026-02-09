-- =============================================================================
-- MISE A JOUR : Trigger pour recalcul automatique de valeur_stock_total
-- =============================================================================
-- Ce script ajoute un trigger pour recalculer automatiquement valeur_stock_total
-- lors des modifications de quantite_actuelle ou cmup_actuel
-- =============================================================================

-- Créer la fonction de recalcul
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
        
    -- FIFO/LIFO : pas de CMUP ni valeur stockée (calculé par lots à la demande)
    ELSIF v_methode_id IN (2, 3) THEN
        NEW.cmup_actuel := NULL;
        NEW.valeur_stock_total := NULL;
        NEW.date_maj := CURRENT_TIMESTAMP;
        RETURN NEW;
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

-- Recalculer toutes les valeurs existantes pour CMUP
UPDATE stock 
SET valeur_stock_total = quantite_actuelle * cmup_actuel
WHERE methode_valorisation_stock_id = 1;

-- Recalculer toutes les valeurs existantes pour FIFO/LIFO (pas de CMUP ni valeur stockee)
UPDATE stock
SET cmup_actuel = NULL,
    valeur_stock_total = NULL
WHERE methode_valorisation_stock_id IN (2, 3);

-- Vérification
SELECT 
    s.id,
    a.designation as article,
    d.nom as depot,
    m.nom as methode,
    s.quantite_actuelle,
    s.cmup_actuel,
    s.valeur_stock_total,
    CASE 
        WHEN s.methode_valorisation_stock_id = 1 
        THEN s.quantite_actuelle * s.cmup_actuel
        ELSE (
            SELECT COALESCE(SUM(ls.quantite_restante * ls.prix_unitaire_achat), 0)
            FROM lot_stock ls
            WHERE ls.article_id = s.article_id
              AND ls.depot_id = s.depot_id
              AND ls.quantite_restante > 0
        )
    END as valeur_verifiee
FROM stock s
JOIN article a ON s.article_id = a.id
JOIN depot d ON s.depot_id = d.id
JOIN methode_valorisation_stock m ON s.methode_valorisation_stock_id = m.id
ORDER BY s.id;

COMMENT ON FUNCTION recalculer_valeur_stock_total() IS 'Recalcule automatiquement valeur_stock_total lors de modifications de quantite_actuelle ou cmup_actuel';

-- FIN DU SCRIPT
