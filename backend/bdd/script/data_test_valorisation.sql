-- =============================================================================
-- DONNEES DE TEST POUR VALORISATION DE STOCK
-- Scenarios complets pour tester CMUP, FIFO, LIFO
-- =============================================================================

\c achat_vente_db;

-- -----------------------------------------------------------------------------
-- 0. NETTOYAGE DES DONNEES EXISTANTES (dans l'ordre des dependances)
-- -----------------------------------------------------------------------------

-- Desactiver temporairement les contraintes de cle etrangere
SET session_replication_role = replica;

-- Nettoyer les donnees dans l'ordre inverse des dependances
TRUNCATE TABLE paiement_achat CASCADE;
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

-- Nettoyer les tables de reference
TRUNCATE TABLE statut CASCADE;
TRUNCATE TABLE unite CASCADE;
TRUNCATE TABLE article_categorie CASCADE;
TRUNCATE TABLE personnel_role CASCADE;
TRUNCATE TABLE mode_paiement CASCADE;
TRUNCATE TABLE methode_valorisation_stock CASCADE;

-- Reactiver les contraintes
SET session_replication_role = DEFAULT;

-- Reinitialiser les sequences
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

SELECT 'Nettoyage termine - Base prete pour les donnees de test' as status;

-- -----------------------------------------------------------------------------
-- 1. DONNEES DE REFERENCE
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
('ELECTRONIQUE', 'Electronique'),
('INFORMATIQUE', 'Informatique'),
('MOBILIER', 'Mobilier'),
('CONSOMMABLE', 'Consommables'),
('MATERIEL', 'Materiel');

-- Roles personnel
INSERT INTO personnel_role (code, libelle, niveau_acces) VALUES 
('ADMIN', 'Administrateur', 5),
('GESTIONNAIRE', 'Gestionnaire de stock', 4),
('VENDEUR', 'Vendeur', 3),
('MAGASINIER', 'Magasinier', 2);

-- Modes de paiement
INSERT INTO mode_paiement (code, libelle) VALUES 
('ESPECES', 'Especes'),
('VIREMENT', 'Virement bancaire'),
('CHEQUE', 'Cheque'),
('CARTE', 'Carte bancaire');

-- Methodes de valorisation
INSERT INTO methode_valorisation_stock (code, libelle, description) VALUES 
('CMUP', 'Cout Moyen Unitaire Pondere', 'Valorisation au cout moyen pondere'),
('FIFO', 'Premier Entre Premier Sorti', 'Valorisation FIFO - First In First Out'),
('LIFO', 'Dernier Entre Premier Sorti', 'Valorisation LIFO - Last In First Out');

-- -----------------------------------------------------------------------------
-- 2. STRUCTURE ORGANISATIONNELLE
-- -----------------------------------------------------------------------------

-- Groupe
INSERT INTO groupe (nom, description) VALUES 
('TECH HOLDING GROUP', 'Groupe holding technologique');

-- Entreprises
INSERT INTO entreprise (nom, groupe_id, type_entreprise, matricule_fiscal, adresse, telephone, email) VALUES 
('TECH DISTRIB (Siege)', 1, 'INTERNE', 'TDS2024001', 'Ankorondrano, Antananarivo', '+261 20 22 123 45', 'contact@techdistrib.mg'),
('TECH DISTRIB Toamasina', 1, 'INTERNE', 'TDT2024001', 'Bord de Mer, Toamasina', '+261 53 33 123 45', 'toamasina@techdistrib.mg'),
('TECH DISTRIB Mahajanga', 1, 'INTERNE', 'TDM2024001', 'Centre Ville, Mahajanga', '+261 62 22 123 45', 'mahajanga@techdistrib.mg'),
('TECH DISTRIB Fianarantsoa', 1, 'INTERNE', 'TDF2024001', 'Haute Ville, Fianarantsoa', '+261 75 33 123 45', 'fianarantsoa@techdistrib.mg'),
('FOURNISSEUR ELECTRONICS', 1, 'FOURNISSEUR', 'FE2024001', 'Analakely, Antananarivo', '+261 20 22 567 89', 'vente@electronics.mg'),
('FOURNISSEUR INFORMATIQUE PLUS', 1, 'FOURNISSEUR', 'FIP2024001', 'Tsaralalana, Antananarivo', '+261 20 22 678 90', 'contact@infoplus.mg'),
('FOURNISSEUR GLOBAL TECH', 1, 'FOURNISSEUR', 'FGT2024001', 'Behoririka, Antananarivo', '+261 20 22 789 01', 'vente@globaltech.mg'),
('CLIENT ENTREPRISE A', 1, 'CLIENT', 'CEA2024001', 'Tsaralalana, Antananarivo', '+261 20 22 987 65', 'achat@entrepriseA.mg'),
('CLIENT ENTREPRISE B', 1, 'CLIENT', 'CEB2024001', 'Antsahavola, Antananarivo', '+261 20 22 876 54', 'achat@entrepriseB.mg'),
('CLIENT ENTREPRISE C', 1, 'CLIENT', 'CEC2024001', 'Ambohijatovo, Antananarivo', '+261 20 22 765 43', 'achat@entrepriseC.mg'),
('CLIENT ENTREPRISE D', 1, 'CLIENT', 'CED2024001', 'Andraharo, Antananarivo', '+261 20 22 654 32', 'achat@entrepriseD.mg'),
('CLIENT HOTEL GRAND', 1, 'CLIENT', 'CHG2024001', 'Ivandry, Antananarivo', '+261 20 22 543 21', 'achat@hotelgrand.mg');

-- Sites
INSERT INTO site (nom, adresse, telephone, entreprise_id) VALUES 
-- Sites TECH DISTRIB Antananarivo
('Siege Ankorondrano', 'Lot 123 Ankorondrano', '+261 20 22 123 45', 1),
('Succursale Analakely', 'Rue du Commerce Analakely', '+261 20 22 234 56', 1),
('Entrepot Anosizato', 'Zone Industrielle Anosizato', '+261 20 22 345 67', 1),
('Magasin Behoririka', 'Marche Behoririka', '+261 20 22 456 78', 1),
-- Sites TECH DISTRIB Toamasina
('Siege Toamasina', 'Boulevard Maritime', '+261 53 33 123 45', 2),
('Depot Toamasina Port', 'Zone Portuaire', '+261 53 33 234 56', 2),
('Magasin Toamasina Centre', 'Avenue de l Independence', '+261 53 33 345 67', 2),
-- Sites TECH DISTRIB Mahajanga
('Siege Mahajanga', 'Boulevard Poincare', '+261 62 22 123 45', 3),
('Depot Mahajanga Nord', 'Amborovy', '+261 62 22 234 56', 3),
-- Sites TECH DISTRIB Fianarantsoa
('Siege Fianarantsoa', 'Haute Ville', '+261 75 33 123 45', 4),
('Depot Fianarantsoa Sud', 'Zone Commerciale', '+261 75 33 234 56', 4);

-- Depots avec differentes methodes de valorisation
-- On peut definir une methode par depot (s'applique a tous les articles du depot)
-- OU definir une methode par (article + depot) dans la table stock
INSERT INTO depot (nom, adresse, site_id, methode_valorisation_stock_id) VALUES 
-- Depots Antananarivo
('Entrepot Central', 'Batiment A - Ankorondrano', 1, 1),  -- CMUP par defaut
('Depot Analakely', 'Magasin Analakely', 2, 2),          -- FIFO par defaut
('Stock Anosizato', 'Entrepot Anosizato', 3, 3),         -- LIFO par defaut
('Magasin Behoririka', 'Batiment B - Behoririka', 4, 1),  -- CMUP
-- Depots Toamasina
('Entrepot Toamasina Principal', 'Zone Industrielle Toamasina', 5, 1),  -- CMUP
('Depot Port Toamasina', 'Quai 3 - Port', 6, 2),  -- FIFO
('Magasin Toamasina Centre', 'Avenue Commerce', 7, 2),  -- FIFO
-- Depots Mahajanga
('Entrepot Mahajanga', 'Zone Industrielle Amborovy', 8, 3),  -- LIFO
('Depot Mahajanga Nord', 'Route Nationale 4', 9, 1),  -- CMUP
-- Depots Fianarantsoa
('Entrepot Fianarantsoa', 'Zone Commerciale Sud', 10, 2),  -- FIFO
('Depot Fianarantsoa Centre', 'Haute Ville', 11, 1);  -- CMUP

-- Personnel
INSERT INTO personnel (code_employe, nom, prenom, email, telephone, personnel_role_id, entreprise_id, site_defaut_id) VALUES 
-- Personnel Antananarivo
('ADM001', 'RAKOTO', 'Jean', 'j.rakoto@techdistrib.mg', '+261 34 12 345 67', 1, 1, 1),
('GES001', 'RABE', 'Marie', 'm.rabe@techdistrib.mg', '+261 34 23 456 78', 2, 1, 1),
('VEN001', 'RANDRIA', 'Paul', 'p.randria@techdistrib.mg', '+261 34 34 567 89', 3, 1, 2),
('MAG001', 'RAZANA', 'Sophie', 's.razana@techdistrib.mg', '+261 34 45 678 90', 4, 1, 3),
('VEN002', 'RASOLOFO', 'Luc', 'l.rasolofo@techdistrib.mg', '+261 34 56 789 01', 3, 1, 4),
-- Personnel Toamasina
('GES002', 'RANDRIANASOLO', 'Hery', 'h.randrianasolo@techdistrib.mg', '+261 32 11 222 33', 2, 2, 5),
('VEN003', 'RAKOTOMALALA', 'Fara', 'f.rakotomalala@techdistrib.mg', '+261 32 22 333 44', 3, 2, 7),
('MAG002', 'ANDRIAMAMPIONONA', 'Miora', 'm.andriamampionona@techdistrib.mg', '+261 32 33 444 55', 4, 2, 6),
-- Personnel Mahajanga
('GES003', 'RABARY', 'Serge', 's.rabary@techdistrib.mg', '+261 33 11 222 33', 2, 3, 8),
('VEN004', 'RAHARISON', 'Nadia', 'n.raharison@techdistrib.mg', '+261 33 22 333 44', 3, 3, 8),
('MAG003', 'RAFALIMANANA', 'Claude', 'c.rafalimanana@techdistrib.mg', '+261 33 33 444 55', 4, 3, 9),
-- Personnel Fianarantsoa
('GES004', 'RAKOTOZAFY', 'Andry', 'a.rakotozafy@techdistrib.mg', '+261 34 11 222 33', 2, 4, 10),
('VEN005', 'RAZAFIMAHATRATRA', 'Nina', 'n.razafimahatratra@techdistrib.mg', '+261 34 22 333 44', 3, 4, 11),
('MAG004', 'RAKOTOARISOA', 'Patrick', 'p.rakotoarisoa@techdistrib.mg', '+261 34 33 444 55', 4, 4, 10);

-- Articles pour les tests
INSERT INTO article (reference, designation, description, prix_achat_ref, prix_vente_ref, unite_id, article_categorie_id) VALUES 
('LAPTOP001', 'Ordinateur Portable HP', 'HP ProBook 450 G8 - i5, 8GB RAM, 256GB SSD', 2500000, 3200000, 1, 2),
('LAPTOP002', 'Ordinateur Portable Dell', 'Dell Latitude 3420 - i7, 16GB RAM, 512GB SSD', 3200000, 4100000, 1, 2),
('LAPTOP003', 'Ordinateur Portable Lenovo', 'Lenovo ThinkPad E15 - i5, 8GB RAM, 256GB SSD', 2400000, 3100000, 1, 2),
('MOUSE001', 'Souris Optique', 'Souris optique sans fil Logitech', 45000, 65000, 1, 2),
('MOUSE002', 'Souris Gaming', 'Souris Gaming RGB 7 boutons', 85000, 120000, 1, 2),
('CABLE001', 'Cable HDMI', 'Cable HDMI 2.0 - 2 metres', 25000, 35000, 1, 1),
('CABLE002', 'Cable USB-C', 'Cable USB-C 3.1 - 1 metre', 18000, 28000, 1, 1),
('PHONE001', 'Smartphone Samsung', 'Galaxy A54 5G - 128GB', 1800000, 2300000, 1, 1),
('PHONE002', 'Smartphone iPhone', 'iPhone 13 - 128GB', 3500000, 4200000, 1, 1),
('KEYBOARD001', 'Clavier Mecanique', 'Clavier Gaming RGB', 180000, 250000, 1, 2),
('KEYBOARD002', 'Clavier Sans Fil', 'Clavier Bluetooth slim', 95000, 135000, 1, 2),
('MONITOR001', 'Ecran 24 pouces', 'Moniteur Full HD 24\" LED', 650000, 850000, 1, 2),
('MONITOR002', 'Ecran 27 pouces', 'Moniteur 2K 27\" IPS', 980000, 1250000, 1, 2),
('PRINTER001', 'Imprimante Laser', 'Imprimante Laser noir et blanc', 850000, 1100000, 1, 2),
('TABLET001', 'Tablette Samsung', 'Galaxy Tab A8 - 64GB', 950000, 1250000, 1, 1);

-- -----------------------------------------------------------------------------
-- SCENARIO TEST VALORISATION - LAPTOP001
-- -----------------------------------------------------------------------------

-- Stock initial avec methodes differentes par depot
INSERT INTO stock (article_id, depot_id, methode_valorisation_stock_id, quantite_actuelle, cmup_actuel, valeur_stock_total) VALUES 
(1, 1, 1, 0, 0, 0),  -- LAPTOP001 - Depot 1 - CMUP
(1, 2, 2, 0, 0, 0),  -- LAPTOP001 - Depot 2 - FIFO  
(1, 3, 3, 0, 0, 0);  -- LAPTOP001 - Depot 3 - LIFO

-- Caisses
INSERT INTO caisse (code_caisse, libelle, solde_actuel, entreprise_id) VALUES 
('CAISSE01', 'Caisse principale Antananarivo', 50000000, 1),
('CAISSE02', 'Caisse Analakely', 15000000, 1),
('CAISSE03', 'Caisse Behoririka', 8000000, 1),
('CAISSE_TM01', 'Caisse principale Toamasina', 25000000, 2),
('CAISSE_MJ01', 'Caisse principale Mahajanga', 18000000, 3),
('CAISSE_FN01', 'Caisse principale Fianarantsoa', 12000000, 4);

-- Demandes d'achat pour créer des entrées de stock
INSERT INTO proforma_demande_achat (numero_da, date_demande, personnel_demandeur_id, entreprise_id, depot_cible_id, motif_achat, statut_id) VALUES 
('DA2024001', '2024-01-15', 2, 1, 1, 'Reconstitution stock laptops', 2),
('DA2024002', '2024-02-10', 2, 1, 1, 'Commande supplementaire laptops', 2),
('DA2024003', '2024-03-05', 2, 1, 1, 'Stock de securite laptops', 2);

-- Détails demandes d'achat
INSERT INTO proforma_demande_achat_details (proforma_demande_achat_id, article_id, quantite_demandee, prix_estime) VALUES 
(1, 1, 10, 2400000),
(2, 1, 15, 2600000),
(3, 1, 8, 2450000);

-- Proformas fournisseur
INSERT INTO proforma_fournisseur (numero_proforma, date_emission, date_validite, entreprise_fournisseur_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, proforma_demande_achat_id) VALUES 
('PRO2024001', '2024-01-16', '2024-02-15', 5, 1, 2, 2, 24000000, 1),
('PRO2024002', '2024-02-11', '2024-03-12', 5, 1, 2, 2, 39000000, 2),
('PRO2024003', '2024-03-06', '2024-04-05', 5, 1, 2, 2, 19600000, 3);

-- Détails proformas
INSERT INTO proforma_fournisseur_details (proforma_fournisseur_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 10, 2400000),
(2, 1, 15, 2600000),
(3, 1, 8, 2450000);

-- Bons de commande
INSERT INTO bon_commande_achat (numero_bc, date_commande, proforma_fournisseur_id, entreprise_fournisseur_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, depot_livraison_id) VALUES 
('BC2024001', '2024-01-20', 1, 5, 1, 2, 4, 24000000, 1),
('BC2024002', '2024-02-15', 2, 5, 1, 2, 4, 39000000, 1),
('BC2024003', '2024-03-10', 3, 5, 1, 2, 4, 19600000, 1);

-- Détails bons de commande
INSERT INTO bon_commande_achat_details (bon_commande_achat_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 10, 2400000),
(2, 1, 15, 2600000),
(3, 1, 8, 2450000);

-- Factures d'achat (réceptions)
INSERT INTO facture_achat (numero_facture_fournisseur, date_facture, bon_commande_achat_id, entreprise_fournisseur_id, entreprise_filiale_id, statut_id, montant_ttc, reste_a_payer, depot_reception_id) VALUES 
('FA2024001', '2024-01-25', 1, 5, 1, 5, 24000000, 24000000, 1),
('FA2024002', '2024-02-20', 2, 5, 1, 5, 39000000, 39000000, 1),
('FA2024003', '2024-03-15', 3, 5, 1, 5, 19600000, 19600000, 1);

-- Détails factures d'achat
INSERT INTO facture_achat_details (facture_achat_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 10, 2400000),
(2, 1, 15, 2600000),
(3, 1, 8, 2450000);

-- -----------------------------------------------------------------------------
-- MOUVEMENTS DE STOCK - ENTREES A DIFFERENTES DATES ET PRIX
-- -----------------------------------------------------------------------------

-- Premiere entree - 25 janvier 2024 - 10 laptops a 2,400,000 Ar
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-01-25 10:00:00', 'ENTREE_ACHAT', 0, 10, 10, 2400000, 1, 1, 4, 'FA2024001');

-- Deuxieme entree - 20 fevrier 2024 - 15 laptops a 2,600,000 Ar  
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-02-20 14:30:00', 'ENTREE_ACHAT', 10, 15, 25, 2600000, 1, 1, 4, 'FA2024002');

-- Troisieme entree - 15 mars 2024 - 8 laptops a 2,450,000 Ar
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-03-15 09:15:00', 'ENTREE_ACHAT', 25, 8, 33, 2450000, 1, 1, 4, 'FA2024003');

-- Dupliquer les mouvements pour les autres depots (FIFO et LIFO)
-- Depot 2 (FIFO)
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-01-25 10:00:00', 'ENTREE_ACHAT', 0, 10, 10, 2400000, 1, 2, 4, 'FA2024001-D2'),
('2024-02-20 14:30:00', 'ENTREE_ACHAT', 10, 15, 25, 2600000, 1, 2, 4, 'FA2024002-D2'),
('2024-03-15 09:15:00', 'ENTREE_ACHAT', 25, 8, 33, 2450000, 1, 2, 4, 'FA2024003-D2');

-- Depot 3 (LIFO)  
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-01-25 10:00:00', 'ENTREE_ACHAT', 0, 10, 10, 2400000, 1, 3, 4, 'FA2024001-D3'),
('2024-02-20 14:30:00', 'ENTREE_ACHAT', 10, 15, 25, 2600000, 1, 3, 4, 'FA2024002-D3'),
('2024-03-15 09:15:00', 'ENTREE_ACHAT', 25, 8, 33, 2450000, 1, 3, 4, 'FA2024003-D3');

-- -----------------------------------------------------------------------------
-- VENTES - Les sorties de stock seront gerees par les triggers
-- Les triggers calculeront automatiquement CMUP, FIFO, LIFO
-- -----------------------------------------------------------------------------

-- NOTE IMPORTANTE : Ne PAS inserer manuellement les mouvements de sortie
-- Les triggers se chargeront de :
-- 1. Calculer le cout selon la methode (CMUP/FIFO/LIFO)
-- 2. Creer les mouvements de stock
-- 3. Mettre a jour les lots (pour FIFO/LIFO)
-- 4. Mettre a jour les quantites et valeurs de stock

-- Les ventes ci-dessous declencheront automatiquement les sorties de stock
-- avec les bonnes valorisations selon chaque depot

-- Devis de vente
INSERT INTO devis_vente (numero_devis, date_devis, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc) VALUES 
('DV2024001', '2024-04-01', 8, 1, 3, 2, 96000000);

INSERT INTO devis_vente_details (devis_vente_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 3, 3200000);

-- Bon de commande vente
INSERT INTO bon_commande_vente (numero_bc, date_commande, devis_vente_id, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, depot_expedition_id) VALUES 
('BCV2024001', '2024-04-05', 1, 8, 1, 3, 4, 96000000, 1);

INSERT INTO bon_commande_vente_details (bon_commande_vente_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 3, 3200000);

-- Facture de vente
INSERT INTO facture_vente (numero_facture, date_facture, bon_commande_vente_id, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, reste_a_payer, depot_expedition_id) VALUES 
('FV2024001', '2024-04-10', 1, 8, 1, 3, 5, 96000000, 96000000, 1);

INSERT INTO facture_vente_details (facture_vente_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 3, 3200000);

-- -----------------------------------------------------------------------------
-- Les sorties de stock seront gerees automatiquement par les triggers
-- lors de la validation des factures de vente
-- -----------------------------------------------------------------------------

-- -----------------------------------------------------------------------------
-- 11. DONNÉES SUPPLÉMENTAIRES POUR AUTRES ARTICLES
-- -----------------------------------------------------------------------------

-- Stock pour MOUSE001 avec des prix très variables
INSERT INTO stock (article_id, depot_id, methode_valorisation_stock_id, quantite_actuelle, cmup_actuel, valeur_stock_total) VALUES 
(4, 1, 1, 0, 0, 0);

-- Entrées de souris à des prix différents
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2024-01-10 08:00:00', 'ENTREE_ACHAT', 0, 50, 50, 40000, 4, 1, 4, 'MOUSE-LOT1'),
('2024-02-15 10:00:00', 'ENTREE_ACHAT', 50, 30, 80, 48000, 4, 1, 4, 'MOUSE-LOT2'),
('2024-03-20 14:00:00', 'ENTREE_ACHAT', 80, 20, 100, 52000, 4, 1, 4, 'MOUSE-LOT3');

-- -----------------------------------------------------------------------------
-- REQUETES DE VERIFICATION
-- Les lots et valorisations seront geres automatiquement par les triggers
-- -----------------------------------------------------------------------------

-- Supprimer les vues existantes si elles existent
DROP VIEW IF EXISTS v_stock_valorisation;
DROP VIEW IF EXISTS v_lots_detail;
DROP VIEW IF EXISTS v_mouvements_stock;

-- Vue pour vérifier les valorisations (sans valeurs négatives)
CREATE VIEW v_stock_valorisation AS
SELECT 
    s.article_id,
    a.reference,
    a.designation,
    d.nom as depot,
    mvs.code as methode,
    CASE 
        WHEN s.quantite_actuelle < 0 THEN 0::numeric(15,2)
        ELSE s.quantite_actuelle
    END as quantite_actuelle,
    CASE 
        WHEN s.cmup_actuel < 0 THEN 0::numeric(15,2)
        ELSE s.cmup_actuel
    END as cmup_actuel,
    CASE 
        WHEN s.valeur_stock_total < 0 THEN 0::numeric(15,2)
        ELSE s.valeur_stock_total
    END as valeur_stock_total,
    CASE 
        WHEN s.quantite_actuelle > 0 AND s.valeur_stock_total >= 0 
        THEN ROUND(s.valeur_stock_total / s.quantite_actuelle, 2)::numeric(15,2)
        ELSE 0::numeric(15,2)
    END as prix_unitaire_moyen
FROM stock s
JOIN article a ON s.article_id = a.id
JOIN depot d ON s.depot_id = d.id
JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id;
CASE 
        WHEN ls.quantite_initiale < 0 THEN 0::numeric(15,2)
        ELSE ls.quantite_initiale
    END as quantite_initiale,
    CASE 
        WHEN ls.quantite_restante < 0 THEN 0::numeric(15,2)
        ELSE ls.quantite_restante
    END as quantite_restante,
    CASE 
        WHEN ls.prix_unitaire_achat < 0 THEN 0::numeric(15,2)
        ELSE ls.prix_unitaire_achat
    END as prix_unitaire_achat,
    CASE 
        WHEN (ls.prix_unitaire_achat * ls.quantite_restante) < 0 THEN 0::numeric(15,2)
        ELSE (ls.prix_unitaire_achat * ls.quantite_restante)::numeric(15,2)
    END as valeur_lot,
    ls.statut
FROM lot_stock ls
JOIN article a ON ls.article_id = a.id
JOIN depot d ON ls.depot_id = d.id
    GREATEST(ls.quantite_initiale, 0)::numeric(15,2) as quantite_initiale,
    GREATEST(ls.quantite_restante, 0)::numeric(15,2) as quantite_restante,
    GREATEST(ls.prix_unitaire_achat, 0)::numeric(15,2) as prix_unitaire_achat,
    GREATEST(ls.prix_unitaire_achat * ls.quantite_restante, 0)::numeric(15,2) as valeur_lot,
    ls.statut
FROM lot_stock ls
JOIN article a ON ls.article_id = a.id
JOINCASE 
        WHEN COALESCE(ms.quantite_entree, 0) < 0 THEN 0::numeric(15,2)
        ELSE COALESCE(ms.quantite_entree, 0)::numeric(15,2)
    END as quantite_entree,
    CASE 
        WHEN COALESCE(ms.quantite_sortie, 0) < 0 THEN 0::numeric(15,2)
        ELSE COALESCE(ms.quantite_sortie, 0)::numeric(15,2)
    END as quantite_sortie,
    CASE 
        WHEN ms.prix_unitaire_mouvement < 0 THEN 0::numeric(15,2)
        ELSE ms.prix_unitaire_mouvement::numeric(15,2)
    END as prix_unitaire_mouvement,
    CASE 
        WHEN ms.quantite_stock_apres < 0 THEN 0::numeric(15,2)
        ELSE ms.quantite_stock_apres::numeric(15,2)
    END as quantite_stock_apres,
    ms.ref- État du stock AVANT toute sortie (33 pièces dans chaque dépôt) :

/*DÉPÔT 1 (CMUP - Entrepot Central) :
- Lot 1 : 10 × 2,400,000 = 24,000,000 Ar
- Lot 2 : 15 × 2,600,000 = 39,000,000 Ar
- Lot 3 : 8 × 2,450,000 = 19,600,000 Ar
- Total : 33 pièces = 82,600,000 Ar
- CMUP = 82,600,000 / 33 = 2,503,030.30 Ar/pièce

DÉPÔT 2 (FIFO - Depot Analakely) :
- Lot 1 : 10 × 2,400,000 = 24,000,000 Ar (1er entré)
- Lot 2 : 15 × 2,600,000 = 39,000,000 Ar (2ème entré)
- Lot 3 : 8 × 2,450,000 = 19,600,000 Ar (3ème entré)
- Total : 33 pièces = 82,600,000 Ar

DÉPÔT 3 (LIFO - Stock Anosizato) :
- Lot 1 : 10 × 2,400,000 = 24,000,000 Ar (1er entré)
- Lot 2 : 15 × 2,600,000 = 39,000,000 Ar (2ème entré)
- Lot 3 : 8 × 2,450,000 = 19,600,000 Ar (dernier entré)
- Total : 33 pièces = 82,600,000 Ar

Si vous effectuez une sortie de 3 pièces, les triggers calculeront :

DÉPÔT 1 (CMUP) - Après sortie de 3 pièces :
- Coût de sortie : 3 × 2,503,030.30 = 7,509,090.90 Ar
- Stock restant : 30 pièces × 2,503,030.30 = 75,090,909.00 Ar

DÉPÔT 2 (FIFO) - Après sortie de 3 pièces :
- Sortie depuis Lot 1 (plus ancien) : 3 × 2,400,000 = 7,200,000 Ar
- Lots restants : 
  * Lot 1 : 7 × 2,400,000 = 16,800,000 Ar
  * Lot 2 : 15 × 2,600,000 = 39,000,000 Ar
  * Lot 3 : 8 × 2,450,000 = 19,600,000 Ar
- Total : 30 pièces = 75,400,000 Ar

DÉPÔT 3 (LIFO) - Après sortie de 3 pièces :
- Sortie depuis Lot 3 (plus récent) : 3 × 2,450,000 = 7,350,000 Ar
- Lots restants :
  * Lot 1 : 10 × 2,400,000 = 24,000,000 Ar
  * Lot 2 : 15 × 2,600,000 = 39,000,000 Ar
  * Lot 3 : 5 × 2,450,000 = 12,250,000 Ar
- Total : 30 pièces = 75,250,000 Ar

Ces différences montrent l'impact des méthodes de valorisation sur :
1. Le coût des marchandises vendues (CMV)
2. La valeur du stock restant
3. Le résultat comptable
4. La marge brute

IMPORTANT : Toutes les valeurs doivent être POSITIVES========================================================
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