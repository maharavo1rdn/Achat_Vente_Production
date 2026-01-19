-- ==============================================================================
-- FICHIER : VIEWS_ANALYTICS.SQL
-- Vues optimisées pour l'Architecture V3.2
-- ==============================================================================

-- 1. VUE : ÉTAT DU STOCK VALORISÉ
-- Affiche le stock réel, sa méthode de valorisation et sa valeur comptable (CMUP)
CREATE OR REPLACE VIEW v_stock_valorise AS
SELECT 
    s.id AS stock_id,
    e.nom AS filiale,
    cat.libelle AS categorie,
    a.reference,
    a.designation,
    u.code AS unite,
    s.quantite_actuelle,
    mvs.code AS methode_val, -- CMUP, FIFO, LIFO
    s.cmup_actuel AS prix_unitaire_comptable,
    s.valeur_stock_total AS valeur_totale_comptable,
    -- Comparaison avec le prix de vente catalogue pour estimer le CA potentiel
    a.prix_vente_ref,
    (s.quantite_actuelle * a.prix_vente_ref) AS valeur_vente_potentielle
FROM stock s
JOIN entreprise e ON s.entreprise_id = e.id
JOIN article a ON s.article_id = a.id
JOIN article_categorie cat ON a.article_categorie_id = cat.id
JOIN unite u ON a.unite_id = u.id
JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
WHERE s.quantite_actuelle > 0
ORDER BY e.nom, a.designation;

-- 2. VUE : ANALYSE DES LOTS (POUR FIFO/LIFO)
-- Permet de voir l'âge des stocks ("Stock Ageing")
CREATE OR REPLACE VIEW v_stock_lots_fifo AS
SELECT 
    l.numero_lot,
    e.nom AS filiale,
    a.reference,
    a.designation,
    l.date_entree,
    l.quantite_initiale,
    l.quantite_restante,
    l.prix_unitaire_achat,
    (l.quantite_restante * l.prix_unitaire_achat) AS valeur_restante_lot,
    -- Calcul de l'âge en jours
    EXTRACT(DAY FROM (NOW() - l.date_entree)) AS age_stock_jours,
    ms.reference_document AS source_achat
FROM lot_stock l
JOIN article a ON l.article_id = a.id
JOIN entreprise e ON l.entreprise_id = e.id
LEFT JOIN mouvement_stock ms ON l.mouvement_entree_id = ms.id
WHERE l.statut = 'ACTIF' AND l.quantite_restante > 0
ORDER BY a.reference, l.date_entree ASC;

-- 3. VUE : FICHE DE STOCK (HISTORIQUE)
-- Trace tous les mouvements avec l'opérateur responsable
CREATE OR REPLACE VIEW v_fiche_stock AS
SELECT 
    ms.id,
    ms.date_mouvement,
    e.nom AS filiale,
    a.reference,
    a.designation,
    ms.type_mouvement,
    ms.reference_document,
    p.nom || ' ' || p.prenom AS operateur,
    ms.quantite_stock_avant,
    CASE WHEN ms.quantite_entree > 0 THEN ms.quantite_entree ELSE NULL END AS entree,
    CASE WHEN ms.quantite_sortie > 0 THEN ms.quantite_sortie ELSE NULL END AS sortie,
    ms.quantite_stock_apres,
    ms.prix_unitaire_mouvement
FROM mouvement_stock ms
JOIN article a ON ms.article_id = a.id
JOIN entreprise e ON ms.entreprise_id = e.id
JOIN personnel p ON ms.personnel_id = p.id
ORDER BY ms.date_mouvement DESC;

-- 4. VUE : CRÉANCES CLIENTS (ARGENT À RECEVOIR)
-- Basé sur la table facture_vente
CREATE OR REPLACE VIEW v_creances_clients AS
SELECT 
    fv.id AS facture_id,
    fv.numero_facture,
    fv.date_facture,
    filiale.nom AS filiale_vendeuse,
    client.nom AS client,
    client.telephone AS contact_client,
    fv.montant_ttc,
    fv.reste_a_payer,
    -- Progression du paiement (0% à 100%)
    CASE 
        WHEN fv.montant_ttc > 0 THEN ROUND(((fv.montant_ttc - fv.reste_a_payer) / fv.montant_ttc) * 100, 0)
        ELSE 0 
    END AS pourcentage_paye,
    st.libelle AS statut_facture,
    (CURRENT_DATE - fv.date_facture) AS jours_retard
FROM facture_vente fv
JOIN entreprise filiale ON fv.entreprise_filiale_id = filiale.id
JOIN entreprise client ON fv.entreprise_client_id = client.id
JOIN statut st ON fv.statut_id = st.id
WHERE fv.reste_a_payer > 0 AND st.code <> 'ANNULE'
ORDER BY fv.date_facture ASC;

-- 5. VUE : DETTES FOURNISSEURS (ARGENT À SORTIR)
-- Basé sur la table facture_achat
CREATE OR REPLACE VIEW v_dettes_fournisseurs AS
SELECT 
    fa.id AS facture_id,
    fa.numero_facture_fournisseur,
    fa.date_facture,
    filiale.nom AS filiale_payeur,
    fourn.nom AS fournisseur,
    fa.montant_ttc,
    fa.reste_a_payer,
    st.libelle AS statut_facture,
    (CURRENT_DATE - fa.date_facture) AS anciennete_facture_jours
FROM facture_achat fa
JOIN entreprise filiale ON fa.entreprise_filiale_id = filiale.id
JOIN entreprise fourn ON fa.entreprise_fournisseur_id = fourn.id
JOIN statut st ON fa.statut_id = st.id
WHERE fa.reste_a_payer > 0 AND st.code <> 'ANNULE'
ORDER BY fa.date_facture ASC;

-- 6. VUE : JOURNAL DE CAISSE INTELLIGENT
-- Fait le lien entre le mouvement d'argent et la facture correspondante (Vente ou Achat)
CREATE OR REPLACE VIEW v_journal_caisse AS
SELECT 
    cm.id,
    cm.date_mouvement,
    c.libelle AS caisse,
    cm.libelle_operation,
    -- Tentative de trouver le numéro de document lié (Facture Vente ou Achat)
    CASE 
        WHEN fv.numero_facture IS NOT NULL THEN 'Vente: ' || fv.numero_facture
        WHEN fa.numero_facture_fournisseur IS NOT NULL THEN 'Achat: ' || fa.numero_facture_fournisseur
        ELSE 'Divers'
    END AS document_lie,
    cm.montant_entree AS recette,
    cm.montant_sortie AS depense,
    cm.solde_apres,
    p.nom AS caissier
FROM caisse_mouvement cm
JOIN caisse c ON cm.caisse_id = c.id
JOIN personnel p ON cm.personnel_id = p.id
-- Jointures LEFT pour ne pas perdre les mouvements divers (apport capital, etc.)
LEFT JOIN paiement_vente pv ON pv.caisse_mouvement_id = cm.id
LEFT JOIN facture_vente fv ON pv.facture_vente_id = fv.id
LEFT JOIN paiement_achat pa ON pa.caisse_mouvement_id = cm.id
LEFT JOIN facture_achat fa ON pa.facture_achat_id = fa.id
ORDER BY cm.date_mouvement DESC;

-- 7. VUE : PERFORMANCE PRODUIT (MARGE ESTIMÉE)
-- Calcule le CA et la Marge Brute approximative (Prix Vente - Prix Achat Ref)
CREATE OR REPLACE VIEW v_performance_produit AS
SELECT 
    e.nom AS filiale,
    cat.libelle AS categorie,
    a.designation,
    SUM(fvd.quantite) AS quantite_vendue,
    SUM(fvd.quantite * fvd.prix_unitaire) AS ca_ht_total,
    -- Marge estimée
    SUM(fvd.quantite * (fvd.prix_unitaire - a.prix_achat_ref)) AS marge_brute_estimee
FROM facture_vente_details fvd
JOIN facture_vente fv ON fvd.facture_vente_id = fv.id
JOIN article a ON fvd.article_id = a.id
JOIN article_categorie cat ON a.article_categorie_id = cat.id
JOIN entreprise e ON fv.entreprise_filiale_id = e.id
JOIN statut s ON fv.statut_id = s.id
WHERE s.code IN ('VALIDE', 'PAYE', 'LIVRE')
GROUP BY e.nom, cat.libelle, a.designation
ORDER BY ca_ht_total DESC;

-- 8. VUE : DASHBOARD KPI (CHIFFRES CLÉS)
-- Vue agrégée pour l'écran d'accueil (retourne 1 seule ligne)
CREATE OR REPLACE VIEW v_dashboard_kpi AS
SELECT
    (SELECT COALESCE(SUM(valeur_stock_total), 0) FROM stock) AS valeur_stock_global,
    (SELECT COALESCE(SUM(solde_actuel), 0) FROM caisse) AS tresorerie_totale,
    (SELECT COALESCE(SUM(reste_a_payer), 0) FROM facture_vente WHERE statut_id <> 7) AS creances_clients,
    (SELECT COALESCE(SUM(reste_a_payer), 0) FROM facture_achat WHERE statut_id <> 7) AS dettes_fournisseurs;

-- 9. VUE : STATS CA MENSUEL
CREATE OR REPLACE VIEW v_stats_ca_mensuel AS
SELECT 
    e.nom AS filiale,
    TO_CHAR(fv.date_facture, 'YYYY-MM') AS mois_annee,
    SUM(fv.montant_ttc) AS ca_ttc,
    COUNT(fv.id) AS nombre_ventes
FROM facture_vente fv
JOIN entreprise e ON fv.entreprise_filiale_id = e.id
JOIN statut s ON fv.statut_id = s.id
WHERE s.code IN ('VALIDE', 'PAYE', 'LIVRE')
GROUP BY e.nom, TO_CHAR(fv.date_facture, 'YYYY-MM')
ORDER BY mois_annee DESC;