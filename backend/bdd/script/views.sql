-- ==============================================================================
-- FICHIER : VIEWS_ANALYTICS.SQL
-- Vues optimisées pour l'Architecture V3.3 (Groupe > Entreprise > Site > Dépôt)
-- ==============================================================================

-- 0. VUE : STRUCTURE ORGANISATIONNELLE (NOUVEAU)
-- Permet de visualiser la hiérarchie complète pour les listes déroulantes ou les audits
CREATE OR REPLACE VIEW v_structure_organisation AS
SELECT 
    g.nom AS groupe,
    e.nom AS entreprise,
    s.nom AS site_geo,
    d.id AS depot_id,
    d.nom AS depot_logistique
FROM depot d
JOIN site s ON d.site_id = s.id
JOIN entreprise e ON s.entreprise_id = e.id
LEFT JOIN groupe g ON e.groupe_id = g.id;

-- 1. VUE : ÉTAT DU STOCK VALORISÉ (PAR DÉPÔT)
-- Adaptation : Lien via depot -> site -> entreprise
CREATE OR REPLACE VIEW v_stock_valorise AS
SELECT 
    s.id AS stock_id,
    e.nom AS filiale,
    si.nom AS site,
    d.nom AS depot, -- Précision logistique
    cat.libelle AS categorie,
    a.reference,
    a.designation,
    u.code AS unite,
    s.quantite_actuelle,
    mvs.code AS methode_val,
    s.cmup_actuel AS pu_comptable,
    s.valeur_stock_total AS valeur_comptable,
    -- Valeur Potentielle Vente
    (s.quantite_actuelle * a.prix_vente_ref) AS valeur_vente_potentielle
FROM stock s
JOIN depot d ON s.depot_id = d.id
JOIN site si ON d.site_id = si.id
JOIN entreprise e ON si.entreprise_id = e.id
JOIN article a ON s.article_id = a.id
JOIN article_categorie cat ON a.article_categorie_id = cat.id
JOIN unite u ON a.unite_id = u.id
JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
WHERE s.quantite_actuelle > 0
ORDER BY e.nom, d.nom, a.designation;

-- 2. VUE : ANALYSE DES LOTS (PAR DÉPÔT)
-- Adaptation : Lien via depot_id
CREATE OR REPLACE VIEW v_stock_lots_fifo AS
SELECT 
    l.numero_lot,
    e.nom AS filiale,
    d.nom AS depot,
    a.reference,
    a.designation,
    l.date_entree,
    l.quantite_initiale,
    l.quantite_restante,
    l.prix_unitaire_achat,
    (l.quantite_restante * l.prix_unitaire_achat) AS valeur_restante,
    EXTRACT(DAY FROM (NOW() - l.date_entree)) AS age_stock_jours
FROM lot_stock l
JOIN depot d ON l.depot_id = d.id
JOIN site si ON d.site_id = si.id
JOIN entreprise e ON si.entreprise_id = e.id
JOIN article a ON l.article_id = a.id
WHERE l.statut = 'ACTIF' AND l.quantite_restante > 0
ORDER BY a.reference, l.date_entree ASC;

-- 3. VUE : FICHE DE STOCK (HISTORIQUE PAR DÉPÔT)
-- Adaptation : Lien via depot_id
CREATE OR REPLACE VIEW v_fiche_stock AS
SELECT 
    ms.id,
    ms.date_mouvement,
    e.nom AS filiale,
    d.nom AS depot,
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
JOIN depot d ON ms.depot_id = d.id
JOIN site si ON d.site_id = si.id
JOIN entreprise e ON si.entreprise_id = e.id
JOIN article a ON ms.article_id = a.id
JOIN personnel p ON ms.personnel_id = p.id
ORDER BY ms.date_mouvement DESC;

-- 4. VUE : CRÉANCES CLIENTS
-- Note : Resté au niveau Entreprise (Juridique), mais ajout du Dépôt d'expédition pour info
CREATE OR REPLACE VIEW v_creances_clients AS
SELECT 
    fv.id AS facture_id,
    fv.numero_facture,
    fv.date_facture,
    filiale.nom AS filiale_vendeuse,
    d.nom AS depot_expedition, -- Savoir d'où c'est parti
    client.nom AS client,
    fv.montant_ttc,
    fv.reste_a_payer,
    CASE 
        WHEN fv.montant_ttc > 0 THEN ROUND(((fv.montant_ttc - fv.reste_a_payer) / fv.montant_ttc) * 100, 0)
        ELSE 0 
    END AS pourcentage_paye,
    (CURRENT_DATE - fv.date_facture) AS jours_retard
FROM facture_vente fv
JOIN entreprise filiale ON fv.entreprise_filiale_id = filiale.id
JOIN depot d ON fv.depot_expedition_id = d.id
JOIN entreprise client ON fv.entreprise_client_id = client.id
JOIN statut st ON fv.statut_id = st.id
WHERE fv.reste_a_payer > 0 AND st.code <> 'ANNULE'
ORDER BY fv.date_facture ASC;

-- 5. VUE : DETTES FOURNISSEURS
CREATE OR REPLACE VIEW v_dettes_fournisseurs AS
SELECT 
    fa.id AS facture_id,
    fa.numero_facture_fournisseur,
    fa.date_facture,
    filiale.nom AS filiale_payeur,
    d.nom AS depot_reception, -- Savoir où c'est arrivé
    fourn.nom AS fournisseur,
    fa.montant_ttc,
    fa.reste_a_payer,
    (CURRENT_DATE - fa.date_facture) AS anciennete_facture_jours
FROM facture_achat fa
JOIN entreprise filiale ON fa.entreprise_filiale_id = filiale.id
JOIN depot d ON fa.depot_reception_id = d.id
JOIN entreprise fourn ON fa.entreprise_fournisseur_id = fourn.id
JOIN statut st ON fa.statut_id = st.id
WHERE fa.reste_a_payer > 0 AND st.code <> 'ANNULE'
ORDER BY fa.date_facture ASC;

-- 6. VUE : JOURNAL DE CAISSE
-- Adaptation : Ajout de la Filiale propriétaire de la caisse
CREATE OR REPLACE VIEW v_journal_caisse AS
SELECT 
    cm.id,
    cm.date_mouvement,
    e.nom AS filiale, -- Pour savoir à qui est l'argent
    c.libelle AS caisse,
    cm.libelle_operation,
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
JOIN entreprise e ON c.entreprise_id = e.id
JOIN personnel p ON cm.personnel_id = p.id
LEFT JOIN paiement_vente pv ON pv.caisse_mouvement_id = cm.id
LEFT JOIN facture_vente fv ON pv.facture_vente_id = fv.id
LEFT JOIN paiement_achat pa ON pa.caisse_mouvement_id = cm.id
LEFT JOIN facture_achat fa ON pa.facture_achat_id = fa.id
ORDER BY cm.date_mouvement DESC;

-- 7. VUE : DASHBOARD KPI
-- Agrégation globale (Stock de tous les dépôts)
CREATE OR REPLACE VIEW v_dashboard_kpi AS
SELECT
    (SELECT COALESCE(SUM(valeur_stock_total), 0) FROM stock) AS valeur_stock_global,
    (SELECT COALESCE(SUM(solde_actuel), 0) FROM caisse) AS tresorerie_totale,
    (SELECT COALESCE(SUM(reste_a_payer), 0) FROM facture_vente WHERE statut_id <> 7) AS creances_clients,
    (SELECT COALESCE(SUM(reste_a_payer), 0) FROM facture_achat WHERE statut_id <> 7) AS dettes_fournisseurs;

-- 8. VUE : STOCK CONSOLIDÉ GROUPE (NOUVEAU)
-- Permet de voir "Combien de PC Dell on a au total ?" sans se soucier du dépôt
CREATE OR REPLACE VIEW v_stock_consolide_groupe AS
SELECT 
    a.reference,
    a.designation,
    u.code AS unite,
    SUM(s.quantite_actuelle) AS qte_totale_groupe,
    SUM(s.valeur_stock_total) AS valeur_totale_groupe
FROM stock s
JOIN article a ON s.article_id = a.id
JOIN unite u ON a.unite_id = u.id
GROUP BY a.reference, a.designation, u.code
ORDER BY qte_totale_groupe DESC;

-- 9. VUE : ALERTE STOCK PAR DÉPÔT
CREATE OR REPLACE VIEW v_alerte_stock_depot AS
SELECT 
    e.nom AS filiale,
    d.nom AS depot,
    a.reference,
    a.designation,
    s.quantite_actuelle
FROM stock s
JOIN depot d ON s.depot_id = d.id
JOIN site si ON d.site_id = si.id
JOIN entreprise e ON si.entreprise_id = e.id
JOIN article a ON s.article_id = a.id
WHERE s.quantite_actuelle <= 5
ORDER BY s.quantite_actuelle ASC;