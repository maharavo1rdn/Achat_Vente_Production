-- =============================================================================
-- DONNÉES DE TEST POUR VALORISATION DE STOCK
-- Scénarios complets pour tester CMUP, FIFO, LIFO
-- =============================================================================

\c achat_vente_db;

-- -----------------------------------------------------------------------------
-- 0. NETTOYAGE DES DONNÉES EXISTANTES (dans l'ordre des dépendances)
-- -----------------------------------------------------------------------------

-- Désactiver temporairement les contraintes de clé étrangère
SET session_replication_role = replica;

-- Nettoyer les données dans l'ordre inverse des dépendances
TRUNCATE TABLE paiement_achat_details CASCADE;
TRUNCATE TABLE paiement_achat CASCADE;
TRUNCATE TABLE paiement_vente_details CASCADE;
TRUNCATE TABLE paiement_vente CASCADE;
TRUNCATE TABLE caisse_mouvement CASCADE;
TRUNCATE TABLE caisse CASCADE;
TRUNCATE TABLE facture_achat_details CASCADE;
TRUNCATE TABLE facture_achat CASCADE;
TRUNCATE TABLE facture_vente_details CASCADE;
TRUNCATE TABLE facture_vente CASCADE;
TRUNCATE TABLE bon_commande_achat_details CASCADE;
TRUNCATE TABLE bon_commande_achat CASCADE;
TRUNCATE TABLE bon_commande_vente_details CASCADE;
TRUNCATE TABLE bon_commande_vente CASCADE;
TRUNCATE TABLE proforma_fournisseur_details CASCADE;
TRUNCATE TABLE proforma_fournisseur CASCADE;
TRUNCATE TABLE proforma_demande_achat_details CASCADE;
TRUNCATE TABLE proforma_demande_achat CASCADE;
TRUNCATE TABLE devis_vente_details CASCADE;
TRUNCATE TABLE devis_vente CASCADE;
TRUNCATE TABLE sortie_lot_detail CASCADE;
TRUNCATE TABLE lot_stock CASCADE;
TRUNCATE TABLE mouvement_stock CASCADE;
TRUNCATE TABLE stock CASCADE;
TRUNCATE TABLE article CASCADE;
TRUNCATE TABLE personnel CASCADE;
TRUNCATE TABLE depot CASCADE;
TRUNCATE TABLE site CASCADE;
TRUNCATE TABLE entreprise CASCADE;
TRUNCATE TABLE groupe CASCADE;

-- Nettoyer les tables de référence
TRUNCATE TABLE statut CASCADE;
TRUNCATE TABLE unite CASCADE;
TRUNCATE TABLE article_categorie CASCADE;
TRUNCATE TABLE personnel_role CASCADE;
TRUNCATE TABLE mode_paiement CASCADE;
TRUNCATE TABLE methode_valorisation_stock CASCADE;

-- Réactiver les contraintes
SET session_replication_role = DEFAULT;

-- Réinitialiser les séquences
ALTER SEQUENCE IF EXISTS groupe_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS entreprise_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS site_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS depot_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS personnel_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS article_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS stock_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS mouvement_stock_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS lot_stock_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS caisse_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS statut_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS unite_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS article_categorie_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS personnel_role_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS mode_paiement_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS methode_valorisation_stock_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS proforma_demande_achat_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS proforma_fournisseur_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS bon_commande_achat_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS bon_commande_vente_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS facture_achat_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS facture_vente_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS devis_vente_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS devis_num_seq RESTART WITH 1;

SELECT '🧹 Nettoyage terminé - Base prête pour les données de test' as status;

-- -----------------------------------------------------------------------------
-- 1. DONNÉES DE RÉFÉRENCE
-- -----------------------------------------------------------------------------

-- Statuts
INSERT INTO statut (code, libelle, niveau) VALUES 
('EN_ATTENTE', 'En attente', 1),
('VALIDE', 'Validé', 2),
('EN_COURS', 'En cours', 3),
('LIVRE', 'Livré', 4),
('FACTURE', 'Facturé', 5),
('PAYE', 'Payé', 6),
('ANNULE', 'Annulé', 0);

-- Unités
INSERT INTO unite (code, libelle) VALUES 
('PCE', 'Pièce'),
('KG', 'Kilogramme'),
('L', 'Litre'),
('M', 'Mètre'),
('M2', 'Mètre carré');

-- Catégories d'articles
INSERT INTO article_categorie (code, libelle) VALUES 
('ELECTRONIQUE', 'Électronique'),
('INFORMATIQUE', 'Informatique'),
('MOBILIER', 'Mobilier'),
('CONSOMMABLE', 'Consommables'),
('MATERIEL', 'Matériel');

-- Rôles personnel
INSERT INTO personnel_role (code, libelle, niveau_acces) VALUES 
('ADMIN', 'Administrateur', 5),
('GESTIONNAIRE', 'Gestionnaire de stock', 4),
('VENDEUR', 'Vendeur', 3),
('MAGASINIER', 'Magasinier', 2);

-- Modes de paiement
INSERT INTO mode_paiement (code, libelle) VALUES 
('ESPECES', 'Espèces'),
('VIREMENT', 'Virement bancaire'),
('CHEQUE', 'Chèque'),
('CARTE', 'Carte bancaire');

-- Méthodes de valorisation
INSERT INTO methode_valorisation_stock (code, libelle, description) VALUES 
('CMUP', 'Coût Moyen Unitaire Pondéré', 'Valorisation au coût moyen pondéré'),
('FIFO', 'Premier Entré Premier Sorti', 'Valorisation FIFO - First In First Out'),
('LIFO', 'Dernier Entré Premier Sorti', 'Valorisation LIFO - Last In First Out');

-- -----------------------------------------------------------------------------
-- 2. STRUCTURE ORGANISATIONNELLE
-- -----------------------------------------------------------------------------

-- Groupe
INSERT INTO groupe (nom, description) VALUES 
('TECH HOLDING GROUP', 'Groupe holding technologique');

-- Entreprises
INSERT INTO entreprise (nom, groupe_id, type_entreprise, matricule_fiscal, adresse, telephone, email) VALUES 
('TECH DISTRIB (Siege)', 1, 'INTERNE', 'TDS2024001', 'Ankorondrano, Antananarivo', '+261 20 22 123 45', 'contact@techdistrib.mg'),
('FOURNISSEUR ELECTRONICS', 1, 'FOURNISSEUR', 'FE2024001', 'Analakely, Antananarivo', '+261 20 22 567 89', 'vente@electronics.mg'),
('CLIENT ENTREPRISE A', 1, 'CLIENT', 'CEA2024001', 'Tsaralalana, Antananarivo', '+261 20 22 987 65', 'achat@entrepriseA.mg');

-- Sites
INSERT INTO site (nom, adresse, telephone, entreprise_id) VALUES 
('Siege Ankorondrano', 'Lot 123 Ankorondrano', '+261 20 22 123 45', 1),
('Succursale Analakely', 'Rue du Commerce Analakely', '+261 20 22 234 56', 1),
('Entrepot Anosizato', 'Zone Industrielle Anosizato', '+261 20 22 345 67', 1);

-- Dépôts avec différentes méthodes de valorisation
INSERT INTO depot (nom, adresse, site_id) VALUES 
('Entrepot Central', 'Bâtiment A - Ankorondrano', 1),
('Depot Analakely', 'Magasin Analakely', 2),
('Stock Anosizato', 'Entrepot Anosizato', 3);

-- Personnel
INSERT INTO personnel (code_employe, nom, prenom, email, telephone, personnel_role_id, entreprise_id, site_defaut_id) VALUES 
('ADM001', 'RAKOTO', 'Jean', 'j.rakoto@techdistrib.mg', '+261 34 12 345 67', 1, 1, 1),
('GES001', 'RABE', 'Marie', 'm.rabe@techdistrib.mg', '+261 34 23 456 78', 2, 1, 1),
('VEN001', 'RANDRIA', 'Paul', 'p.randria@techdistrib.mg', '+261 34 34 567 89', 3, 1, 2),
('MAG001', 'RAZANA', 'Sophie', 's.razana@techdistrib.mg', '+261 34 45 678 90', 4, 1, 3);

-- Articles pour les tests
INSERT INTO article (reference, designation, description, prix_achat_ref, prix_vente_ref, unite_id, article_categorie_id) VALUES 
('LAPTOP001', 'Ordinateur Portable HP', 'HP ProBook 450 G8 - i5, 8GB RAM, 256GB SSD', 2500000, 3200000, 1, 2),
('MOUSE001', 'Souris Optique', 'Souris optique sans fil Logitech', 45000, 65000, 1, 2),
('CABLE001', 'Câble HDMI', 'Câble HDMI 2.0 - 2 mètres', 25000, 35000, 1, 1),
('PHONE001', 'Smartphone Samsung', 'Galaxy A54 5G - 128GB', 1800000, 2300000, 1, 1),
('KEYBOARD001', 'Clavier Mécanique', 'Clavier Gaming RGB', 180000, 250000, 1, 2);

-- Caisse
INSERT INTO caisse (code_caisse, libelle, solde_actuel, entreprise_id) VALUES 
('CAISSE01', 'Caisse principale', 50000000, 1);

-- -----------------------------------------------------------------------------
-- 3. SCÉNARIO TEST VALORISATION - LAPTOP001
-- -----------------------------------------------------------------------------

-- Stock initial avec méthodes différentes par dépôt
INSERT INTO stock (article_id, depot_id, methode_valorisation_stock_id, quantite_actuelle, cmup_actuel, valeur_stock_total) VALUES 
(1, 1, 1, 0, 0, 0),  -- LAPTOP001 - Depot 1 - CMUP
(1, 2, 2, 0, 0, 0),  -- LAPTOP001 - Depot 2 - FIFO  
(1, 3, 3, 0, 0, 0);  -- LAPTOP001 - Depot 3 - LIFO

-- Demandes d'achat pour créer des entrées de stock
INSERT INTO proforma_demande_achat (numero_da, date_demande, personnel_demandeur_id, entreprise_id, depot_cible_id, motif_achat, statut_id) VALUES 
('DA2024001', '2024-01-15', 2, 1, 1, 'Reconstitution stock laptops', 2),
('DA2024002', '2024-02-10', 2, 1, 1, 'Commande supplémentaire laptops', 2),
('DA2024003', '2024-03-05', 2, 1, 1, 'Stock de sécurité laptops', 2);

-- Détails demandes d'achat
INSERT INTO proforma_demande_achat_details (proforma_demande_achat_id, article_id, quantite_demandee, prix_estime) VALUES 
(1, 1, 10, 2400000),
(2, 1, 15, 2600000),
(3, 1, 8, 2450000);

-- Proformas fournisseur
INSERT INTO proforma_fournisseur (numero_proforma, date_emission, date_validite, entreprise_fournisseur_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, proforma_demande_achat_id) VALUES 
('PRO2024001', '2024-01-16', '2024-02-15', 2, 1, 2, 2, 24000000, 1),
('PRO2024002', '2024-02-11', '2024-03-12', 2, 1, 2, 2, 39000000, 2),
('PRO2024003', '2024-03-06', '2024-04-05', 2, 1, 2, 2, 19600000, 3);

-- Détails proformas
INSERT INTO proforma_fournisseur_details (proforma_fournisseur_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 10, 2400000),
(2, 1, 15, 2600000),
(3, 1, 8, 2450000);

-- Bons de commande
INSERT INTO bon_commande_achat (numero_bc, date_commande, proforma_fournisseur_id, entreprise_fournisseur_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, depot_livraison_id) VALUES 
('BC2024001', '2024-01-20', 1, 2, 1, 2, 4, 24000000, 1),
('BC2024002', '2024-02-15', 2, 2, 1, 2, 4, 39000000, 1),
('BC2024003', '2024-03-10', 3, 2, 1, 2, 4, 19600000, 1);

-- Détails bons de commande
INSERT INTO bon_commande_achat_details (bon_commande_achat_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 10, 2400000),
(2, 1, 15, 2600000),
(3, 1, 8, 2450000);

-- Factures d'achat (réceptions)
INSERT INTO facture_achat (numero_facture_fournisseur, date_facture, bon_commande_achat_id, entreprise_fournisseur_id, entreprise_filiale_id, statut_id, montant_ttc, reste_a_payer, depot_reception_id) VALUES 
('FA2024001', '2024-01-25', 1, 2, 1, 5, 24000000, 24000000, 1),
('FA2024002', '2024-02-20', 2, 2, 1, 5, 39000000, 39000000, 1),
('FA2024003', '2024-03-15', 3, 2, 1, 5, 19600000, 19600000, 1);

-- Détails factures d'achat
INSERT INTO facture_achat_details (facture_achat_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 10, 2400000),
(2, 1, 15, 2600000),
(3, 1, 8, 2450000);

-- -----------------------------------------------------------------------------
-- 4. MOUVEMENTS DE STOCK - ENTRÉES À DIFFÉRENTES DATES ET PRIX
-- -----------------------------------------------------------------------------

-- Première entrée - 25 janvier 2024 - 10 laptops à 2,400,000 Ar
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-01-25 10:00:00', 'ENTREE_ACHAT', 0, 10, 10, 2400000, 1, 1, 4, 'FA2024001');

-- Deuxième entrée - 20 février 2024 - 15 laptops à 2,600,000 Ar  
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-02-20 14:30:00', 'ENTREE_ACHAT', 10, 15, 25, 2600000, 1, 1, 4, 'FA2024002');

-- Troisième entrée - 15 mars 2024 - 8 laptops à 2,450,000 Ar
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-03-15 09:15:00', 'ENTREE_ACHAT', 25, 8, 33, 2450000, 1, 1, 4, 'FA2024003');

-- Dupliquer les mouvements pour les autres dépôts (FIFO et LIFO)
-- Dépôt 2 (FIFO)
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-01-25 10:00:00', 'ENTREE_ACHAT', 0, 10, 10, 2400000, 1, 2, 4, 'FA2024001-D2'),
('2024-02-20 14:30:00', 'ENTREE_ACHAT', 10, 15, 25, 2600000, 1, 2, 4, 'FA2024002-D2'),
('2024-03-15 09:15:00', 'ENTREE_ACHAT', 25, 8, 33, 2450000, 1, 2, 4, 'FA2024003-D2');

-- Dépôt 3 (LIFO)  
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-01-25 10:00:00', 'ENTREE_ACHAT', 0, 10, 10, 2400000, 1, 3, 4, 'FA2024001-D3'),
('2024-02-20 14:30:00', 'ENTREE_ACHAT', 10, 15, 25, 2600000, 1, 3, 4, 'FA2024002-D3'),
('2024-03-15 09:15:00', 'ENTREE_ACHAT', 25, 8, 33, 2450000, 1, 3, 4, 'FA2024003-D3');

-- -----------------------------------------------------------------------------
-- 5. LOTS DE STOCK POUR CHAQUE ENTRÉE
-- -----------------------------------------------------------------------------

-- Lots pour Dépôt 1 (CMUP)
INSERT INTO lot_stock (numero_lot, article_id, depot_id, date_entree, mouvement_entree_id, quantite_initiale, quantite_restante, prix_unitaire_achat, statut) VALUES 
('LOT2024001-D1', 1, 1, '2024-01-25 10:00:00', 1, 10, 10, 2400000, 'ACTIF'),
('LOT2024002-D1', 1, 1, '2024-02-20 14:30:00', 2, 15, 15, 2600000, 'ACTIF'),
('LOT2024003-D1', 1, 1, '2024-03-15 09:15:00', 3, 8, 8, 2450000, 'ACTIF');

-- Lots pour Dépôt 2 (FIFO)
INSERT INTO lot_stock (numero_lot, article_id, depot_id, date_entree, mouvement_entree_id, quantite_initiale, quantite_restante, prix_unitaire_achat, statut) VALUES 
('LOT2024001-D2', 1, 2, '2024-01-25 10:00:00', 4, 10, 10, 2400000, 'ACTIF'),
('LOT2024002-D2', 1, 2, '2024-02-20 14:30:00', 5, 15, 15, 2600000, 'ACTIF'),
('LOT2024003-D2', 1, 2, '2024-03-15 09:15:00', 6, 8, 8, 2450000, 'ACTIF');

-- Lots pour Dépôt 3 (LIFO)
INSERT INTO lot_stock (numero_lot, article_id, depot_id, date_entree, mouvement_entree_id, quantite_initiale, quantite_restante, prix_unitaire_achat, statut) VALUES 
('LOT2024001-D3', 1, 3, '2024-01-25 10:00:00', 7, 10, 10, 2400000, 'ACTIF'),
('LOT2024002-D3', 1, 3, '2024-02-20 14:30:00', 8, 15, 15, 2600000, 'ACTIF'),
('LOT2024003-D3', 1, 3, '2024-03-15 09:15:00', 9, 8, 8, 2450000, 'ACTIF');

-- -----------------------------------------------------------------------------
-- 6. MISE À JOUR DU STOCK APRÈS LES ENTRÉES
-- -----------------------------------------------------------------------------

-- Mise à jour du stock pour chaque dépôt avec les bonnes valeurs
UPDATE stock SET 
    quantite_actuelle = 33,
    cmup_actuel = 2521212.12, -- (10*2400000 + 15*2600000 + 8*2450000) / 33
    valeur_stock_total = 83200000 -- 33 * 2521212.12
WHERE article_id = 1 AND depot_id = 1;

UPDATE stock SET 
    quantite_actuelle = 33,
    cmup_actuel = 2521212.12,
    valeur_stock_total = 83200000 
WHERE article_id = 1 AND depot_id = 2;

UPDATE stock SET 
    quantite_actuelle = 33,
    cmup_actuel = 2521212.12,
    valeur_stock_total = 83200000 
WHERE article_id = 1 AND depot_id = 3;

-- -----------------------------------------------------------------------------
-- 7. SCÉNARIOS DE VENTE POUR TESTER LES VALORISATIONS
-- -----------------------------------------------------------------------------

-- Devis de vente
INSERT INTO devis_vente (numero_devis, date_devis, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc) VALUES 
('DV2024001', '2024-04-01', 3, 1, 3, 2, 96000000);

INSERT INTO devis_vente_details (devis_vente_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 3, 3200000);

-- Bon de commande vente
INSERT INTO bon_commande_vente (numero_bc, date_commande, devis_vente_id, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, depot_expedition_id) VALUES 
('BCV2024001', '2024-04-05', 1, 3, 1, 3, 4, 96000000, 1);

INSERT INTO bon_commande_vente_details (bon_commande_vente_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 3, 3200000);

-- Facture de vente
INSERT INTO facture_vente (numero_facture, date_facture, bon_commande_vente_id, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, reste_a_payer, depot_expedition_id) VALUES 
('FV2024001', '2024-04-10', 1, 3, 1, 3, 5, 96000000, 96000000, 1);

INSERT INTO facture_vente_details (facture_vente_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 3, 3200000);

-- -----------------------------------------------------------------------------
-- 8. MOUVEMENTS DE SORTIE POUR TESTER LES DIFFÉRENTES MÉTHODES
-- -----------------------------------------------------------------------------

-- Sortie de 3 laptops le 10 avril 2024 - DÉPÔT 1 (CMUP)
-- Le coût doit être le CMUP : 3 * 2,521,212.12 = 7,563,636.36
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_sortie, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-04-10 15:00:00', 'SORTIE_VENTE', 33, 3, 30, 2521212.12, 1, 1, 4, 'FV2024001');

-- Sortie de 3 laptops le 10 avril 2024 - DÉPÔT 2 (FIFO)
-- Le coût doit prendre les premiers entrés : 3 * 2,400,000 = 7,200,000
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_sortie, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-04-10 15:00:00', 'SORTIE_VENTE', 33, 3, 30, 2400000, 1, 2, 4, 'FV2024001-D2');

-- Sortie de 3 laptops le 10 avril 2024 - DÉPÔT 3 (LIFO)  
-- Le coût doit prendre les derniers entrés : 3 * 2,450,000 = 7,350,000
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_sortie, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-04-10 15:00:00', 'SORTIE_VENTE', 33, 3, 30, 2450000, 1, 3, 4, 'FV2024001-D3');

-- -----------------------------------------------------------------------------
-- 9. DÉTAILS DE SORTIE PAR LOT (pour FIFO et LIFO)
-- -----------------------------------------------------------------------------

-- Pour FIFO (Dépôt 2) - prendre du lot le plus ancien
INSERT INTO sortie_lot_detail (mouvement_sortie_id, lot_stock_id, quantite_prelevee, prix_unitaire_lot) VALUES 
(11, 4, 3, 2400000); -- 3 pièces du LOT2024001-D2

-- Pour LIFO (Dépôt 3) - prendre du lot le plus récent  
INSERT INTO sortie_lot_detail (mouvement_sortie_id, lot_stock_id, quantite_prelevee, prix_unitaire_lot) VALUES 
(12, 9, 3, 2450000); -- 3 pièces du LOT2024003-D3

-- Mise à jour des quantités restantes dans les lots
UPDATE lot_stock SET quantite_restante = 7 WHERE numero_lot = 'LOT2024001-D2'; -- FIFO
UPDATE lot_stock SET quantite_restante = 5 WHERE numero_lot = 'LOT2024003-D3'; -- LIFO

-- -----------------------------------------------------------------------------
-- 10. MISE À JOUR DU STOCK APRÈS SORTIES
-- -----------------------------------------------------------------------------

-- Mise à jour stock Dépôt 1 (CMUP) - valeur restante : 30 * 2,521,212.12
UPDATE stock SET 
    quantite_actuelle = 30,
    valeur_stock_total = 75636363.6
WHERE article_id = 1 AND depot_id = 1;

-- Mise à jour stock Dépôt 2 (FIFO) - valeur restante : 7*2400000 + 15*2600000 + 8*2450000
UPDATE stock SET 
    quantite_actuelle = 30,
    valeur_stock_total = 76000000
WHERE article_id = 1 AND depot_id = 2;

-- Mise à jour stock Dépôt 3 (LIFO) - valeur restante : 10*2400000 + 15*2600000 + 5*2450000  
UPDATE stock SET 
    quantite_actuelle = 30,
    valeur_stock_total = 75250000
WHERE article_id = 1 AND depot_id = 3;

-- -----------------------------------------------------------------------------
-- 11. DONNÉES SUPPLÉMENTAIRES POUR AUTRES ARTICLES
-- -----------------------------------------------------------------------------

-- Stock pour MOUSE001 avec des prix très variables
INSERT INTO stock (article_id, depot_id, methode_valorisation_stock_id, quantite_actuelle, cmup_actuel, valeur_stock_total) VALUES 
(2, 1, 1, 0, 0, 0);

-- Entrées de souris à des prix différents
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-01-10 08:00:00', 'ENTREE_ACHAT', 0, 50, 50, 40000, 2, 1, 4, 'MOUSE-LOT1'),
('2024-02-15 10:00:00', 'ENTREE_ACHAT', 50, 30, 80, 48000, 2, 1, 4, 'MOUSE-LOT2'),
('2024-03-20 14:00:00', 'ENTREE_ACHAT', 80, 20, 100, 52000, 2, 1, 4, 'MOUSE-LOT3');

-- Lots correspondants
INSERT INTO lot_stock (numero_lot, article_id, depot_id, date_entree, mouvement_entree_id, quantite_initiale, quantite_restante, prix_unitaire_achat, statut) VALUES 
('MOUSE-LOT1-2024', 2, 1, '2024-01-10 08:00:00', 13, 50, 50, 40000, 'ACTIF'),
('MOUSE-LOT2-2024', 2, 1, '2024-02-15 10:00:00', 14, 30, 30, 48000, 'ACTIF'),
('MOUSE-LOT3-2024', 2, 1, '2024-03-20 14:00:00', 15, 20, 20, 52000, 'ACTIF');

-- Mise à jour stock souris - CMUP = (50*40000 + 30*48000 + 20*52000) / 100 = 44800
UPDATE stock SET 
    quantite_actuelle = 100,
    cmup_actuel = 44800,
    valeur_stock_total = 4480000
WHERE article_id = 2 AND depot_id = 1;

-- -----------------------------------------------------------------------------
-- 12. REQUÊTES DE VÉRIFICATION
-- -----------------------------------------------------------------------------

-- Vue pour vérifier les valorisations
CREATE OR REPLACE VIEW v_stock_valorisation AS
SELECT 
    s.article_id,
    a.reference,
    a.designation,
    d.nom as depot,
    mvs.code as methode,
    s.quantite_actuelle,
    s.cmup_actuel,
    s.valeur_stock_total,
    ROUND(s.valeur_stock_total / NULLIF(s.quantite_actuelle, 0), 2) as prix_unitaire_moyen
FROM stock s
JOIN article a ON s.article_id = a.id
JOIN depot d ON s.depot_id = d.id
JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
WHERE s.quantite_actuelle > 0;

-- Vue détaillée des lots
CREATE OR REPLACE VIEW v_lots_detail AS
SELECT 
    ls.numero_lot,
    a.reference,
    a.designation,
    d.nom as depot,
    ls.date_entree,
    ls.quantite_initiale,
    ls.quantite_restante,
    ls.prix_unitaire_achat,
    ls.prix_unitaire_achat * ls.quantite_restante as valeur_lot,
    ls.statut
FROM lot_stock ls
JOIN article a ON ls.article_id = a.id
JOIN depot d ON ls.depot_id = d.id
ORDER BY ls.date_entree;

-- Vue des mouvements de stock
CREATE OR REPLACE VIEW v_mouvements_stock AS
SELECT 
    ms.date_mouvement,
    a.reference,
    d.nom as depot,
    ms.type_mouvement,
    ms.quantite_entree,
    ms.quantite_sortie,
    ms.prix_unitaire_mouvement,
    ms.quantite_stock_apres,
    ms.reference_document
FROM mouvement_stock ms
JOIN article a ON ms.article_id = a.id  
JOIN depot d ON ms.depot_id = d.id
ORDER BY ms.date_mouvement;

-- =============================================================================
-- RÉSULTATS ATTENDUS POUR VALIDATION :
-- =============================================================================

/*
LAPTOP001 après sortie de 3 pièces :

DÉPÔT 1 (CMUP) :
- Coût de sortie : 3 × 2,521,212.12 = 7,563,636.36 Ar
- Stock restant : 30 pièces × 2,521,212.12 = 75,636,363.60 Ar

DÉPÔT 2 (FIFO) :
- Coût de sortie : 3 × 2,400,000 = 7,200,000 Ar (du lot le plus ancien)
- Stock restant : 7×2,400,000 + 15×2,600,000 + 8×2,450,000 = 76,000,000 Ar

DÉPÔT 3 (LIFO) :
- Coût de sortie : 3 × 2,450,000 = 7,350,000 Ar (du lot le plus récent)  
- Stock restant : 10×2,400,000 + 15×2,600,000 + 5×2,450,000 = 75,250,000 Ar

Ces différences montrent l'impact des méthodes de valorisation sur :
1. Le coût des marchandises vendues
2. La valeur du stock restant
3. Le résultat comptable
*/