-- ==============================================================================
-- FICHIER : DATA.SQL (V3.5)
-- Jeu de données Architecture Complète (DA > Proforma > BC > Facture)
-- ==============================================================================

-- 0. NETTOYAGE PRÉALABLE
-- ==============================================================================
TRUNCATE TABLE 
paiement_achat, 
paiement_vente, 
caisse_mouvement, caisse, 
facture_achat_details, facture_achat, 
facture_vente_details, facture_vente, 
bon_commande_achat_details, bon_commande_achat, 
bon_commande_vente_details, bon_commande_vente,
proforma_fournisseur_details, proforma_fournisseur,
proforma_demande_achat_details, proforma_demande_achat, -- NOUVEAU
sortie_lot_detail, lot_stock, mouvement_stock, stock, 
personnel, depot, site, entreprise, groupe, 
article, personnel_role, article_categorie, unite, statut, mode_paiement, methode_valorisation_stock 
CASCADE;


-- 1. DONNÉES DE RÉFÉRENCE
-- =======================

INSERT INTO statut (id, code, libelle, niveau) VALUES 
(1, 'BROUILLON', 'Brouillon', 0),
(2, 'EN_ATTENTE', 'En attente de validation', 1),
(3, 'VALIDE', 'Validé / Confirmé', 2),
(4, 'LIVRE', 'Livré / Réceptionné', 3),
(5, 'PAYE', 'Payé intégralement', 4),
(6, 'PARTIEL', 'Payé partiellement', 3),
(7, 'ANNULE', 'Annulé', 99);

INSERT INTO unite (id, code, libelle) VALUES 
(1, 'U', 'Unité'),
(2, 'KG', 'Kilogramme'),
(3, 'L', 'Litre'),
(4, 'M', 'Mètre'),
(5, 'H', 'Heure'),
(6, 'JR', 'Jour');

INSERT INTO article_categorie (id, code, libelle) VALUES 
(1, 'HARDWARE', 'Matériel Informatique'),
(2, 'CONSOMMABLE', 'Consommables & Bureau'),
(3, 'SERVICE', 'Prestations de Service');

INSERT INTO personnel_role (id, code, libelle, niveau_acces) VALUES 
(1, 'ADMIN', 'Administrateur Système', 10),
(2, 'GERANT', 'Gérant de Filiale', 5),
(3, 'VENDEUR', 'Commercial / Vendeur', 2),
(4, 'COMPTABLE', 'Comptable / Trésorier', 3),
(5, 'LOGISTIQUE', 'Responsable Stock', 2);

INSERT INTO mode_paiement (id, code, libelle) VALUES 
(1, 'ESPECE', 'Espèces'),
(2, 'CHEQUE', 'Chèque Bancaire'),
(3, 'VIREMENT', 'Virement Bancaire'),
(4, 'MVola', 'Mobile Money (MVola)'),
(5, 'AirtelMoney', 'Mobile Money (Airtel)'),
(6, 'OrangeMoney', 'Mobile Money (Orange)');

INSERT INTO methode_valorisation_stock (id, code, libelle, description) VALUES
(1, 'CMUP', 'Coût Moyen Unitaire Pondéré', 'Recalculé à chaque entrée'),
(2, 'FIFO', 'First In, First Out', 'Premier entré, premier sorti (PEPS)'),
(3, 'LIFO', 'Last In, First Out', 'Dernier entré, premier sorti (DEPS)');


-- 2. ARCHITECTURE ORGANISATIONNELLE
-- =================================

-- GROUPE
INSERT INTO groupe (id, nom, description) VALUES
(1, 'TECH HOLDING GROUP', 'Groupe spécialisé dans la distribution informatique');

-- ENTREPRISES (Filiales Interne + Tiers)
INSERT INTO entreprise (id, nom, groupe_id, type_entreprise, email, telephone, matricule_fiscal, est_actif) VALUES 
(1, 'TECH DISTRIB (Siège)', 1, 'INTERNE', 'hq@tech.mg', '+261 34 00 000 01', 'NIF-001-HQ', true),
(2, 'TECH STORE (Retail)', 1, 'INTERNE', 'shop@tech.mg', '+261 34 00 000 02', 'NIF-001-SH', true),
(3, 'CHINA SUPPLIER LTD', NULL, 'FOURNISSEUR', 'export@china.cn', '+86 000 000', 'EXT-CN-001', true),
(4, 'PAPETERIE LOCALE', NULL, 'FOURNISSEUR', 'contact@pap.mg', '+261 33 00 000', 'NIF-LOC-002', true),
(5, 'BIG CORP S.A.', NULL, 'CLIENT', 'achat@big.mg', '+261 32 00 000', 'NIF-CLI-999', true),
(6, 'CLIENT COMPTOIR', NULL, 'CLIENT', 'N/A', 'N/A', NULL, true);

-- SITES (Localisation géographique)
INSERT INTO site (id, nom, adresse, entreprise_id) VALUES
(1, 'Siège Ankorondrano', 'Zone Industrielle', 1),
(2, 'Showroom Analakely', 'Avenue de l''Indépendance', 2);

-- DÉPÔTS (Lieux de stockage physique)
INSERT INTO depot (id, nom, adresse, site_id) VALUES
(1, 'Entrepôt Central', 'Hangar A, Ankorondrano', 1), -- Dépôt ID 1 (Lié au Siège)
(2, 'Stock Arrière Boutique', 'Analakely', 2);        -- Dépôt ID 2 (Lié au Showroom)

-- PERSONNELS
INSERT INTO personnel (id, code_employe, nom, prenom, email, mot_de_passe_hash, personnel_role_id, entreprise_id, site_defaut_id) VALUES 
(1, 'ADM001', 'SYSTEM', 'Admin', 'admin@gmail.mg', 'hash123', 1, 1, 1),
(2, 'HQ001', 'ANDRIAM', 'Hery', 'hery@tech.mg', 'hash123', 2, 1, 1),
(3, 'HQ002', 'RAZAFY', 'Tina', 'tina@tech.mg', 'hash123', 5, 1, 1), -- Logistique Siège
(4, 'SH001', 'RABARY', 'Soa', 'soa@tech.mg', 'hash123', 3, 2, 2), -- Vendeuse Showroom
(5, 'SH002', 'RANAIVO', 'Luc', 'luc@tech.mg', 'hash123', 3, 2, 2);


-- 3. CATALOGUE ARTICLES
-- =====================
INSERT INTO article (id, reference, designation, description, prix_achat_ref, prix_vente_ref, unite_id, article_categorie_id) VALUES
(1, 'LAP-DELL-I5', 'Laptop Dell Vostro 15"', 'Core i5, 8GB, 256GB SSD', 1800000, 2400000, 1, 1),
(2, 'LAP-HP-RYZ', 'Laptop HP Ryzen 5', 'Ryzen 5, 16GB, 512GB SSD', 2000000, 2800000, 1, 1),
(3, 'MON-24-SAM', 'Ecran Samsung 24"', 'IPS, 75Hz, HDMI', 450000, 650000, 1, 1),
(4, 'PRT-EPSON', 'Imprimante Epson L3150', 'EcoTank, Wifi', 700000, 950000, 1, 1),
(5, 'INK-BLK', 'Encre Epson Noire 003', 'Bouteille 65ml', 25000, 40000, 1, 2),
(6, 'PAP-A4', 'Papier A4 80g', 'Carton de 5 ramettes', 90000, 120000, 1, 2),
(7, 'KEY-USB', 'Clé USB 32GB', 'Kingston DataTraveler', 15000, 25000, 1, 2);


-- 4. CAISSE & SOLDE INITIAL
-- =========================
INSERT INTO caisse (id, code_caisse, libelle, solde_actuel, entreprise_id) VALUES 
(1, 'C-MAIN-HQ', 'Caisse Principale Siège', 100000000, 1),
(2, 'C-POS-01', 'Caisse Vente Showroom', 2000000, 2);

INSERT INTO caisse_mouvement (id, date_mouvement, libelle_operation, montant_entree, montant_sortie, solde_avant, solde_apres, caisse_id, personnel_id) VALUES
(1, NOW() - INTERVAL '30 days', 'Apport Capital Initial', 100000000, 0, 0, 100000000, 1, 1),
(2, NOW() - INTERVAL '30 days', 'Fond de caisse démarrage', 2000000, 0, 0, 2000000, 2, 2);


-- 5. STOCK INITIAL (DEPOTS)
-- =========================

-- A. STOCK
INSERT INTO stock (id, article_id, depot_id, methode_valorisation_stock_id, quantite_actuelle, cmup_actuel, valeur_stock_total) VALUES
(1, 1, 1, 1, 10, 1800000, 18000000), -- Dépôt 1: Dell
(2, 2, 1, 1, 5, 2000000, 10000000),  -- Dépôt 1: HP
(3, 4, 1, 1, 20, 700000, 14000000),  -- Dépôt 1: Imprimante
(4, 6, 1, 1, 100, 90000, 9000000),   -- Dépôt 1: Papier
(5, 1, 2, 2, 2, 1800000, 3600000),   -- Dépôt 2: Dell
(6, 7, 2, 2, 50, 15000, 750000);     -- Dépôt 2: USB

-- B. MOUVEMENTS INITIAUX
INSERT INTO mouvement_stock (id, date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, depot_id, personnel_id, reference_document) VALUES
(1, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 10, 0, 10, 1, 1, 1, 'INV-INIT'),
(2, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 5, 0, 5, 2, 1, 1, 'INV-INIT'),
(3, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 20, 0, 20, 4, 1, 1, 'INV-INIT'),
(4, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 100, 0, 100, 6, 1, 1, 'INV-INIT'),
(5, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 2, 0, 2, 1, 2, 1, 'INV-INIT'),
(6, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 50, 0, 50, 7, 2, 1, 'INV-INIT');

-- C. LOTS INITIAUX
INSERT INTO lot_stock (id, numero_lot, article_id, depot_id, date_entree, mouvement_entree_id, quantite_initiale, quantite_restante, prix_unitaire_achat, statut) VALUES
(1, 'LOT-INIT-DL-001', 1, 1, NOW() - INTERVAL '30 days', 1, 10, 10, 1800000, 'ACTIF'),
(2, 'LOT-INIT-HP-001', 2, 1, NOW() - INTERVAL '30 days', 2, 5, 5, 2000000, 'ACTIF'),
(3, 'LOT-INIT-PR-001', 4, 1, NOW() - INTERVAL '30 days', 3, 20, 20, 700000, 'ACTIF'),
(4, 'LOT-INIT-PA-001', 6, 1, NOW() - INTERVAL '30 days', 4, 100, 100, 90000, 'ACTIF'),
(5, 'LOT-INIT-DL-SH1', 1, 2, NOW() - INTERVAL '30 days', 5, 2, 2, 1800000, 'ACTIF'),
(6, 'LOT-INIT-US-SH1', 7, 2, NOW() - INTERVAL '30 days', 6, 50, 50, 15000, 'ACTIF');


-- 6. SCÉNARIO COMPLET : DEMANDE -> COMMANDE -> FACTURE
-- ====================================================
-- Contexte : Tina (Logistique) a besoin de 10 Écrans Samsung pour le Siège.

-- ETAPE 1 : La Demande Interne (DA)
INSERT INTO proforma_demande_achat (id, numero_da, date_demande, personnel_demandeur_id, entreprise_id, depot_cible_id, date_souhaitee, motif_achat, statut_id, montant_ttc) VALUES
(1, 'DA-HQ-23-001', NOW() - INTERVAL '20 days', 3, 1, 1, NOW() - INTERVAL '10 days', 'Renouvellement parc écran', 3, 4600000); -- Statut 3 = VALIDÉ

INSERT INTO proforma_demande_achat_details (id, proforma_demande_achat_id, article_id, quantite_demandee, prix_estime) VALUES
(1, 1, 3, 10, 460000); -- Estimation un peu plus chère que le réel

-- ETAPE 2 : Le Devis Fournisseur (Proforma) lié à la DA
-- On a interrogé "PAPETERIE LOCALE" (ID 4)
INSERT INTO proforma_fournisseur (id, numero_proforma, date_emission, entreprise_fournisseur_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, proforma_demande_achat_id) VALUES
(1, 'PROF-PAP-088', NOW() - INTERVAL '18 days', 4, 1, 3, 3, 4500000, 1); -- Lien vers DA ID 1

INSERT INTO proforma_fournisseur_details (id, proforma_fournisseur_id, article_id, quantite, prix_unitaire) VALUES
(1, 1, 3, 10, 450000); -- Le fournisseur propose 450.000

-- ETAPE 3 : Le Bon de Commande (BC)
INSERT INTO bon_commande_achat (id, numero_bc, date_commande, proforma_fournisseur_id, entreprise_fournisseur_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, depot_livraison_id) VALUES
(1, 'BCA-HQ-101', NOW() - INTERVAL '16 days', 1, 4, 1, 3, 3, 4500000, 1);

INSERT INTO bon_commande_achat_details (id, bon_commande_achat_id, article_id, quantite, prix_unitaire) VALUES
(1, 1, 3, 10, 450000);

-- ETAPE 4 : La Facture & Réception (Entrée Stock)
INSERT INTO facture_achat (id, numero_facture_fournisseur, date_facture, bon_commande_achat_id, entreprise_fournisseur_id, entreprise_filiale_id, depot_reception_id, statut_id, montant_ttc, reste_a_payer) VALUES
(1, 'FAC-FRN-2023-88', NOW() - INTERVAL '15 days', 1, 4, 1, 1, 5, 4500000, 0); -- Lié au BC ID 1

INSERT INTO facture_achat_details (id, facture_achat_id, article_id, quantite, prix_unitaire) VALUES
(1, 1, 3, 10, 450000);

-- Mouvements & Stock (Suite à la réception)
INSERT INTO stock (id, article_id, depot_id, methode_valorisation_stock_id, quantite_actuelle, cmup_actuel, valeur_stock_total) VALUES
(7, 3, 1, 1, 10, 450000, 4500000); 

INSERT INTO mouvement_stock (id, date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, depot_id, personnel_id, reference_document) VALUES
(7, NOW() - INTERVAL '15 days', 'ACHAT', 0, 10, 0, 10, 3, 1, 3, 'FAC-FRN-2023-88');

INSERT INTO lot_stock (id, numero_lot, article_id, depot_id, date_entree, mouvement_entree_id, quantite_initiale, quantite_restante, prix_unitaire_achat) VALUES
(7, 'LOT-ACH-ECR-001', 3, 1, NOW() - INTERVAL '15 days', 7, 10, 10, 450000);

-- ETAPE 5 : Le Paiement
INSERT INTO caisse_mouvement (id, date_mouvement, libelle_operation, montant_entree, montant_sortie, solde_avant, solde_apres, caisse_id, personnel_id) VALUES
(3, NOW() - INTERVAL '15 days', 'Paiement Facture Ecrans', 0, 4500000, 100000000, 95500000, 1, 2);
UPDATE caisse SET solde_actuel = 95500000 WHERE id = 1;

-- Paiements achat : on remplace les lignes détails par paiements par mode
INSERT INTO paiement_achat (id, numero_paiement, mode_paiement_id, statut_id, facture_achat_id, caisse_mouvement_id, montant, date_paiement) VALUES
(1, 'PAY-ACH-001-1', 2, 3, 1, 3, 4000000, NOW() - INTERVAL '15 days'),
(2, 'PAY-ACH-001-2', 1, 3, 1, 3, 500000, NOW() - INTERVAL '15 days');


-- 7. SCÉNARIO : VENTE SHOWROOM
-- ============================

-- 1. Facture Vente (Directe)
INSERT INTO facture_vente (id, numero_facture, date_facture, entreprise_client_id, entreprise_filiale_id, depot_expedition_id, personnel_id, statut_id, montant_ttc, reste_a_payer) VALUES
(1, 'FV-SH-23001', NOW() - INTERVAL '1 day', 6, 2, 2, 4, 5, 2425000, 0);

INSERT INTO facture_vente_details (id, facture_vente_id, article_id, quantite, prix_unitaire) VALUES
(1, 1, 1, 1, 2400000),
(2, 1, 7, 1, 25000);

-- 2. Mouvements Stock
UPDATE stock SET quantite_actuelle = 1 WHERE id = 5; 
INSERT INTO mouvement_stock (id, date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, depot_id, personnel_id, reference_document) VALUES
(8, NOW() - INTERVAL '1 day', 'VENTE', 2, 0, 1, 1, 1, 2, 4, 'FV-SH-23001');

UPDATE stock SET quantite_actuelle = 49 WHERE id = 6;
INSERT INTO mouvement_stock (id, date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, depot_id, personnel_id, reference_document) VALUES
(9, NOW() - INTERVAL '1 day', 'VENTE', 50, 0, 1, 49, 7, 2, 4, 'FV-SH-23001');

-- 3. Lots
UPDATE lot_stock SET quantite_restante = 1 WHERE id = 5; 
UPDATE lot_stock SET quantite_restante = 49 WHERE id = 6; 

-- 4. Paiement
INSERT INTO caisse_mouvement (id, date_mouvement, libelle_operation, montant_entree, montant_sortie, solde_avant, solde_apres, caisse_id, personnel_id) VALUES
(4, NOW() - INTERVAL '1 day', 'Vente Client Comptoir', 2425000, 0, 2000000, 4425000, 2, 4);
UPDATE caisse SET solde_actuel = 4425000 WHERE id = 2;

-- Paiements vente : paiement par mode (un enregistrement = un mode + montant)
INSERT INTO paiement_vente (id, numero_recu, mode_paiement_id, statut_id, facture_vente_id, caisse_mouvement_id, montant, date_paiement) VALUES
(1, 'REC-001', 4, 3, 1, 4, 2425000, NOW() - INTERVAL '1 day');

-- Exemple: Paiement partiel en attente pour la facture 2 (B2B) déplacé pour garantir l'existence de la facture (voir plus bas)
-- INSERT moved below after creation of facture_vente id=2


-- 8. SCÉNARIO : GROSSE VENTE B2B (Avec BC)
-- ========================================

-- 1. BC Client
INSERT INTO bon_commande_vente (id, numero_bc, date_commande, entreprise_client_id, entreprise_filiale_id, depot_expedition_id, personnel_id, statut_id, montant_ttc) VALUES
(1, 'BCV-HQ-009', NOW(), 5, 1, 1, 2, 3, 12000000);

INSERT INTO bon_commande_vente_details (id, bon_commande_vente_id, article_id, quantite, prix_unitaire) VALUES
(1, 1, 1, 5, 2400000);

-- 2. Facture
INSERT INTO facture_vente (id, numero_facture, date_facture, bon_commande_vente_id, entreprise_client_id, entreprise_filiale_id, depot_expedition_id, personnel_id, statut_id, montant_ttc, reste_a_payer) VALUES
(2, 'FV-HQ-23050', NOW(), 1, 5, 1, 1, 2, 3, 12000000, 12000000);

INSERT INTO facture_vente_details (id, facture_vente_id, article_id, quantite, prix_unitaire) VALUES
(3, 2, 1, 5, 2400000);

-- Exemple: Paiement partiel en attente pour la facture 2 (B2B) : création d'un paiement draft
INSERT INTO paiement_vente (id, numero_recu, mode_paiement_id, statut_id, facture_vente_id, caisse_mouvement_id, montant, date_paiement) VALUES
(2, 'REC-002', 2, 1, 2, NULL, 4000000, NOW());

-- 3. Stock
UPDATE stock SET quantite_actuelle = 5 WHERE id = 1;
INSERT INTO mouvement_stock (id, date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, depot_id, personnel_id, reference_document) VALUES
(10, NOW(), 'VENTE', 10, 0, 5, 5, 1, 1, 2, 'FV-HQ-23050');

-- 4. Lot
UPDATE lot_stock SET quantite_restante = 5 WHERE id = 1;


-- 9. RESET DES SÉQUENCES
-- ======================
SELECT setval('groupe_id_seq', (SELECT MAX(id) FROM groupe));
SELECT setval('entreprise_id_seq', (SELECT MAX(id) FROM entreprise));
SELECT setval('site_id_seq', (SELECT MAX(id) FROM site));
SELECT setval('depot_id_seq', (SELECT MAX(id) FROM depot));
SELECT setval('statut_id_seq', (SELECT MAX(id) FROM statut));
SELECT setval('unite_id_seq', (SELECT MAX(id) FROM unite));
SELECT setval('article_categorie_id_seq', (SELECT MAX(id) FROM article_categorie));
SELECT setval('personnel_role_id_seq', (SELECT MAX(id) FROM personnel_role));
SELECT setval('mode_paiement_id_seq', (SELECT MAX(id) FROM mode_paiement));
SELECT setval('methode_valorisation_stock_id_seq', (SELECT MAX(id) FROM methode_valorisation_stock));
SELECT setval('personnel_id_seq', (SELECT MAX(id) FROM personnel));
SELECT setval('article_id_seq', (SELECT MAX(id) FROM article));
SELECT setval('caisse_id_seq', (SELECT MAX(id) FROM caisse));
SELECT setval('stock_id_seq', (SELECT MAX(id) FROM stock));
SELECT setval('mouvement_stock_id_seq', (SELECT MAX(id) FROM mouvement_stock));
SELECT setval('lot_stock_id_seq', (SELECT MAX(id) FROM lot_stock));
SELECT setval('caisse_mouvement_id_seq', (SELECT MAX(id) FROM caisse_mouvement));

-- Séquences Achats complètes
SELECT setval('proforma_demande_achat_id_seq', (SELECT MAX(id) FROM proforma_demande_achat));
SELECT setval('proforma_demande_achat_details_id_seq', (SELECT MAX(id) FROM proforma_demande_achat_details));
SELECT setval('proforma_fournisseur_id_seq', (SELECT MAX(id) FROM proforma_fournisseur));
SELECT setval('proforma_fournisseur_details_id_seq', (SELECT MAX(id) FROM proforma_fournisseur_details));
SELECT setval('bon_commande_achat_id_seq', (SELECT MAX(id) FROM bon_commande_achat));
SELECT setval('bon_commande_achat_details_id_seq', (SELECT MAX(id) FROM bon_commande_achat_details));
SELECT setval('facture_achat_id_seq', (SELECT MAX(id) FROM facture_achat));
SELECT setval('facture_achat_details_id_seq', (SELECT MAX(id) FROM facture_achat_details));
SELECT setval('paiement_achat_id_seq', (SELECT MAX(id) FROM paiement_achat));

-- Séquences Ventes
SELECT setval('facture_vente_id_seq', (SELECT MAX(id) FROM facture_vente));
SELECT setval('facture_vente_details_id_seq', (SELECT MAX(id) FROM facture_vente_details));
SELECT setval('paiement_vente_id_seq', (SELECT MAX(id) FROM paiement_vente));
SELECT setval('bon_commande_vente_id_seq', (SELECT MAX(id) FROM bon_commande_vente));
SELECT setval('bon_commande_vente_details_id_seq', (SELECT MAX(id) FROM bon_commande_vente_details));