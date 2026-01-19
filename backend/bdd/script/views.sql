-- ==============================================================================
-- FICHIER : VIEWS_ANALYTICS.SQL
-- Vues optimisées pour le Dashboard et les Rapports
-- ==============================================================================

-- 1. VUE : ÉTAT DU STOCK VALORISÉ PAR FILIALE
-- Affiche : Quel produit est où, combien on en a, et combien ça vaut (PMP).
CREATE OR REPLACE VIEW v_stock_valorise AS
SELECT 
    s.id AS stock_id,
    e.nom AS filiale,
    cat.libelle AS categorie,
    a.reference,
    a.designation,
    u.code AS unite,
    s.quantite_actuelle,
    a.prix_achat_ref,
    (s.quantite_actuelle * a.prix_achat_ref) AS valeur_totale_stock
FROM stock s
JOIN entreprise e ON s.entreprise_id = e.id
JOIN article a ON s.article_id = a.id
JOIN article_categorie cat ON a.article_categorie_id = cat.id
JOIN unite u ON a.unite_id = u.id
ORDER BY e.nom, a.designation;

-- 2. VUE : HISTORIQUE DES MOUVEMENTS (FICHE DE STOCK)
-- Affiche : Le journal d'entrées/sorties façon comptable.
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
    -- Affichage conditionnel pour lisibilité
    CASE WHEN ms.quantite_entree > 0 THEN ms.quantite_entree ELSE NULL END AS entree,
    CASE WHEN ms.quantite_sortie > 0 THEN ms.quantite_sortie ELSE NULL END AS sortie,
    ms.quantite_stock_apres
FROM mouvement_stock ms
JOIN article a ON ms.article_id = a.id
JOIN entreprise e ON ms.entreprise_id = e.id
JOIN personnel p ON ms.personnel_id = p.id
ORDER BY ms.date_mouvement DESC;

-- 3. VUE : CRÉANCES CLIENTS (QUI DOIT DE L'ARGENT ?)
-- Affiche : Les factures de vente non soldées.
CREATE OR REPLACE VIEW v_creances_clients AS
SELECT 
    fv.id,
    fv.numero_facture,
    fv.date_facture,
    filiale.nom AS filiale_creancier,
    client.nom AS client_debiteur,
    client.telephone,
    fv.montant_ttc,
    fv.reste_a_payer,
    -- Calcul de l'ancienneté de la dette (en jours)
    (CURRENT_DATE - fv.date_facture) AS jours_retard
FROM facture_vente fv
JOIN entreprise filiale ON fv.entreprise_filiale_id = filiale.id
JOIN entreprise client ON fv.entreprise_client_id = client.id
JOIN statut s ON fv.statut_id = s.id
WHERE fv.reste_a_payer > 0 AND s.code <> 'ANNULE'
ORDER BY fv.date_facture ASC;

-- 4. VUE : JOURNAL DE CAISSE JOURNALIER
-- Affiche : Les mouvements d'argent pour validation de fin de journée.
CREATE OR REPLACE VIEW v_journal_caisse AS
SELECT 
    cm.id,
    cm.date_mouvement,
    c.libelle AS nom_caisse,
    cm.libelle_operation,
    cm.montant_entree AS recette,
    cm.montant_sortie AS depense,
    cm.solde_apres,
    pers.nom AS caissier
FROM caisse_mouvement cm
JOIN caisse c ON cm.caisse_id = c.id
JOIN personnel pers ON cm.personnel_id = pers.id
ORDER BY cm.date_mouvement DESC;

-- 5. VUE : CHIFFRE D'AFFAIRES MENSUEL PAR FILIALE
-- Affiche : Données prêtes pour un graphique en barres.
CREATE OR REPLACE VIEW v_stats_ca_mensuel AS
SELECT 
    e.nom AS filiale,
    TO_CHAR(fv.date_facture, 'YYYY-MM') AS mois_annee,
    SUM(fv.montant_ttc) AS ca_total_ttc,
    COUNT(fv.id) AS nombre_ventes
FROM facture_vente fv
JOIN entreprise e ON fv.entreprise_filiale_id = e.id
JOIN statut s ON fv.statut_id = s.id
WHERE s.code IN ('VALIDE', 'PAYE', 'LIVRE')
GROUP BY e.nom, TO_CHAR(fv.date_facture, 'YYYY-MM')
ORDER BY mois_annee DESC;

-- 6. VUE : ARTICLES A REAPPROVISIONNER (ALERTE)
CREATE OR REPLACE VIEW v_alerte_stock AS
SELECT 
    e.nom AS filiale,
    a.reference,
    a.designation,
    s.quantite_actuelle
FROM stock s
JOIN article a ON s.article_id = a.id
JOIN entreprise e ON s.entreprise_id = e.id
WHERE s.quantite_actuelle <= 5 -- Seuil fixe (peut être rendu dynamique)
ORDER BY s.quantite_actuelle ASC;

