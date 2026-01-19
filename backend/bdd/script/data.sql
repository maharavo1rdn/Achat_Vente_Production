-- ==============================================================================
-- FICHIER : DATA.SQL
-- Jeu de données compatible Architecture V3.2
-- Scénario : 30 jours d'activité sur 2 filiales
-- ==============================================================================

-- 1. DONNÉES DE RÉFÉRENCE (LES BASES)
-- ===================================

-- Statuts des documents
INSERT INTO statut (id, code, libelle, niveau) VALUES 
(1, 'BROUILLON', 'Brouillon', 0),
(2, 'EN_ATTENTE', 'En attente de validation', 1),
(3, 'VALIDE', 'Validé / Confirmé', 2),
(4, 'LIVRE', 'Livré / Réceptionné', 3),
(5, 'PAYE', 'Payé intégralement', 4),
(6, 'PARTIEL', 'Payé partiellement', 3),
(7, 'ANNULE', 'Annulé', 99);

-- Unités de mesure
INSERT INTO unite (id, code, libelle) VALUES 
(1, 'U', 'Unité'),
(2, 'KG', 'Kilogramme'),
(3, 'L', 'Litre'),
(4, 'M', 'Mètre'),
(5, 'H', 'Heure'),
(6, 'JR', 'Jour');

-- Catégories Articles
INSERT INTO article_categorie (id, code, libelle) VALUES 
(1, 'HARDWARE', 'Matériel Informatique'),
(2, 'CONSOMMABLE', 'Consommables & Bureau'),
(3, 'SERVICE', 'Prestations de Service');

-- Rôles Personnel
INSERT INTO personnel_role (id, code, libelle, niveau_acces) VALUES 
(1, 'ADMIN', 'Administrateur Système', 10),
(2, 'GERANT', 'Gérant de Filiale', 5),
(3, 'VENDEUR', 'Commercial / Vendeur', 2),
(4, 'COMPTABLE', 'Comptable / Trésorier', 3),
(5, 'LOGISTIQUE', 'Responsable Stock', 2);

-- Modes de Paiement
INSERT INTO mode_paiement (id, code, libelle) VALUES 
(1, 'ESPECE', 'Espèces'),
(2, 'CHEQUE', 'Chèque Bancaire'),
(3, 'VIREMENT', 'Virement Bancaire'),
(4, 'MVola', 'Mobile Money (MVola)'),
(5, 'AirtelMoney', 'Mobile Money (Airtel)'),
(6, 'OrangeMoney', 'Mobile Money (Orange)');

-- Méthodes de Valorisation de Stock
INSERT INTO methode_valorisation_stock (id, code, libelle, description) VALUES
(1, 'CMUP', 'Coût Moyen Unitaire Pondéré', 'Recalculé à chaque entrée'),
(2, 'FIFO', 'First In, First Out', 'Premier entré, premier sorti (PEPS)'),
(3, 'LIFO', 'Last In, First Out', 'Dernier entré, premier sorti (DEPS)');


-- 2. ACTEURS (ENTREPRISES & PERSONNELS)
-- =====================================

-- Entreprises
INSERT INTO entreprise (id, nom, type_entreprise, email, telephone, matricule_fiscal, est_actif) VALUES 
(1, 'TECH GROUP - SIÈGE CENTRAL', 'INTERNE', 'hq@techgroup.mg', '+261 34 00 000 01', 'NIF-001-HQ', true),
(2, 'TECH GROUP - SHOWROOM VILLE', 'INTERNE', 'shop@techgroup.mg', '+261 34 00 000 02', 'NIF-001-SH', true),
(3, 'SUPPLIER GLOBAL CHINA', 'FOURNISSEUR', 'export@china-tech.cn', '+86 000 000', 'EXT-CN-001', true),
(4, 'LOCAL PAPETERIE PRO', 'FOURNISSEUR', 'contact@papepro.mg', '+261 33 11 222 33', 'NIF-LOC-002', true),
(5, 'SOCIETE BIG CORP', 'CLIENT', 'achat@bigcorp.mg', '+261 32 00 999 88', 'NIF-CLI-999', true),
(6, 'CLIENT PASSAGE', 'CLIENT', 'N/A', 'N/A', NULL, true);

-- Personnels
-- 1. Super Admin
INSERT INTO personnel (id, code_employe, nom, prenom, email, mot_de_passe_hash, personnel_role_id, entreprise_id) VALUES 
(1, 'ADM001', 'SYSTEM', 'Admin', 'root@techgroup.mg', 'hash123', 1, 1);

-- 2. Staff Siège
INSERT INTO personnel (id, code_employe, nom, prenom, email, mot_de_passe_hash, personnel_role_id, entreprise_id) VALUES 
(2, 'HQ001', 'ANDRIAM', 'Hery', 'hery@techgroup.mg', 'hash123', 2, 1), -- Gérant
(3, 'HQ002', 'RAZAFY', 'Tina', 'tina@techgroup.mg', 'hash123', 5, 1); -- Logistique

-- 3. Staff Showroom
INSERT INTO personnel (id, code_employe, nom, prenom, email, mot_de_passe_hash, personnel_role_id, entreprise_id) VALUES 
(4, 'SH001', 'RABARY', 'Soa', 'soa@techgroup.mg', 'hash123', 3, 2), -- Vendeuse
(5, 'SH002', 'RANAIVO', 'Luc', 'luc@techgroup.mg', 'hash123', 3, 2); -- Vendeur


-- 3. CATALOGUE ARTICLES
-- =====================
INSERT INTO article (id, reference, designation, description, prix_achat_ref, prix_vente_ref, unite_id, article_categorie_id) VALUES
-- Hardware
(1, 'LAP-DELL-I5', 'Laptop Dell Vostro 15"', 'Core i5, 8GB, 256GB SSD', 1800000, 2400000, 1, 1),
(2, 'LAP-HP-RYZ', 'Laptop HP Ryzen 5', 'Ryzen 5, 16GB, 512GB SSD', 2000000, 2800000, 1, 1),
(3, 'MON-24-SAM', 'Ecran Samsung 24"', 'IPS, 75Hz, HDMI', 450000, 650000, 1, 1),
(4, 'PRT-EPSON', 'Imprimante Epson L3150', 'EcoTank, Wifi', 700000, 950000, 1, 1),
-- Consommables
(5, 'INK-BLK', 'Encre Epson Noire 003', 'Bouteille 65ml', 25000, 40000, 1, 2),
(6, 'PAP-A4', 'Papier A4 80g', 'Carton de 5 ramettes', 90000, 120000, 1, 2),
(7, 'KEY-USB', 'Clé USB 32GB', 'Kingston DataTraveler', 15000, 25000, 1, 2),
-- Services
(8, 'INST-WIN', 'Installation Windows + Office', 'Licence non incluse', 0, 50000, 1, 3),
(9, 'DIAG-PC', 'Diagnostic Réparation PC', 'Forfait main d''oeuvre', 0, 30000, 1, 3);


-- 4. INITIALISATION FINANCIÈRE (CAISSE)
-- =====================================
INSERT INTO caisse (id, code_caisse, libelle, solde_actuel, entreprise_id) VALUES 
(1, 'C-MAIN-HQ', 'Caisse Principale Siège', 100000000, 1),
(2, 'C-POS-01', 'Caisse Vente Showroom', 2000000, 2);

-- Capital de départ (Il y a 30 jours)
INSERT INTO caisse_mouvement (id, date_mouvement, libelle_operation, montant_entree, montant_sortie, solde_avant, solde_apres, caisse_id, personnel_id) VALUES
(1, NOW() - INTERVAL '30 days', 'Apport Capital Initial', 100000000, 0, 0, 100000000, 1, 1),
(2, NOW() - INTERVAL '30 days', 'Fond de caisse démarrage', 2000000, 0, 0, 2000000, 2, 2);


-- 5. INITIALISATION STOCK & LOTS (LE PLUS IMPORTANT POUR V3.2)
-- ============================================================

-- A. STOCK AU SIEGE (HUB LOGISTIQUE) - Valorisation CMUP par défaut
INSERT INTO stock (id, article_id, entreprise_id, methode_valorisation_stock_id, quantite_actuelle, cmup_actuel, valeur_stock_total) VALUES
(1, 1, 1, 1, 10, 1800000, 18000000), -- 10 Dell
(2, 2, 1, 1, 5, 2000000, 10000000),  -- 5 HP
(3, 4, 1, 1, 20, 700000, 14000000),  -- 20 Imprimantes
(4, 6, 1, 1, 100, 90000, 9000000);   -- 100 Cartons papier

-- B. STOCK AU SHOWROOM - Valorisation FIFO (Pour tester les lots)
INSERT INTO stock (id, article_id, entreprise_id, methode_valorisation_stock_id, quantite_actuelle, cmup_actuel, valeur_stock_total) VALUES
(5, 1, 2, 2, 2, 1800000, 3600000),   -- 2 Dell (FIFO)
(6, 7, 2, 2, 50, 15000, 750000);     -- 50 Clés USB (FIFO)

-- C. CRÉATION DES MOUVEMENTS INITIAUX (INVENTAIRE)
INSERT INTO mouvement_stock (id, date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, entreprise_id, personnel_id, reference_document) VALUES
-- Siège
(1, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 10, 0, 10, 1, 1, 1, 'INV-INIT'),
(2, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 5, 0, 5, 2, 1, 1, 'INV-INIT'),
(3, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 20, 0, 20, 4, 1, 1, 'INV-INIT'),
(4, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 100, 0, 100, 6, 1, 1, 'INV-INIT'),
-- Showroom
(5, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 2, 0, 2, 1, 2, 1, 'INV-INIT'),
(6, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 50, 0, 50, 7, 2, 1, 'INV-INIT');

-- D. CRÉATION DES LOTS (INDISPENSABLE MAINTENANT)
-- Chaque entrée de stock doit avoir un lot correspondant
INSERT INTO lot_stock (id, numero_lot, article_id, entreprise_id, date_entree, mouvement_entree_id, quantite_initiale, quantite_restante, prix_unitaire_achat, statut) VALUES
(1, 'LOT-INIT-DL-001', 1, 1, NOW() - INTERVAL '30 days', 1, 10, 10, 1800000, 'ACTIF'),
(2, 'LOT-INIT-HP-001', 2, 1, NOW() - INTERVAL '30 days', 2, 5, 5, 2000000, 'ACTIF'),
(3, 'LOT-INIT-PR-001', 4, 1, NOW() - INTERVAL '30 days', 3, 20, 20, 700000, 'ACTIF'),
(4, 'LOT-INIT-PA-001', 6, 1, NOW() - INTERVAL '30 days', 4, 100, 100, 90000, 'ACTIF'),
(5, 'LOT-INIT-DL-SH1', 1, 2, NOW() - INTERVAL '30 days', 5, 2, 2, 1800000, 'ACTIF'),
(6, 'LOT-INIT-US-SH1', 7, 2, NOW() - INTERVAL '30 days', 6, 50, 50, 15000, 'ACTIF');


-- 6. SCÉNARIO : APPROVISIONNEMENT (ACHAT FOURNISSEUR)
-- ===================================================
-- Il y a 15 jours, on achète 10 Ecrans Samsung au fournisseur LOCAL PAPETERIE

-- 1. Facture Achat
INSERT INTO facture_achat (id, numero_facture_fournisseur, date_facture, entreprise_fournisseur_id, entreprise_filiale_id, statut_id, montant_ttc, reste_a_payer) VALUES
(1, 'FAC-FRN-2023-88', NOW() - INTERVAL '15 days', 4, 1, 5, 4500000, 0); -- 10 * 450.000

-- 2. Détail Facture
INSERT INTO facture_achat_details (id, facture_achat_id, article_id, quantite, prix_unitaire) VALUES
(1, 1, 3, 10, 450000);

-- 3. Entrée Stock (Siège)
INSERT INTO stock (article_id, entreprise_id, methode_valorisation_stock_id, quantite_actuelle, cmup_actuel, valeur_stock_total) VALUES
(3, 1, 1, 10, 450000, 4500000); -- Nouvel article en stock

INSERT INTO mouvement_stock (id, date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, entreprise_id, personnel_id, reference_document) VALUES
(7, NOW() - INTERVAL '15 days', 'ACHAT', 0, 10, 0, 10, 3, 1, 3, 'FAC-FRN-2023-88');

-- 4. Création du Lot
INSERT INTO lot_stock (numero_lot, article_id, entreprise_id, date_entree, mouvement_entree_id, quantite_initiale, quantite_restante, prix_unitaire_achat) VALUES
('LOT-ACH-ECR-001', 3, 1, NOW() - INTERVAL '15 days', 7, 10, 10, 450000);

-- 5. Paiement (Décaissement)
INSERT INTO caisse_mouvement (id, date_mouvement, libelle_operation, montant_entree, montant_sortie, solde_avant, solde_apres, caisse_id, personnel_id) VALUES
(3, NOW() - INTERVAL '15 days', 'Paiement Facture Ecrans', 0, 4500000, 100000000, 95500000, 1, 2);
UPDATE caisse SET solde_actuel = 95500000 WHERE id = 1;

-- 6. Enregistrement Paiement et Détails (Mixte : Chèque + Espèce)
INSERT INTO paiement_achat (id, numero_paiement, facture_achat_id, caisse_mouvement_id, montant_total_paye) VALUES
(1, 'PAY-ACH-001', 1, 3, 4500000);

INSERT INTO paiement_achat_details (paiement_achat_id, mode_paiement_id, montant, reference_externe) VALUES
(1, 2, 4000000, 'CHQ-BNI-009988'), -- 4M en Chèque
(1, 1, 500000, NULL);              -- 500k en Espèces


-- 7. SCÉNARIO : VENTE AU SHOWROOM (B2C)
-- =====================================
-- Hier, un client achète 1 Laptop Dell + 1 Clé USB

-- 1. Facture Vente
INSERT INTO facture_vente (id, numero_facture, date_facture, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, reste_a_payer) VALUES
(1, 'FV-SH-23001', NOW() - INTERVAL '1 day', 6, 2, 4, 5, 2425000, 0);

-- 2. Détails Facture
INSERT INTO facture_vente_details (id, facture_vente_id, article_id, quantite, prix_unitaire) VALUES
(1, 1, 1, 1, 2400000), -- 1 Laptop
(2, 1, 7, 1, 25000);   -- 1 Clé USB

-- 3. Sortie Stock & Mouvements (Showroom)
-- Laptop : Stock Avant 2 -> Après 1
UPDATE stock SET quantite_actuelle = 1 WHERE id = 5; 
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, entreprise_id, personnel_id, reference_document) VALUES
(NOW() - INTERVAL '1 day', 'VENTE', 2, 0, 1, 1, 1, 2, 4, 'FV-SH-23001');

-- Clé USB : Stock Avant 50 -> Après 49
UPDATE stock SET quantite_actuelle = 49 WHERE id = 6;
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, entreprise_id, personnel_id, reference_document) VALUES
(NOW() - INTERVAL '1 day', 'VENTE', 50, 0, 1, 49, 7, 2, 4, 'FV-SH-23001');

-- 4. Gestion des Lots (Sortie FIFO)
-- On tape dans le lot LOT-INIT-DL-SH1 pour le Laptop
UPDATE lot_stock SET quantite_restante = 1 WHERE numero_lot = 'LOT-INIT-DL-SH1';
-- On tape dans le lot LOT-INIT-US-SH1 pour l'USB
UPDATE lot_stock SET quantite_restante = 49 WHERE numero_lot = 'LOT-INIT-US-SH1';

-- 5. Encaissement (Entrée Caisse)
INSERT INTO caisse_mouvement (id, date_mouvement, libelle_operation, montant_entree, montant_sortie, solde_avant, solde_apres, caisse_id, personnel_id) VALUES
(4, NOW() - INTERVAL '1 day', 'Vente Client Comptoir', 2425000, 0, 2000000, 4425000, 2, 4);
UPDATE caisse SET solde_actuel = 4425000 WHERE id = 2;

-- 6. Enregistrement Paiement (Mobile Money)
INSERT INTO paiement_vente (id, numero_recu, facture_vente_id, caisse_mouvement_id, montant_total_paye) VALUES
(1, 'REC-001', 1, 4, 2425000);

INSERT INTO paiement_vente_details (paiement_vente_id, mode_paiement_id, montant, reference_externe) VALUES
(1, 4, 2425000, 'TRANS-ID-88887777'); -- MVola


-- 8. SCÉNARIO : GROSSE VENTE B2B (CLIENT BIG CORP)
-- ================================================
-- Aujourd'hui, on vend 5 Laptops Dell au client BIG CORP depuis le siège.

-- 1. Bon de Commande Vente
INSERT INTO bon_commande_vente (id, numero_bc, date_commande, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc) VALUES
(1, 'BCV-HQ-009', NOW(), 5, 1, 2, 3, 12000000); -- 5 * 2.400.000

INSERT INTO bon_commande_vente_details (id, bon_commande_vente_id, article_id, quantite, prix_unitaire) VALUES
(1, 1, 1, 5, 2400000);

-- 2. Facture Vente (Non payée encore)
INSERT INTO facture_vente (id, numero_facture, date_facture, bon_commande_vente_id, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, reste_a_payer) VALUES
(2, 'FV-HQ-23050', NOW(), 1, 5, 1, 2, 3, 12000000, 12000000);

INSERT INTO facture_vente_details (id, facture_vente_id, article_id, quantite, prix_unitaire) VALUES
(3, 2, 1, 5, 2400000);

-- 3. Sortie Stock Siège
-- Stock Avant 10 -> Après 5
UPDATE stock SET quantite_actuelle = 5 WHERE id = 1;
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, entreprise_id, personnel_id, reference_document) VALUES
(NOW(), 'VENTE', 10, 0, 5, 5, 1, 1, 2, 'FV-HQ-23050');

-- 4. Mise à jour Lot (Siège)
UPDATE lot_stock SET quantite_restante = 5 WHERE numero_lot = 'LOT-INIT-DL-001';


-- 9. RESET DES SÉQUENCES (POUR QUE TES PROCHAINS INSERTS MARCHENT)
-- ================================================================
SELECT setval('statut_id_seq', (SELECT MAX(id) FROM statut));
SELECT setval('unite_id_seq', (SELECT MAX(id) FROM unite));
SELECT setval('article_categorie_id_seq', (SELECT MAX(id) FROM article_categorie));
SELECT setval('personnel_role_id_seq', (SELECT MAX(id) FROM personnel_role));
SELECT setval('mode_paiement_id_seq', (SELECT MAX(id) FROM mode_paiement));
SELECT setval('methode_valorisation_stock_id_seq', (SELECT MAX(id) FROM methode_valorisation_stock));
SELECT setval('entreprise_id_seq', (SELECT MAX(id) FROM entreprise));
SELECT setval('personnel_id_seq', (SELECT MAX(id) FROM personnel));
SELECT setval('article_id_seq', (SELECT MAX(id) FROM article));
SELECT setval('caisse_id_seq', (SELECT MAX(id) FROM caisse));
SELECT setval('stock_id_seq', (SELECT MAX(id) FROM stock));
SELECT setval('mouvement_stock_id_seq', (SELECT MAX(id) FROM mouvement_stock));
SELECT setval('lot_stock_id_seq', (SELECT MAX(id) FROM lot_stock));
SELECT setval('caisse_mouvement_id_seq', (SELECT MAX(id) FROM caisse_mouvement));
SELECT setval('facture_achat_id_seq', (SELECT MAX(id) FROM facture_achat));
SELECT setval('facture_achat_details_id_seq', (SELECT MAX(id) FROM facture_achat_details));
SELECT setval('paiement_achat_id_seq', (SELECT MAX(id) FROM paiement_achat));
SELECT setval('facture_vente_id_seq', (SELECT MAX(id) FROM facture_vente));
SELECT setval('facture_vente_details_id_seq', (SELECT MAX(id) FROM facture_vente_details));
SELECT setval('paiement_vente_id_seq', (SELECT MAX(id) FROM paiement_vente));
SELECT setval('bon_commande_vente_id_seq', (SELECT MAX(id) FROM bon_commande_vente));