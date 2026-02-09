-- ============================================================================
-- Correction du CMUP pour les stocks FIFO/LIFO
-- ============================================================================
-- Les dépôts utilisant FIFO ou LIFO ne doivent PAS avoir de CMUP calculé.
-- Ce script met à jour tous les stocks FIFO/LIFO existants avec cmup_actuel = NULL
-- ============================================================================

-- 1. Afficher les stocks FIFO/LIFO qui ont un CMUP (AVANT correction)
SELECT 
    s.id,
    a.reference,
    a.designation,
    d.nom as depot_nom,
    mvs.code as methode_valorisation,
    s.quantite_actuelle,
    s.cmup_actuel as cmup_avant,
    s.valeur_stock_total as valeur_avant
FROM stock s
INNER JOIN article a ON s.article_id = a.id
INNER JOIN depot d ON s.depot_id = d.id
INNER JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
WHERE mvs.code IN ('FIFO', 'LIFO')
  AND s.cmup_actuel IS NOT NULL
ORDER BY d.nom, a.reference;

-- 2. Mettre à jour les stocks FIFO/LIFO : cmup_actuel = NULL
UPDATE stock
SET 
    cmup_actuel = NULL,
    valeur_stock_total = NULL,
    date_maj = NOW()
WHERE methode_valorisation_stock_id IN (
    SELECT id FROM methode_valorisation_stock WHERE code IN ('FIFO', 'LIFO')
)
AND cmup_actuel IS NOT NULL;

-- 3. Afficher les stocks FIFO/LIFO (APRÈS correction)
SELECT 
    s.id,
    a.reference,
    a.designation,
    d.nom as depot_nom,
    mvs.code as methode_valorisation,
    s.quantite_actuelle,
    s.cmup_actuel as cmup_apres,
    s.valeur_stock_total as valeur_apres
FROM stock s
INNER JOIN article a ON s.article_id = a.id
INNER JOIN depot d ON s.depot_id = d.id
INNER JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
WHERE mvs.code IN ('FIFO', 'LIFO')
ORDER BY d.nom, a.reference;

-- 4. Vérifier qu'il ne reste plus de CMUP dans les stocks FIFO/LIFO
SELECT 
    mvs.code as methode,
    COUNT(*) as nb_stocks,
    COUNT(CASE WHEN s.cmup_actuel IS NOT NULL THEN 1 END) as nb_avec_cmup,
    COUNT(CASE WHEN s.cmup_actuel IS NULL THEN 1 END) as nb_sans_cmup
FROM stock s
INNER JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
GROUP BY mvs.code
ORDER BY mvs.code;
