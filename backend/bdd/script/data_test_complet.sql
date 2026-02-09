-- =============================================================================
-- DONNEES DE TEST COMPLETES POUR TESTS APPLICATION
-- Scenarios multiples pour tester stock, achats, ventes, valorisation
-- =============================================================================

\c achat_vente_db;

-- -----------------------------------------------------------------------------
-- 0. NETTOYAGE DES DONNEES EXISTANTES (dans l'ordre des dependances)
-- -----------------------------------------------------------------------------

SET session_replication_role = replica;

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
TRUNCATE TABLE statut CASCADE;
TRUNCATE TABLE unite CASCADE;
TRUNCATE TABLE article_categorie CASCADE;
TRUNCATE TABLE personnel_role CASCADE;
TRUNCATE TABLE mode_paiement CASCADE;
TRUNCATE TABLE methode_valorisation_stock CASCADE;

SET session_replication_role = DEFAULT;

-- Reset sequences
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
ALTER SEQUENCE IF EXISTS caisse_mouvement_id_seq RESTART WITH 1;
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
ALTER SEQUENCE IF EXISTS paiement_vente_id_seq RESTART WITH 1;
ALTER SEQUENCE IF EXISTS paiement_achat_id_seq RESTART WITH 1;

SELECT 'Nettoyage termine' as status;

-- =============================================================================
-- 1. DONNEES DE REFERENCE
-- =============================================================================

-- Statuts (ORDRE IMPORTANT: id fixe)
INSERT INTO statut (id, code, libelle, niveau) VALUES 
(1, 'BROUILLON', 'Brouillon', 0),
(2, 'EN_ATTENTE', 'En attente de validation', 1),
(3, 'VALIDE', 'Valide / Confirme', 2),
(4, 'LIVRE', 'Livre / Receptionne', 3),
(5, 'PAYE', 'Paye integralement', 4),
(6, 'PARTIEL', 'Paye partiellement', 3),
(7, 'ANNULE', 'Annule', 99);

-- Unites
INSERT INTO unite (id, code, libelle) VALUES 
(1, 'U', 'Unite'),
(2, 'KG', 'Kilogramme'),
(3, 'L', 'Litre'),
(4, 'M', 'Metre'),
(5, 'M2', 'Metre carre'),
(6, 'PCE', 'Piece');

-- Categories d'articles
INSERT INTO article_categorie (id, code, libelle) VALUES 
(1, 'INFORMATIQUE', 'Materiel Informatique'),
(2, 'ELECTRONIQUE', 'Electronique'),
(3, 'MOBILIER', 'Mobilier de Bureau'),
(4, 'CONSOMMABLE', 'Consommables'),
(5, 'RESEAU', 'Equipement Reseau');

-- Roles personnel
INSERT INTO personnel_role (id, code, libelle, niveau_acces) VALUES 
(1, 'ADMIN', 'Administrateur', 5),
(2, 'GESTIONNAIRE', 'Gestionnaire', 4),
(3, 'COMMERCIAL', 'Commercial', 3),
(4, 'MAGASINIER', 'Magasinier', 2),
(5, 'CAISSIER', 'Caissier', 2);

-- Modes de paiement
INSERT INTO mode_paiement (id, code, libelle) VALUES 
(1, 'ESPECES', 'Especes'),
(2, 'VIREMENT', 'Virement bancaire'),
(3, 'CHEQUE', 'Cheque'),
(4, 'CARTE', 'Carte bancaire'),
(5, 'MOBILE', 'Mobile Money');

-- Methodes de valorisation stock
INSERT INTO methode_valorisation_stock (id, code, libelle, description) VALUES 
(1, 'CMUP', 'Cout Moyen Unitaire Pondere', 'Calcul du cout moyen a chaque entree'),
(2, 'FIFO', 'Premier Entre Premier Sorti', 'Les plus anciens lots sortent en premier'),
(3, 'LIFO', 'Dernier Entre Premier Sorti', 'Les lots les plus recents sortent en premier');

-- =============================================================================
-- 2. STRUCTURE ORGANISATIONNELLE
-- =============================================================================

-- Groupe
INSERT INTO groupe (id, nom, description) VALUES 
(1, 'TECH DISTRIBUTION GROUP', 'Groupe de distribution informatique et electronique');

-- Entreprises (INTERNES = nos filiales, FOURNISSEUR, CLIENT)
INSERT INTO entreprise (id, nom, groupe_id, type_entreprise, matricule_fiscal, adresse, telephone, email) VALUES 
-- Filiales internes
(1, 'TECH DISTRIB Tana', 1, 'INTERNE', 'TDT2025001', 'Ankorondrano, Antananarivo', '+261 20 22 111 11', 'tana@techdistrib.mg'),
(2, 'TECH DISTRIB Tamatave', 1, 'INTERNE', 'TDV2025001', 'Bord de Mer, Toamasina', '+261 20 53 222 22', 'tamatave@techdistrib.mg'),
(3, 'TECH DISTRIB Diego', 1, 'INTERNE', 'TDD2025001', 'Centre, Diego Suarez', '+261 20 82 333 33', 'diego@techdistrib.mg'),
-- Fournisseurs
(4, 'CHINA ELECTRONICS', 1, 'FOURNISSEUR', 'CE2025001', 'Guangzhou, Chine', '+86 20 8888 8888', 'export@chinaelec.cn'),
(5, 'DUBAI TECH IMPORT', 1, 'FOURNISSEUR', 'DTI2025001', 'Dubai, UAE', '+971 4 123 4567', 'sales@dubaitech.ae'),
(6, 'LOCAL DISTRIB', 1, 'FOURNISSEUR', 'LD2025001', 'Behoririka, Antananarivo', '+261 20 22 444 44', 'vente@localdistrib.mg'),
-- Clients
(7, 'BANQUE OF AFRICA', 1, 'CLIENT', 'BOA2025001', 'Antsahavola, Antananarivo', '+261 20 22 555 55', 'it@boa.mg'),
(8, 'AIRTEL MADAGASCAR', 1, 'CLIENT', 'AIR2025001', 'Andraharo, Antananarivo', '+261 34 666 66 66', 'achat@airtel.mg'),
(9, 'JIRAMA', 1, 'CLIENT', 'JIR2025001', 'Ampasika, Antananarivo', '+261 20 22 777 77', 'logistique@jirama.mg'),
(10, 'HOTEL CARLTON', 1, 'CLIENT', 'HC2025001', 'Anosy, Antananarivo', '+261 20 22 888 88', 'achat@carlton.mg'),
(11, 'UNIVERSITE ANTANANARIVO', 1, 'CLIENT', 'UA2025001', 'Ankatso, Antananarivo', '+261 20 22 999 99', 'it@univ-antananarivo.mg'),
(12, 'SOCIETE GENERALE MG', 1, 'CLIENT', 'SGM2025001', 'Antaninarenina, Antananarivo', '+261 20 22 000 00', 'achat@socgen.mg');

-- Sites (plusieurs par filiale)
INSERT INTO site (id, nom, adresse, telephone, entreprise_id) VALUES 
-- Sites Tana
(1, 'Siege Ankorondrano', 'Immeuble TECNO, Ankorondrano', '+261 20 22 111 11', 1),
(2, 'Magasin Analakely', '5 Rue du Commerce, Analakely', '+261 20 22 111 22', 1),
(3, 'Entrepot Anosizato', 'Zone Industrielle, Anosizato', '+261 20 22 111 33', 1),
-- Sites Tamatave
(4, 'Siege Tamatave', 'Boulevard Joffre', '+261 20 53 222 11', 2),
(5, 'Depot Port', 'Zone Portuaire', '+261 20 53 222 22', 2),
-- Sites Diego
(6, 'Siege Diego', 'Rue Colbert', '+261 20 82 333 11', 3),
(7, 'Depot Diego', 'Zone Commerciale', '+261 20 82 333 22', 3);

-- Depots (avec methodes de valorisation differentes)
INSERT INTO depot (id, nom, adresse, site_id, methode_valorisation_stock_id) VALUES 
-- Depots Tana
(1, 'Depot Central Tana', 'Batiment A, Ankorondrano', 1, 1),      -- CMUP
(2, 'Depot Retail Analakely', 'Magasin Analakely', 2, 2),          -- FIFO
(3, 'Entrepot Principal Anosizato', 'Zone Industrielle', 3, 3),    -- LIFO
(4, 'Reserve Siege', 'Batiment B, Ankorondrano', 1, 1),            -- CMUP
-- Depots Tamatave
(5, 'Depot Tamatave Centre', 'Boulevard Joffre', 4, 1),            -- CMUP
(6, 'Depot Tamatave Port', 'Zone Portuaire', 5, 2),                -- FIFO
-- Depots Diego
(7, 'Depot Diego Principal', 'Rue Colbert', 6, 1),                 -- CMUP
(8, 'Reserve Diego', 'Zone Commerciale', 7, 3);                    -- LIFO

-- Personnel
INSERT INTO personnel (id, code_employe, nom, prenom, email, telephone, personnel_role_id, entreprise_id, site_defaut_id) VALUES 
-- Personnel Tana
(1, 'ADM001', 'RAKOTO', 'Jean', 'j.rakoto@techdistrib.mg', '+261 34 01 111 11', 1, 1, 1),
(2, 'GES001', 'RABE', 'Marie', 'm.rabe@techdistrib.mg', '+261 34 01 222 22', 2, 1, 1),
(3, 'COM001', 'RANDRIA', 'Paul', 'p.randria@techdistrib.mg', '+261 34 01 333 33', 3, 1, 2),
(4, 'MAG001', 'RAZANA', 'Sophie', 's.razana@techdistrib.mg', '+261 34 01 444 44', 4, 1, 3),
(5, 'COM002', 'RASOLO', 'Luc', 'l.rasolo@techdistrib.mg', '+261 34 01 555 55', 3, 1, 1),
(6, 'CAI001', 'RAVELO', 'Nina', 'n.ravelo@techdistrib.mg', '+261 34 01 666 66', 5, 1, 1),
-- Personnel Tamatave
(7, 'GES002', 'ANDRIA', 'Hery', 'h.andria@techdistrib.mg', '+261 33 02 111 11', 2, 2, 4),
(8, 'COM003', 'RALAIVAO', 'Fara', 'f.ralaivao@techdistrib.mg', '+261 33 02 222 22', 3, 2, 4),
(9, 'MAG002', 'RABENJA', 'Tojo', 't.rabenja@techdistrib.mg', '+261 33 02 333 33', 4, 2, 5),
-- Personnel Diego
(10, 'GES003', 'RAMANA', 'Serge', 's.ramana@techdistrib.mg', '+261 32 03 111 11', 2, 3, 6),
(11, 'COM004', 'RATIANA', 'Vola', 'v.ratiana@techdistrib.mg', '+261 32 03 222 22', 3, 3, 6);

-- =============================================================================
-- 3. ARTICLES (variete de produits)
-- =============================================================================

INSERT INTO article (id, reference, designation, description, prix_achat_ref, prix_vente_ref, unite_id, article_categorie_id) VALUES 
-- Informatique
(1, 'LAPTOP-HP-01', 'Laptop HP ProBook 450', 'Intel i5, 8GB RAM, 256GB SSD', 2500000, 3200000, 1, 1),
(2, 'LAPTOP-DELL-01', 'Laptop Dell Latitude 3420', 'Intel i7, 16GB RAM, 512GB SSD', 3200000, 4100000, 1, 1),
(3, 'LAPTOP-LEN-01', 'Laptop Lenovo ThinkPad E15', 'Intel i5, 8GB RAM, 256GB SSD', 2400000, 3000000, 1, 1),
(4, 'PC-HP-01', 'PC Bureau HP ProDesk', 'Intel i5, 8GB RAM, 500GB HDD', 1800000, 2300000, 1, 1),
(5, 'ECRAN-24', 'Ecran 24 pouces Full HD', 'LED, 1920x1080, HDMI/VGA', 650000, 850000, 1, 1),
(6, 'ECRAN-27', 'Ecran 27 pouces 2K', 'IPS, 2560x1440, USB-C', 980000, 1300000, 1, 1),
-- Peripheriques
(7, 'SOURIS-LOG-01', 'Souris Logitech M185', 'Sans fil, USB', 45000, 65000, 1, 1),
(8, 'SOURIS-GAM-01', 'Souris Gaming RGB', '7 boutons, 12000 DPI', 85000, 125000, 1, 1),
(9, 'CLAVIER-STD', 'Clavier USB Standard', 'AZERTY, USB', 35000, 55000, 1, 1),
(10, 'CLAVIER-MECA', 'Clavier Mecanique RGB', 'Switches Red, USB', 180000, 260000, 1, 1),
-- Electronique
(11, 'PHONE-SAM-01', 'Samsung Galaxy A54', '128GB, 5G', 1800000, 2300000, 1, 2),
(12, 'PHONE-IPH-01', 'iPhone 14 128GB', '128GB, 5G', 3800000, 4500000, 1, 2),
(13, 'TABLET-SAM-01', 'Samsung Tab A8', '64GB, WiFi', 950000, 1250000, 1, 2),
-- Reseau
(14, 'SWITCH-8P', 'Switch 8 ports Gigabit', 'TP-Link TL-SG108', 120000, 180000, 1, 5),
(15, 'ROUTER-WIFI', 'Routeur WiFi 6 AX3000', 'TP-Link Archer AX50', 280000, 380000, 1, 5),
(16, 'CABLE-RJ45-5M', 'Cable RJ45 Cat6 5m', 'UTP, Cat6', 15000, 25000, 1, 5),
-- Consommables
(17, 'TONER-HP-85A', 'Toner HP 85A', 'Compatible LaserJet P1102', 85000, 120000, 1, 4),
(18, 'ENCRE-CANON', 'Encre Canon GI-490', 'Pack 4 couleurs', 95000, 140000, 1, 4),
(19, 'PAPIER-A4', 'Ramette Papier A4 80g', '500 feuilles', 18000, 25000, 6, 4),
(20, 'CLE-USB-32', 'Cle USB 32GB', 'USB 3.0, SanDisk', 35000, 55000, 1, 4);

-- =============================================================================
-- 4. CAISSES
-- =============================================================================

INSERT INTO caisse (id, code_caisse, libelle, solde_actuel, entreprise_id) VALUES 
(1, 'CAISSE-TANA-01', 'Caisse Principale Tana', 50000000, 1),
(2, 'CAISSE-TANA-02', 'Caisse Analakely', 15000000, 1),
(3, 'CAISSE-TMV-01', 'Caisse Tamatave', 25000000, 2),
(4, 'CAISSE-DGO-01', 'Caisse Diego', 18000000, 3);

-- =============================================================================
-- 5. STOCK INITIAL ET MOUVEMENTS D'ENTREE (via triggers)
-- =============================================================================

-- Stock initial vide pour tous les articles dans differents depots
INSERT INTO stock (article_id, depot_id, methode_valorisation_stock_id, quantite_actuelle, cmup_actuel, valeur_stock_total) VALUES 
-- Depot 1 (CMUP) - Tana Central
(1, 1, 1, 0, 0, 0), (2, 1, 1, 0, 0, 0), (3, 1, 1, 0, 0, 0), (4, 1, 1, 0, 0, 0),
(5, 1, 1, 0, 0, 0), (6, 1, 1, 0, 0, 0), (7, 1, 1, 0, 0, 0), (8, 1, 1, 0, 0, 0),
(9, 1, 1, 0, 0, 0), (10, 1, 1, 0, 0, 0), (11, 1, 1, 0, 0, 0), (12, 1, 1, 0, 0, 0),
(13, 1, 1, 0, 0, 0), (14, 1, 1, 0, 0, 0), (15, 1, 1, 0, 0, 0), (16, 1, 1, 0, 0, 0),
(17, 1, 1, 0, 0, 0), (18, 1, 1, 0, 0, 0), (19, 1, 1, 0, 0, 0), (20, 1, 1, 0, 0, 0),
-- Depot 2 (FIFO) - Analakely (pas de CMUP pour FIFO)
(1, 2, 2, 0, NULL, NULL), (5, 2, 2, 0, NULL, NULL), (7, 2, 2, 0, NULL, NULL), (9, 2, 2, 0, NULL, NULL),
(11, 2, 2, 0, NULL, NULL), (14, 2, 2, 0, NULL, NULL), (17, 2, 2, 0, NULL, NULL), (19, 2, 2, 0, NULL, NULL),
-- Depot 3 (LIFO) - Anosizato (pas de CMUP pour LIFO)
(1, 3, 3, 0, NULL, NULL), (2, 3, 3, 0, NULL, NULL), (4, 3, 3, 0, NULL, NULL), (5, 3, 3, 0, NULL, NULL),
(7, 3, 3, 0, NULL, NULL), (14, 3, 3, 0, NULL, NULL), (16, 3, 3, 0, NULL, NULL), (19, 3, 3, 0, NULL, NULL),
-- Depot 5 (CMUP) - Tamatave
(1, 5, 1, 0, 0, 0), (5, 5, 1, 0, 0, 0), (7, 5, 1, 0, 0, 0), (11, 5, 1, 0, 0, 0),
(14, 5, 1, 0, 0, 0), (17, 5, 1, 0, 0, 0), (19, 5, 1, 0, 0, 0),
-- Depot 7 (CMUP) - Diego
(1, 7, 1, 0, 0, 0), (5, 7, 1, 0, 0, 0), (7, 7, 1, 0, 0, 0), (11, 7, 1, 0, 0, 0);

-- =============================================================================
-- MOUVEMENTS D'ENTREE (vont declencher les triggers de valorisation)
-- =============================================================================

-- ========== DEPOT 1 - CMUP ==========
-- Laptops HP (article 1) - 3 entrees a prix differents
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2025-01-15 09:00:00', 'ACHAT', 0, 20, 20, 2400000, 1, 1, 4, 'FA-2025-001'),
('2025-02-10 10:00:00', 'ACHAT', 20, 15, 35, 2550000, 1, 1, 4, 'FA-2025-015'),
('2025-03-05 11:00:00', 'ACHAT', 35, 10, 45, 2600000, 1, 1, 4, 'FA-2025-028');

-- Laptops Dell (article 2)
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2025-01-20 09:00:00', 'ACHAT', 0, 10, 10, 3100000, 2, 1, 4, 'FA-2025-003'),
('2025-02-15 10:00:00', 'ACHAT', 10, 8, 18, 3250000, 2, 1, 4, 'FA-2025-018');

-- Ecrans 24 pouces (article 5)
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2025-01-10 09:00:00', 'ACHAT', 0, 50, 50, 620000, 5, 1, 4, 'FA-2025-002'),
('2025-02-20 10:00:00', 'ACHAT', 50, 30, 80, 650000, 5, 1, 4, 'FA-2025-020');

-- Souris (article 7) - stock abondant
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2025-01-05 09:00:00', 'ACHAT', 0, 200, 200, 42000, 7, 1, 4, 'FA-2025-004'),
('2025-02-01 10:00:00', 'ACHAT', 200, 150, 350, 44000, 7, 1, 4, 'FA-2025-010'),
('2025-03-01 11:00:00', 'ACHAT', 350, 100, 450, 46000, 7, 1, 4, 'FA-2025-025');

-- Smartphones Samsung (article 11)
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2025-01-25 09:00:00', 'ACHAT', 0, 30, 30, 1750000, 11, 1, 4, 'FA-2025-006'),
('2025-02-25 10:00:00', 'ACHAT', 30, 20, 50, 1800000, 11, 1, 4, 'FA-2025-022');

-- Autres articles depot 1
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2025-01-10 09:00:00', 'ACHAT', 0, 15, 15, 2350000, 3, 1, 4, 'FA-2025-007'),  -- Lenovo
('2025-01-12 09:00:00', 'ACHAT', 0, 20, 20, 1750000, 4, 1, 4, 'FA-2025-008'),  -- PC Bureau
('2025-01-14 09:00:00', 'ACHAT', 0, 25, 25, 950000, 6, 1, 4, 'FA-2025-009'),   -- Ecran 27
('2025-01-16 09:00:00', 'ACHAT', 0, 100, 100, 80000, 8, 1, 4, 'FA-2025-011'),  -- Souris Gaming
('2025-01-18 09:00:00', 'ACHAT', 0, 150, 150, 32000, 9, 1, 4, 'FA-2025-012'),  -- Clavier std
('2025-01-20 09:00:00', 'ACHAT', 0, 50, 50, 170000, 10, 1, 4, 'FA-2025-013'), -- Clavier meca
('2025-01-22 09:00:00', 'ACHAT', 0, 15, 15, 3700000, 12, 1, 4, 'FA-2025-014'), -- iPhone
('2025-01-24 09:00:00', 'ACHAT', 0, 25, 25, 920000, 13, 1, 4, 'FA-2025-016'),  -- Tablette
('2025-01-26 09:00:00', 'ACHAT', 0, 80, 80, 110000, 14, 1, 4, 'FA-2025-017'),  -- Switch
('2025-01-28 09:00:00', 'ACHAT', 0, 40, 40, 265000, 15, 1, 4, 'FA-2025-019'),  -- Routeur
('2025-01-30 09:00:00', 'ACHAT', 0, 500, 500, 12000, 16, 1, 4, 'FA-2025-021'), -- Cable RJ45
('2025-02-01 09:00:00', 'ACHAT', 0, 100, 100, 80000, 17, 1, 4, 'FA-2025-023'), -- Toner
('2025-02-03 09:00:00', 'ACHAT', 0, 80, 80, 90000, 18, 1, 4, 'FA-2025-024'),   -- Encre
('2025-02-05 09:00:00', 'ACHAT', 0, 300, 300, 16000, 19, 1, 4, 'FA-2025-026'),  -- Papier
('2025-02-07 09:00:00', 'ACHAT', 0, 200, 200, 32000, 20, 1, 4, 'FA-2025-027');  -- Cle USB

-- ========== DEPOT 2 - FIFO (Analakely) ==========
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2025-01-15 10:00:00', 'ACHAT', 0, 10, 10, 2380000, 1, 2, 4, 'FA-2025-030'),
('2025-02-15 10:00:00', 'ACHAT', 10, 8, 18, 2520000, 1, 2, 4, 'FA-2025-035'),
('2025-01-18 10:00:00', 'ACHAT', 0, 30, 30, 610000, 5, 2, 4, 'FA-2025-031'),
('2025-01-20 10:00:00', 'ACHAT', 0, 100, 100, 43000, 7, 2, 4, 'FA-2025-032'),
('2025-01-22 10:00:00', 'ACHAT', 0, 100, 100, 33000, 9, 2, 4, 'FA-2025-033'),
('2025-01-24 10:00:00', 'ACHAT', 0, 20, 20, 1760000, 11, 2, 4, 'FA-2025-034'),
('2025-01-26 10:00:00', 'ACHAT', 0, 50, 50, 115000, 14, 2, 4, 'FA-2025-036'),
('2025-01-28 10:00:00', 'ACHAT', 0, 60, 60, 82000, 17, 2, 4, 'FA-2025-037'),
('2025-01-30 10:00:00', 'ACHAT', 0, 200, 200, 17000, 19, 2, 4, 'FA-2025-038');

-- ========== DEPOT 3 - LIFO (Anosizato) ==========
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2025-01-10 11:00:00', 'ACHAT', 0, 15, 15, 2350000, 1, 3, 4, 'FA-2025-040'),
('2025-02-10 11:00:00', 'ACHAT', 15, 12, 27, 2500000, 1, 3, 4, 'FA-2025-045'),
('2025-03-10 11:00:00', 'ACHAT', 27, 8, 35, 2650000, 1, 3, 4, 'FA-2025-050'),
('2025-01-12 11:00:00', 'ACHAT', 0, 8, 8, 3150000, 2, 3, 4, 'FA-2025-041'),
('2025-01-14 11:00:00', 'ACHAT', 0, 15, 15, 1720000, 4, 3, 4, 'FA-2025-042'),
('2025-01-16 11:00:00', 'ACHAT', 0, 40, 40, 630000, 5, 3, 4, 'FA-2025-043'),
('2025-01-18 11:00:00', 'ACHAT', 0, 150, 150, 41000, 7, 3, 4, 'FA-2025-044'),
('2025-01-20 11:00:00', 'ACHAT', 0, 60, 60, 108000, 14, 3, 4, 'FA-2025-046'),
('2025-01-22 11:00:00', 'ACHAT', 0, 300, 300, 13000, 16, 3, 4, 'FA-2025-047'),
('2025-01-24 11:00:00', 'ACHAT', 0, 150, 150, 17500, 19, 3, 4, 'FA-2025-048');

-- ========== DEPOT 5 - Tamatave ==========
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2025-01-20 09:00:00', 'ACHAT', 0, 12, 12, 2450000, 1, 5, 9, 'FA-2025-060'),
('2025-02-20 09:00:00', 'ACHAT', 12, 8, 20, 2550000, 1, 5, 9, 'FA-2025-065'),
('2025-01-22 09:00:00', 'ACHAT', 0, 25, 25, 640000, 5, 5, 9, 'FA-2025-061'),
('2025-01-24 09:00:00', 'ACHAT', 0, 80, 80, 44000, 7, 5, 9, 'FA-2025-062'),
('2025-01-26 09:00:00', 'ACHAT', 0, 15, 15, 1780000, 11, 5, 9, 'FA-2025-063'),
('2025-01-28 09:00:00', 'ACHAT', 0, 40, 40, 112000, 14, 5, 9, 'FA-2025-064'),
('2025-01-30 09:00:00', 'ACHAT', 0, 50, 50, 83000, 17, 5, 9, 'FA-2025-066'),
('2025-02-01 09:00:00', 'ACHAT', 0, 100, 100, 17500, 19, 5, 9, 'FA-2025-067');

-- ========== DEPOT 7 - Diego ==========
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
('2025-01-25 09:00:00', 'ACHAT', 0, 8, 8, 2480000, 1, 7, 4, 'FA-2025-070'),
('2025-01-27 09:00:00', 'ACHAT', 0, 15, 15, 650000, 5, 7, 4, 'FA-2025-071'),
('2025-01-29 09:00:00', 'ACHAT', 0, 60, 60, 45000, 7, 7, 4, 'FA-2025-072'),
('2025-01-31 09:00:00', 'ACHAT', 0, 10, 10, 1800000, 11, 7, 4, 'FA-2025-073');

-- =============================================================================
-- 6. DEVIS DE VENTE (differents statuts)
-- =============================================================================

INSERT INTO devis_vente (id, numero_devis, date_devis, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc) VALUES 
-- Devis EN_ATTENTE (pour validation)
(1, 'DV-2025-001', '2025-03-01', 7, 1, 3, 2, 12800000),   -- BOA - 4 laptops HP
(2, 'DV-2025-002', '2025-03-02', 8, 1, 5, 2, 8200000),    -- Airtel - laptops Dell
(3, 'DV-2025-003', '2025-03-03', 9, 1, 3, 2, 2550000),    -- JIRAMA - ecrans
-- Devis VALIDE (prets a etre transformes en BC)
(4, 'DV-2025-004', '2025-02-15', 10, 1, 3, 3, 6400000),   -- Carlton - laptops
(5, 'DV-2025-005', '2025-02-18', 11, 1, 5, 3, 4600000),   -- Universite - smartphones
(6, 'DV-2025-006', '2025-02-20', 12, 1, 3, 3, 1700000),   -- SocGen - ecrans
-- Devis BROUILLON
(7, 'DV-2025-007', '2025-03-05', 7, 1, 3, 1, 9000000),    -- BOA - commande potentielle
(8, 'DV-2025-008', '2025-03-06', 8, 1, 5, 1, 3500000);    -- Airtel - commande potentielle

INSERT INTO devis_vente_details (devis_vente_id, article_id, quantite, prix_unitaire) VALUES 
-- Devis 1: 4 laptops HP
(1, 1, 4, 3200000),
-- Devis 2: 2 laptops Dell
(2, 2, 2, 4100000),
-- Devis 3: 3 ecrans 24"
(3, 5, 3, 850000),
-- Devis 4: 2 laptops HP
(4, 1, 2, 3200000),
-- Devis 5: 2 Samsung A54
(5, 11, 2, 2300000),
-- Devis 6: 2 ecrans 24"
(6, 5, 2, 850000),
-- Devis 7: mix
(7, 1, 2, 3200000), (7, 5, 3, 850000),
-- Devis 8: tablettes
(8, 13, 3, 1166667);

-- =============================================================================
-- 7. BONS DE COMMANDE VENTE (differents statuts)
-- =============================================================================

INSERT INTO bon_commande_vente (id, numero_bc, date_commande, devis_vente_id, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, depot_expedition_id) VALUES 
-- BC VALIDE (prets a facturer)
(1, 'BCV-2025-001', '2025-02-16', 4, 10, 1, 3, 3, 6400000, 1),   -- Carlton
(2, 'BCV-2025-002', '2025-02-19', 5, 11, 1, 5, 3, 4600000, 1),   -- Universite
-- BC LIVRE (deja livre, en attente facture)
(3, 'BCV-2025-003', '2025-02-21', 6, 12, 1, 3, 4, 1700000, 1);   -- SocGen

INSERT INTO bon_commande_vente_details (bon_commande_vente_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 2, 3200000),
(2, 11, 2, 2300000),
(3, 5, 2, 850000);

-- =============================================================================
-- 8. FACTURES VENTE (differents statuts pour tester le flux LIVRE->stock)
-- =============================================================================

INSERT INTO facture_vente (id, numero_facture, date_facture, bon_commande_vente_id, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, reste_a_payer, depot_expedition_id) VALUES 
-- Factures VALIDE (a livrer pour tester la diminution de stock)
(1, 'FV-2025-001', '2025-02-22', 3, 12, 1, 3, 3, 1700000, 1700000, 1),  -- Pour test livraison
(2, 'FV-2025-002', '2025-02-25', 1, 10, 1, 3, 3, 6400000, 6400000, 1),  -- Carlton
-- Factures EN_ATTENTE
(3, 'FV-2025-003', '2025-03-01', 2, 11, 1, 5, 2, 4600000, 4600000, 1);  -- Universite

INSERT INTO facture_vente_details (facture_vente_id, article_id, quantite, prix_unitaire) VALUES 
(1, 5, 2, 850000),    -- 2 ecrans
(2, 1, 2, 3200000),   -- 2 laptops HP
(3, 11, 2, 2300000);  -- 2 smartphones

-- =============================================================================
-- 9. CYCLE ACHAT COMPLET (pour tester le flux)
-- =============================================================================

-- Demandes d'achat
INSERT INTO proforma_demande_achat (id, numero_da, date_demande, personnel_demandeur_id, entreprise_id, depot_cible_id, motif_achat, statut_id) VALUES 
(1, 'DA-2025-001', '2025-02-01', 2, 1, 1, 'Reapprovisionnement laptops', 3),
(2, 'DA-2025-002', '2025-02-05', 2, 1, 1, 'Stock ecrans', 3),
(3, 'DA-2025-003', '2025-02-10', 2, 1, 1, 'Consommables bureau', 2),
(4, 'DA-2025-004', '2025-03-01', 7, 2, 5, 'Stock Tamatave', 2);

INSERT INTO proforma_demande_achat_details (proforma_demande_achat_id, article_id, quantite_demandee, prix_estime) VALUES 
(1, 1, 10, 2500000), (1, 2, 5, 3200000),
(2, 5, 20, 650000), (2, 6, 10, 980000),
(3, 17, 50, 85000), (3, 18, 40, 95000), (3, 19, 100, 18000),
(4, 1, 5, 2500000), (4, 7, 50, 45000);

-- Proformas fournisseur
INSERT INTO proforma_fournisseur (id, numero_proforma, date_emission, date_validite, entreprise_fournisseur_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, proforma_demande_achat_id) VALUES 
(1, 'PRO-2025-001', '2025-02-02', '2025-03-02', 4, 1, 2, 3, 41000000, 1),
(2, 'PRO-2025-002', '2025-02-06', '2025-03-06', 5, 1, 2, 3, 22800000, 2),
(3, 'PRO-2025-003', '2025-02-11', '2025-03-11', 6, 1, 2, 2, 10050000, 3);

INSERT INTO proforma_fournisseur_details (proforma_fournisseur_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 10, 2500000), (1, 2, 5, 3200000),
(2, 5, 20, 650000), (2, 6, 10, 980000),
(3, 17, 50, 85000), (3, 18, 40, 95000), (3, 19, 100, 18000);

-- Bons de commande achat
INSERT INTO bon_commande_achat (id, numero_bc, date_commande, proforma_fournisseur_id, entreprise_fournisseur_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, depot_livraison_id) VALUES 
(1, 'BCA-2025-001', '2025-02-03', 1, 4, 1, 2, 4, 41000000, 1),
(2, 'BCA-2025-002', '2025-02-07', 2, 5, 1, 2, 3, 22800000, 1);

INSERT INTO bon_commande_achat_details (bon_commande_achat_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 10, 2500000), (1, 2, 5, 3200000),
(2, 5, 20, 650000), (2, 6, 10, 980000);

-- Factures achat (receptions)
INSERT INTO facture_achat (id, numero_facture_fournisseur, date_facture, bon_commande_achat_id, entreprise_fournisseur_id, entreprise_filiale_id, statut_id, montant_ttc, reste_a_payer, depot_reception_id) VALUES 
(1, 'INV-FN-2025-001', '2025-02-08', 1, 4, 1, 4, 41000000, 41000000, 1);

INSERT INTO facture_achat_details (facture_achat_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 10, 2500000), (1, 2, 5, 3200000);

-- =============================================================================
-- 10. PAIEMENTS (pour tester la caisse)
-- =============================================================================

-- D'abord creer les mouvements de caisse (requis par les paiements)
INSERT INTO caisse_mouvement (id, date_mouvement, libelle_operation, montant_entree, montant_sortie, solde_avant, solde_apres, caisse_id, personnel_id) VALUES 
(1, '2025-02-28 10:00:00', 'Paiement facture FV-2025-001 - SOCIETE GENERALE', 1000000, 0, 50000000, 51000000, 1, 6),
(2, '2025-02-15 14:00:00', 'Paiement fournisseur FA achat - CHINA ELECTRONICS', 0, 20000000, 51000000, 31000000, 1, 6),
(3, '2025-03-01 12:00:00', 'Paiement facture FV-2025-002 - CARLTON', 2000000, 0, 31000000, 33000000, 1, 6),
(4, '2025-03-05 10:30:00', 'Acompte facture FV-2025-003 - UNIVERSITE', 1500000, 0, 33000000, 34500000, 1, 6);

-- Paiements vente (format schema actuel : mode_paiement_id, statut_id, montant, reference_externe)
INSERT INTO paiement_vente (id, numero_recu, mode_paiement_id, statut_id, facture_vente_id, caisse_mouvement_id, montant, date_paiement, reference_externe) VALUES 
(1, 'RV-2025-001', 2, 5, 1, 1, 1000000, '2025-02-28', 'VIR-BOA-2025-001'),
(2, 'RV-2025-002', 4, 6, 2, 3, 2000000, '2025-03-01', 'CARD-002'),
(3, 'RV-2025-003', 1, 1, 3, 4, 1500000, '2025-03-05', NULL);

-- Paiements achat (schema actuel)
INSERT INTO paiement_achat (id, numero_paiement, mode_paiement_id, statut_id, facture_achat_id, caisse_mouvement_id, montant, date_paiement, reference_externe) VALUES 
(1, 'PA-2025-001', 2, 5, 1, 2, 20000000, '2025-02-15', 'VIR-BNI-2025-001'),
(2, 'PA-2025-002', 3, 6, 1, NULL, 5000000, '2025-02-20', 'CHQ-98765');

-- Mise a jour reste a payer
UPDATE facture_vente SET reste_a_payer = 700000 WHERE id = 1;
UPDATE facture_achat SET reste_a_payer = 21000000 WHERE id = 1;

-- Mise a jour solde caisse
UPDATE caisse SET solde_actuel = 31000000 WHERE id = 1;

-- =============================================================================
-- 11. QUELQUES ARTICLES EN RUPTURE OU STOCK BAS
-- =============================================================================

-- Simuler des ventes qui ont reduit le stock (via mouvements de sortie directs)
-- Note: normalement fait par le trigger quand facture passe a LIVRE
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, prix_unitaire_mouvement, article_id, depot_id, personnel_id, reference_document) VALUES 
-- Ventes depuis depot 1 pour avoir des stocks bas
('2025-03-01 10:00:00', 'VENTE', 15, 0, 12, 3, 3000000, 3, 1, 3, 'FV-TEST-001'),  -- Lenovo: reste 3
('2025-03-02 10:00:00', 'VENTE', 15, 0, 15, 0, 4500000, 12, 1, 3, 'FV-TEST-002'), -- iPhone: rupture
('2025-03-03 10:00:00', 'VENTE', 25, 0, 20, 5, 1300000, 6, 1, 3, 'FV-TEST-003'),   -- Ecran 27: stock bas
('2025-03-04 10:00:00', 'VENTE', 150, 0, 148, 2, 55000, 9, 1, 3, 'FV-TEST-004');  -- Clavier std: stock bas

-- =============================================================================
-- VERIFICATION FINALE
-- =============================================================================

-- Resynchroniser les sequences avec les donnees inserees
SELECT setval('caisse_mouvement_id_seq', COALESCE((SELECT MAX(id) FROM caisse_mouvement), 0) + 1, false);
SELECT setval('paiement_vente_id_seq', COALESCE((SELECT MAX(id) FROM paiement_vente), 0) + 1, false);
SELECT setval('paiement_achat_id_seq', COALESCE((SELECT MAX(id) FROM paiement_achat), 0) + 1, false);
SELECT setval('mouvement_stock_id_seq', COALESCE((SELECT MAX(id) FROM mouvement_stock), 0) + 1, false);
SELECT setval('lot_stock_id_seq', COALESCE((SELECT MAX(id) FROM lot_stock), 0) + 1, false);
SELECT setval('stock_id_seq', COALESCE((SELECT MAX(id) FROM stock), 0) + 1, false);
SELECT setval('devis_vente_id_seq', COALESCE((SELECT MAX(id) FROM devis_vente), 0) + 1, false);
SELECT setval('bon_commande_vente_id_seq', COALESCE((SELECT MAX(id) FROM bon_commande_vente), 0) + 1, false);
SELECT setval('facture_vente_id_seq', COALESCE((SELECT MAX(id) FROM facture_vente), 0) + 1, false);
SELECT setval('proforma_demande_achat_id_seq', COALESCE((SELECT MAX(id) FROM proforma_demande_achat), 0) + 1, false);
SELECT setval('proforma_fournisseur_id_seq', COALESCE((SELECT MAX(id) FROM proforma_fournisseur), 0) + 1, false);
SELECT setval('bon_commande_achat_id_seq', COALESCE((SELECT MAX(id) FROM bon_commande_achat), 0) + 1, false);
SELECT setval('facture_achat_id_seq', COALESCE((SELECT MAX(id) FROM facture_achat), 0) + 1, false);

SELECT 'Donnees de test chargees avec succes!' as status;

SELECT 
    'Statistiques:' as info,
    (SELECT COUNT(*) FROM article) as articles,
    (SELECT COUNT(*) FROM stock WHERE quantite_actuelle > 0) as stocks_actifs,
    (SELECT COUNT(*) FROM mouvement_stock) as mouvements,
    (SELECT COUNT(*) FROM devis_vente) as devis,
    (SELECT COUNT(*) FROM bon_commande_vente) as bc_vente,
    (SELECT COUNT(*) FROM facture_vente) as factures_vente,
    (SELECT COUNT(*) FROM depot) as depots;

-- Verification stock valorise
SELECT 
    d.nom as depot,
    mvs.code as methode,
    COUNT(*) as nb_articles,
    SUM(s.quantite_actuelle) as qte_totale,
    SUM(s.valeur_stock_total) as valeur_totale
FROM stock s
JOIN depot d ON s.depot_id = d.id
JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
WHERE s.quantite_actuelle > 0
GROUP BY d.nom, mvs.code
ORDER BY d.nom;
