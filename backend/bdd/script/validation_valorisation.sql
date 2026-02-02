-- =============================================================================
-- REQUÊTES DE VALIDATION DES MÉTHODES DE VALORISATION
-- Tests et vérifications des calculs CMUP, FIFO, LIFO
-- =============================================================================

\c achat_vente_db;

-- -----------------------------------------------------------------------------
-- 1. VALIDATION DES STOCKS ACTUELS PAR MÉTHODE
-- -----------------------------------------------------------------------------

SELECT 
    '=== STOCKS ACTUELS PAR MÉTHODE DE VALORISATION ===' as title;

SELECT 
    a.reference,
    a.designation,
    d.nom as depot,
    mvs.code as methode_valorisation,
    s.quantite_actuelle,
    s.cmup_actuel,
    s.valeur_stock_total,
    ROUND(s.valeur_stock_total / NULLIF(s.quantite_actuelle, 0), 2) as prix_unitaire_moyen
FROM stock s
JOIN article a ON s.article_id = a.id
JOIN depot d ON s.depot_id = d.id
JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
WHERE s.quantite_actuelle > 0
ORDER BY a.reference, mvs.code;

-- -----------------------------------------------------------------------------
-- 2. DÉTAIL DES LOTS PAR ARTICLE ET DÉPÔT
-- -----------------------------------------------------------------------------

SELECT 
    '=== DÉTAIL DES LOTS ACTIFS ===' as title;

SELECT 
    a.reference,
    d.nom as depot,
    ls.numero_lot,
    ls.date_entree::date,
    ls.quantite_initiale,
    ls.quantite_restante,
    ls.prix_unitaire_achat,
    ls.prix_unitaire_achat * ls.quantite_restante as valeur_lot_restant
FROM lot_stock ls
JOIN article a ON ls.article_id = a.id
JOIN depot d ON ls.depot_id = d.id
WHERE ls.quantite_restante > 0
ORDER BY a.reference, d.nom, ls.date_entree;

-- -----------------------------------------------------------------------------
-- 3. HISTORIQUE DES MOUVEMENTS POUR LAPTOP001
-- -----------------------------------------------------------------------------

SELECT 
    '=== HISTORIQUE MOUVEMENTS LAPTOP001 ===' as title;

SELECT 
    ms.date_mouvement::timestamp(0),
    d.nom as depot,
    ms.type_mouvement,
    COALESCE(ms.quantite_entree, 0) as entree,
    COALESCE(ms.quantite_sortie, 0) as sortie,
    ms.prix_unitaire_mouvement,
    ms.quantite_stock_apres as stock_apres,
    ms.reference_document
FROM mouvement_stock ms
JOIN article a ON ms.article_id = a.id
JOIN depot d ON ms.depot_id = d.id
WHERE a.reference = 'LAPTOP001'
ORDER BY ms.date_mouvement, d.nom;

-- -----------------------------------------------------------------------------
-- 4. CALCUL MANUEL DU CMUP POUR VÉRIFICATION
-- -----------------------------------------------------------------------------

SELECT 
    '=== VÉRIFICATION CALCUL CMUP LAPTOP001 ===' as title;

WITH entrees_laptop AS (
    SELECT 
        ms.depot_id,
        SUM(ms.quantite_entree * ms.prix_unitaire_mouvement) as valeur_totale_entrees,
        SUM(ms.quantite_entree) as quantite_totale_entrees
    FROM mouvement_stock ms
    JOIN article a ON ms.article_id = a.id
    WHERE a.reference = 'LAPTOP001' 
    AND ms.type_mouvement = 'ENTREE_ACHAT'
    GROUP BY ms.depot_id
),
sorties_laptop AS (
    SELECT 
        ms.depot_id,
        SUM(ms.quantite_sortie * ms.prix_unitaire_mouvement) as valeur_totale_sorties,
        SUM(ms.quantite_sortie) as quantite_totale_sorties
    FROM mouvement_stock ms
    JOIN article a ON ms.article_id = a.id
    WHERE a.reference = 'LAPTOP001' 
    AND ms.type_mouvement = 'SORTIE_VENTE'
    GROUP BY ms.depot_id
)
SELECT 
    d.nom as depot,
    mvs.code as methode,
    e.valeur_totale_entrees,
    e.quantite_totale_entrees,
    ROUND(e.valeur_totale_entrees / e.quantite_totale_entrees, 2) as cmup_calcule,
    s.valeur_totale_sorties,
    s.quantite_totale_sorties,
    (e.quantite_totale_entrees - COALESCE(s.quantite_totale_sorties, 0)) as stock_restant_calcule,
    (e.valeur_totale_entrees - COALESCE(s.valeur_totale_sorties, 0)) as valeur_restante_calculee
FROM entrees_laptop e
LEFT JOIN sorties_laptop s ON e.depot_id = s.depot_id
JOIN depot d ON e.depot_id = d.id
JOIN stock st ON st.depot_id = d.id AND st.article_id = (SELECT id FROM article WHERE reference = 'LAPTOP001')
JOIN methode_valorisation_stock mvs ON st.methode_valorisation_stock_id = mvs.id;

-- -----------------------------------------------------------------------------
-- 5. COMPARAISON DES VALORISATIONS ENTRE MÉTHODES
-- -----------------------------------------------------------------------------

SELECT 
    '=== COMPARAISON VALORISATIONS LAPTOP001 APRÈS SORTIE ===' as title;

SELECT 
    mvs.code as methode,
    mvs.libelle,
    s.quantite_actuelle as stock_restant,
    s.cmup_actuel as prix_unitaire,
    s.valeur_stock_total,
    CASE 
        WHEN mvs.code = 'CMUP' THEN 'Coût moyen pondéré des entrées'
        WHEN mvs.code = 'FIFO' THEN 'Valorisation au coût des lots les plus anciens'
        WHEN mvs.code = 'LIFO' THEN 'Valorisation au coût des lots les plus récents'
    END as explication
FROM stock s
JOIN article a ON s.article_id = a.id
JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
WHERE a.reference = 'LAPTOP001'
ORDER BY mvs.code;

-- -----------------------------------------------------------------------------
-- 6. ANALYSE DE L'IMPACT SUR LA MARGE
-- -----------------------------------------------------------------------------

SELECT 
    '=== IMPACT SUR LA MARGE POUR VENTE DE 3 LAPTOP001 ===' as title;

WITH vente_data AS (
    SELECT 3 as quantite_vendue, 3200000 as prix_vente_unitaire, 9600000 as ca_total
),
couts_par_methode AS (
    SELECT 
        mvs.code as methode,
        CASE 
            WHEN mvs.code = 'CMUP' THEN 2521212.12
            WHEN mvs.code = 'FIFO' THEN 2400000
            WHEN mvs.code = 'LIFO' THEN 2450000
        END as cout_unitaire_sortie
    FROM methode_valorisation_stock mvs
    WHERE mvs.code IN ('CMUP', 'FIFO', 'LIFO')
)
SELECT 
    c.methode,
    v.quantite_vendue,
    v.prix_vente_unitaire,
    v.ca_total as chiffre_affaires,
    c.cout_unitaire_sortie,
    (c.cout_unitaire_sortie * v.quantite_vendue) as cout_marchandises_vendues,
    (v.ca_total - c.cout_unitaire_sortie * v.quantite_vendue) as marge_brute,
    ROUND(
        ((v.ca_total - c.cout_unitaire_sortie * v.quantite_vendue) / v.ca_total) * 100, 
        2
    ) as taux_marge_pct
FROM vente_data v
CROSS JOIN couts_par_methode c
ORDER BY c.methode;

-- -----------------------------------------------------------------------------
-- 7. VÉRIFICATION DE LA COHÉRENCE DES LOTS
-- -----------------------------------------------------------------------------

SELECT 
    '=== COHÉRENCE QUANTITÉS LOTS VS STOCK ===' as title;

SELECT 
    a.reference,
    d.nom as depot,
    s.quantite_actuelle as stock_comptable,
    SUM(ls.quantite_restante) as total_lots,
    (s.quantite_actuelle - SUM(ls.quantite_restante)) as ecart,
    CASE 
        WHEN s.quantite_actuelle = SUM(ls.quantite_restante) THEN '✅ Cohérent'
        ELSE '❌ Incohérent'
    END as statut
FROM stock s
JOIN article a ON s.article_id = a.id
JOIN depot d ON s.depot_id = d.id
LEFT JOIN lot_stock ls ON ls.article_id = a.id AND ls.depot_id = d.id AND ls.statut = 'ACTIF'
WHERE s.quantite_actuelle > 0
GROUP BY a.reference, d.nom, s.quantite_actuelle
ORDER BY a.reference, d.nom;

-- -----------------------------------------------------------------------------
-- 8. REQUÊTE DE PERFORMANCE : TOP ARTICLES PAR VALEUR
-- -----------------------------------------------------------------------------

SELECT 
    '=== TOP 5 ARTICLES PAR VALEUR DE STOCK ===' as title;

SELECT 
    ROW_NUMBER() OVER (ORDER BY s.valeur_stock_total DESC) as rang,
    a.reference,
    a.designation,
    d.nom as depot,
    mvs.code as methode,
    s.quantite_actuelle,
    s.valeur_stock_total,
    ROUND(s.valeur_stock_total / NULLIF(s.quantite_actuelle, 0), 0) as prix_unitaire_moyen
FROM stock s
JOIN article a ON s.article_id = a.id
JOIN depot d ON s.depot_id = d.id
JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
WHERE s.quantite_actuelle > 0
ORDER BY s.valeur_stock_total DESC
LIMIT 5;

-- -----------------------------------------------------------------------------
-- 9. STATISTIQUES GLOBALES
-- -----------------------------------------------------------------------------

SELECT 
    '=== STATISTIQUES GLOBALES ===' as title;

SELECT 
    'Total articles différents' as metric,
    COUNT(DISTINCT s.article_id)::text as value
FROM stock s WHERE s.quantite_actuelle > 0
UNION ALL
SELECT 
    'Total dépôts avec stock',
    COUNT(DISTINCT s.depot_id)::text
FROM stock s WHERE s.quantite_actuelle > 0
UNION ALL
SELECT 
    'Valeur totale du stock',
    TO_CHAR(SUM(s.valeur_stock_total), 'FM999,999,999,999') || ' Ar'
FROM stock s WHERE s.quantite_actuelle > 0
UNION ALL
SELECT 
    'Nombre de lots actifs',
    COUNT(*)::text
FROM lot_stock WHERE statut = 'ACTIF'
UNION ALL
SELECT 
    'Nombre de mouvements',
    COUNT(*)::text
FROM mouvement_stock;

-- =============================================================================
-- INSTRUCTIONS D'UTILISATION :
-- =============================================================================

/*
Pour exécuter ces validations :
1. psql -U postgres -d achat_vente_db -f validation_valorisation.sql

Résultats attendus pour LAPTOP001 après sortie de 3 unités :

CMUP (Dépôt 1) :
- Stock restant : 30 unités
- Valeur unitaire : 2,521,212.12 Ar
- Valeur totale : 75,636,363.60 Ar

FIFO (Dépôt 2) :
- Stock restant : 30 unités  
- Composition : 7×2,400,000 + 15×2,600,000 + 8×2,450,000
- Valeur totale : 76,000,000 Ar

LIFO (Dépôt 3) :
- Stock restant : 30 unités
- Composition : 10×2,400,000 + 15×2,600,000 + 5×2,450,000  
- Valeur totale : 75,250,000 Ar

La différence de valorisation est de :
- FIFO - LIFO = 750,000 Ar (1.0%)
- CMUP est entre les deux
*/