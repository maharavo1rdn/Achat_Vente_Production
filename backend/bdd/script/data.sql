-- ==============================================================================
-- FICHIER : DATA.SQL
-- Jeu de données de test complet (Scénario Multi-Filiales)
-- ==============================================================================

-- 1. DONNÉES DE RÉFÉRENCE
-- =======================

-- Statuts
INSERT INTO statut (id, code, libelle, niveau) VALUES 
(1, 'BROUILLON', 'Brouillon', 0),
(2, 'EN_ATTENTE', 'En attente de validation', 1),
(3, 'VALIDE', 'Validé / Confirmé', 2),
(4, 'LIVRE', 'Livré', 3),
(5, 'PAYE', 'Payé intégralement', 4),
(6, 'ANNULE', 'Annulé', 99);

-- Unités
INSERT INTO unite (id, code, libelle) VALUES 
(1, 'U', 'Unité'),
(2, 'KG', 'Kilogramme'),
(3, 'L', 'Litre'),
(4, 'M', 'Mètre');

-- Catégories Articles
INSERT INTO article_categorie (id, code, libelle) VALUES 
(1, 'INFO', 'Matériel Informatique'),
(2, 'BUR', 'Fournitures de Bureau'),
(3, 'SERV', 'Services & Prestations');

-- Rôles Personnel
INSERT INTO personnel_role (id, code, libelle, niveau_acces) VALUES 
(1, 'ADMIN', 'Administrateur Système', 10),
(2, 'GERANT', 'Gérant de Filiale', 5),
(3, 'VENDEUR', 'Commercial / Vendeur', 2),
(4, 'MAGASINIER', 'Responsable Stock', 2);

-- 2. ACTEURS (ENTREPRISES & PERSONNELS)
-- =====================================

-- Entreprises (Filiales et Tiers)
INSERT INTO entreprise (id, nom, type_entreprise, email, telephone, est_actif) VALUES 
(1, 'TECH GROUP - SIÈGE', 'INTERNE', 'contact@techgroup.com', '034 00 000 00', true),
(2, 'TECH GROUP - MAGASIN VILLE', 'INTERNE', 'ville@techgroup.com', '034 11 111 11', true),
(3, 'GLOBAL IMPORT SAS', 'FOURNISSEUR', 'sales@globalimport.com', '020 22 222 22', true),
(4, 'CLIENT BETA SARL', 'CLIENT', 'achat@clientbeta.com', '033 33 333 33', true);

-- Personnel
-- Super Admin (Lié à aucune filiale spécifique ou au siège)
INSERT INTO personnel (id, code_employe, nom, prenom, email, mot_de_passe_hash, personnel_role_id, entreprise_id) VALUES 
(1, 'ADM01', 'SYSTEM', 'Admin', 'admin@sys.com', 'hash123', 1, 1);

-- Gérant Siège
INSERT INTO personnel (id, code_employe, nom, prenom, email, mot_de_passe_hash, personnel_role_id, entreprise_id) VALUES 
(2, 'EMP01', 'RAKOTO', 'Jean', 'jean@techgroup.com', 'hash123', 2, 1);

-- Vendeur Magasin Ville
INSERT INTO personnel (id, code_employe, nom, prenom, email, mot_de_passe_hash, personnel_role_id, entreprise_id) VALUES 
(3, 'EMP02', 'RABARY', 'Paul', 'paul@techgroup.com', 'hash123', 3, 2);

-- 3. CATALOGUE ARTICLES
-- =====================
INSERT INTO article (id, reference, designation, description, prix_achat_ref, prix_vente_ref, unite_id, article_categorie_id) VALUES
(1, 'PC-DELL-01', 'Laptop Dell Latitude 15"', 'Intel i5, 8GB RAM, 256GB SSD', 1500000, 2200000, 1, 1),
(2, 'IMP-HP-01', 'Imprimante HP Laser', 'Monochrome, Réseau', 800000, 1100000, 1, 1),
(3, 'PAP-A4', 'Papier A4 80g (Carton)', 'Carton de 5 ramettes', 60000, 85000, 1, 2),
(4, 'ART004', 'Ordinateur Portable HP', 'Ordinateur portable professionnel', 800000, 1100000, 1, 1),
(5, 'ART005', 'Imprimante Jet d''Encre', 'Imprimante multifonction couleur', 150000, 250000, 1, 1),
(6, 'ART006', 'Clavier USB', 'Clavier bureautique filaire', 5000, 10000, 1, 1),
(7, 'ART007', 'Souris Optique', 'Souris filaire ergonomique', 3000, 6000, 1, 1),
(8, 'ART008', 'Écran 24"', 'Moniteur LED 24 pouces Full HD', 120000, 200000, 1, 1),
(9, 'ART009', 'Ram 8GB', 'Barrette mémoire DDR4 8GB', 15000, 25000, 1, 1),
(10, 'ART010', 'Disque Dur 1TB', 'Disque dur interne SATA 1TB', 40000, 65000, 1, 1),
(11, 'ART011', 'Carte Graphique', 'Carte graphique gaming', 200000, 320000, 1, 1),
(12, 'ART012', 'Stylos', 'Boîte de 50 stylos bille', 1000, 2000, 1, 2),
(13, 'ART013', 'Ordinateur Portable Dell XPS 13', 'Ordinateur portable professionnel 13 pouces', 850000, 1200000, 1, 1),
(14, 'ART014', 'Imprimante Laser HP Color', 'Imprimante couleur multifonction laser', 450000, 650000, 1, 1),
(15, 'ART015', 'Clavier mécanique RGB', 'Clavier gaming mécanique avec éclairage RGB', 25000, 45000, 1, 1),
(16, 'ART016', 'Souris optique sans fil', 'Souris ergonomique sans fil rechargeable', 8000, 15000, 1, 1),
(17, 'ART017', 'Écran 27" 4K UHD', 'Moniteur professionnel 27 pouces 4K', 320000, 480000, 1, 1),
(18, 'ART018', 'Ram 16GB DDR4', 'Barrette mémoire DDR4 16GB 3200MHz', 35000, 55000, 1, 1),
(19, 'ART019', 'SSD NVMe 1TB', 'Disque SSD NVMe haute performance 1TB', 120000, 180000, 1, 1),
(20, 'ART020', 'Carte graphique RTX 3060', 'Carte graphique NVIDIA RTX 3060 12GB', 280000, 420000, 1, 1),
(21, 'ART021', 'Papier A4 80g', 'Ramette de papier A4 80g - 500 feuilles', 2500, 4500, 1, 2),
(22, 'ART022', 'Stylos bille bleus', 'Boîte de 50 stylos bille bleus', 1500, 3000, 1, 2),
(23, 'ART023', 'Chemises cartonnées A4', 'Boîte de 100 chemises cartonnées A4', 8000, 15000, 1, 2),
(24, 'ART024', 'Marqueurs permanents', 'Set de 12 marqueurs permanents de couleurs', 3500, 7000, 1, 2),
(25, 'ART025', 'Agrafeuse électrique', 'Agrafeuse électrique professionnelle', 15000, 28000, 1, 2),
(26, 'ART026', 'Calculatrice scientifique', 'Calculatrice scientifique programmable', 12000, 22000, 1, 2),
(27, 'ART027', 'Formation Excel Avancé', 'Formation de 2 jours sur Excel avancé', 150000, 250000, 1, 3),
(28, 'ART028', 'Maintenance informatique', 'Contrat de maintenance annuel', 500000, 750000, 1, 3),
(29, 'ART029', 'Développement logiciel', 'Service de développement sur mesure', 1000000, 1500000, 1, 3),
(30, 'ART030', 'Audit sécurité', 'Audit de sécurité informatique complet', 300000, 450000, 1, 3),
(31, 'ART031', 'Hébergement web', 'Hébergement mutualisé 1 an', 80000, 120000, 1, 3),
(32, 'ART032', 'Formation bureautique', 'Formation complète bureautique 5 jours', 200000, 350000, 1, 3);


-- 4. INITIALISATION DES CAISSES (ARGENT DE DÉPART)
-- ================================================
INSERT INTO caisse (id, code_caisse, libelle, solde_actuel, entreprise_id) VALUES 
(1, 'CS-SIEGE', 'Caisse Principale Siège', 50000000, 1),
(2, 'CS-MAG-01', 'Caisse Boutique Ville', 1000000, 2);

-- Mouvement initial (Apport capital)
INSERT INTO caisse_mouvement (id, caisse_id, personnel_id, date_mouvement, libelle_operation, montant_entree, montant_sortie, solde_avant, solde_apres) VALUES 
(1, 1, 1, NOW() - INTERVAL '30 days', 'Apport initial Capital', 50000000, 0, 0, 50000000),
(2, 2, 1, NOW() - INTERVAL '30 days', 'Fonds de caisse boutique', 1000000, 0, 0, 1000000);


-- 5. INITIALISATION STOCK (INVENTAIRE DE DÉPART)
-- ==============================================

-- Stock au Siège (Filiale 1)
INSERT INTO stock (id, article_id, entreprise_id, quantite_actuelle) VALUES
(1, 1, 1, 10), -- 10 PC Dell au siège
(2, 2, 1, 5),  -- 5 Imprimantes HP
(3, 3, 1, 100), -- 100 Cartons papier
(4, 4, 1, 8),  -- 8 PC HP
(5, 5, 1, 12), -- 12 Imprimantes Jet d'encre
(6, 6, 1, 25), -- 25 Claviers USB
(7, 7, 1, 30), -- 30 Souris optiques
(8, 8, 1, 6),  -- 6 Écrans 24"
(9, 9, 1, 15), -- 15 Ram 8GB
(10, 10, 1, 8), -- 8 Disques durs 1TB
(11, 11, 1, 3), -- 3 Cartes graphiques
(12, 12, 1, 50), -- 50 Boîtes de stylos
(13, 13, 1, 5), -- 5 PC Dell XPS 13
(14, 14, 1, 4), -- 4 Imprimantes Laser HP Color
(15, 15, 1, 12), -- 12 Claviers mécaniques RGB
(16, 16, 1, 20), -- 20 Souris sans fil
(17, 17, 1, 3), -- 3 Écrans 27" 4K
(18, 18, 1, 10), -- 10 Ram 16GB
(19, 19, 1, 6), -- 6 SSD NVMe 1TB
(20, 20, 1, 2), -- 2 Cartes RTX 3060
(21, 21, 1, 200), -- 200 Ramettes papier A4
(22, 22, 1, 80), -- 80 Boîtes stylos bleus
(23, 23, 1, 15), -- 15 Boîtes chemises cartonnées
(24, 24, 1, 25), -- 25 Sets marqueurs
(25, 25, 1, 8), -- 8 Agrafeuses électriques
(26, 26, 1, 12), -- 12 Calculatrices scientifiques
(27, 27, 1, 0), -- 0 Formation Excel (service)
(28, 28, 1, 0), -- 0 Maintenance (service)
(29, 29, 1, 0), -- 0 Développement (service)
(30, 30, 1, 0), -- 0 Audit sécurité (service)
(31, 31, 1, 0), -- 0 Hébergement web (service)
(32, 32, 1, 0); -- 0 Formation bureautique (service)

-- Stock à la Boutique Ville (Filiale 2)
INSERT INTO stock (id, article_id, entreprise_id, quantite_actuelle) VALUES
(33, 4, 2, 3),  -- 3 PC HP en boutique
(34, 5, 2, 2),  -- 2 Imprimantes Jet d'encre
(35, 6, 2, 8),  -- 8 Claviers USB
(36, 7, 2, 12), -- 12 Souris optiques
(37, 8, 2, 2),  -- 2 Écrans 24"
(38, 12, 2, 20), -- 20 Boîtes de stylos
(39, 21, 2, 50), -- 50 Ramettes papier A4
(40, 22, 2, 30), -- 30 Boîtes stylos bleus
(41, 23, 2, 5),  -- 5 Boîtes chemises cartonnées
(42, 24, 2, 10), -- 10 Sets marqueurs
(43, 25, 2, 3),  -- 3 Agrafeuses électriques
(44, 26, 2, 5);  -- 5 Calculatrices scientifiques

-- Historique Mouvements (Inventaire Initial)
INSERT INTO mouvement_stock (id, date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, entreprise_id, personnel_id, reference_document) VALUES 
(1, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 10, 0, 10, 1, 1, 1, 'INV-INIT-001'),
(2, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 5, 0, 5, 2, 1, 1, 'INV-INIT-001'),
(3, NOW() - INTERVAL '30 days', 'INVENTAIRE', 0, 100, 0, 100, 3, 1, 1, 'INV-INIT-001');

-- Stock au Magasin (Filiale 2) - Vide au départ
INSERT INTO stock (id, article_id, entreprise_id, quantite_actuelle) VALUES 
(4, 1, 2, 0);


-- 6. SCÉNARIO ACHAT (Le Siège achète au Fournisseur)
-- ==================================================

-- A. Bon de Commande
INSERT INTO bon_commande_achat (id, numero_bc, date_commande, entreprise_fournisseur_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc) VALUES 
(1, 'BC-2023-001', NOW() - INTERVAL '10 days', 3, 1, 2, 3, 3000000); -- 2 PC à 1.5M

INSERT INTO bon_commande_achat_details (id, bon_commande_achat_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 1, 2, 1500000);

-- B. Réception Facture & Marchandise
INSERT INTO facture_achat (id, numero_facture_fournisseur, date_facture, bon_commande_achat_id, entreprise_fournisseur_id, entreprise_filiale_id, statut_id, montant_ttc, reste_a_payer) VALUES 
(1, 'FAC-FRN-999', NOW() - INTERVAL '8 days', 1, 3, 1, 5, 3000000, 0);

-- Mise à jour Stock Siège (+2 PC)
UPDATE stock SET quantite_actuelle = 12, date_maj = NOW() WHERE id = 1;

-- Trace Mouvement Stock (Entrée Achat)
INSERT INTO mouvement_stock (id, date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, entreprise_id, personnel_id, reference_document) VALUES 
(4, NOW() - INTERVAL '8 days', 'ACHAT', 10, 2, 0, 12, 1, 1, 2, 'FAC-FRN-999');

-- C. Paiement Facture Achat (Sortie d'argent Siège)
INSERT INTO caisse_mouvement (id, caisse_id, personnel_id, date_mouvement, libelle_operation, montant_entree, montant_sortie, solde_avant, solde_apres) VALUES 
(3, 1, 2, NOW() - INTERVAL '8 days', 'Paiement Facture FAC-FRN-999', 0, 3000000, 50000000, 47000000);

UPDATE caisse SET solde_actuel = 47000000 WHERE id = 1;

INSERT INTO paiement_achat (id, facture_achat_id, caisse_mouvement_id, montant_paye) VALUES 
(1, 1, 3, 3000000);


-- 7. SCÉNARIO TRANSFERT (Siège envoie au Magasin)
-- ===============================================
-- Transfert de 2 PC du Siège vers Magasin

-- Sortie Siège
UPDATE stock SET quantite_actuelle = 10 WHERE id = 1;
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, entreprise_id, personnel_id, reference_document) VALUES 
(NOW() - INTERVAL '5 days', 'TRANSFERT_SORTIE', 12, 0, 2, 10, 1, 1, 2, 'TRF-001');

-- Entrée Magasin
UPDATE stock SET quantite_actuelle = 2 WHERE id = 4;
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, entreprise_id, personnel_id, reference_document) VALUES
(NOW() - INTERVAL '5 days', 'TRANSFERT_ENTREE', 0, 2, 0, 2, 1, 2, 3, 'TRF-001');


-- 8. DONNÉES SUPPLÉMENTAIRES POUR TESTS
-- =====================================

-- Plus de mouvements de stock récents
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, entreprise_id, personnel_id, reference_document) VALUES
(NOW() - INTERVAL '3 days', 'VENTE', 25, 0, 3, 22, 6, 1, 2, 'CMD-V-001'),
(NOW() - INTERVAL '2 days', 'VENTE', 30, 0, 5, 25, 7, 1, 2, 'CMD-V-002'),
(NOW() - INTERVAL '1 day', 'ACHAT', 8, 4, 0, 12, 8, 1, 2, 'CMD-A-003'),
(NOW() - INTERVAL '12 hours', 'VENTE', 20, 0, 2, 18, 16, 1, 3, 'CMD-V-004');

-- Mise à jour des stocks correspondants
UPDATE stock SET quantite_actuelle = 22 WHERE id = 6; -- Claviers
UPDATE stock SET quantite_actuelle = 25 WHERE id = 7; -- Souris
UPDATE stock SET quantite_actuelle = 12 WHERE id = 8; -- Écrans 24"
UPDATE stock SET quantite_actuelle = 18 WHERE id = 16; -- Souris sans fil

-- Quelques mouvements de caisse supplémentaires
INSERT INTO caisse_mouvement (caisse_id, personnel_id, date_mouvement, libelle_operation, montant_entree, montant_sortie, solde_avant, solde_apres) VALUES
(1, 2, NOW() - INTERVAL '3 days', 'Vente PC et accessoires', 3300000, 0, 47000000, 50300000),
(1, 2, NOW() - INTERVAL '2 days', 'Vente fournitures bureau', 125000, 0, 50300000, 50425000),
(2, 3, NOW() - INTERVAL '1 day', 'Vente boutique', 450000, 0, 550000, 1000000),
(1, 2, NOW() - INTERVAL '12 hours', 'Achat écrans 24"', 0, 800000, 50425000, 49625000);

-- Mise à jour soldes caisses
UPDATE caisse SET solde_actuel = 49625000 WHERE id = 1;
UPDATE caisse SET solde_actuel = 1000000 WHERE id = 2;


-- 8. SCÉNARIO VENTE (Le Magasin vend au Client)
-- =============================================

-- A. Bon de Commande Vente
INSERT INTO bon_commande_vente (id, numero_bc, date_commande, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc) VALUES 
(1, 'BCV-2023-050', NOW() - INTERVAL '2 days', 4, 2, 3, 3, 2200000);

INSERT INTO bon_commande_vente_details (id, bon_commande_vente_id, article_id, quantite, prix_unitaire) VALUES 
(1, 1, 1, 1, 2200000);

-- B. Facture Vente & Sortie Stock
INSERT INTO facture_vente (id, numero_facture, date_facture, bon_commande_vente_id, entreprise_client_id, entreprise_filiale_id, personnel_id, statut_id, montant_ttc, reste_a_payer) VALUES 
(1, 'FV-23-001', NOW(), 1, 4, 2, 3, 3, 2200000, 0); -- Payé direct

-- Sortie Stock Magasin (-1 PC)
UPDATE stock SET quantite_actuelle = 1 WHERE id = 4;

-- Trace Mouvement Stock (Sortie Vente)
INSERT INTO mouvement_stock (date_mouvement, type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie, quantite_stock_apres, article_id, entreprise_id, personnel_id, reference_document) VALUES 
(NOW(), 'VENTE', 2, 0, 1, 1, 1, 2, 3, 'FV-23-001');

-- C. Encaissement (Entrée argent Magasin)
INSERT INTO caisse_mouvement (id, caisse_id, personnel_id, date_mouvement, libelle_operation, montant_entree, montant_sortie, solde_avant, solde_apres) VALUES 
(4, 2, 3, NOW(), 'Encaissement Facture FV-23-001', 2200000, 0, 1000000, 3200000);

UPDATE caisse SET solde_actuel = 3200000 WHERE id = 2;

INSERT INTO paiement_vente (id, facture_vente_id, caisse_mouvement_id, montant_recu) VALUES 
(1, 1, 4, 2200000);

-- 9. REINITIALISATION DES SEQUENCES (IMPORTANT POUR QUE LES PROCHAINS INSERTS MARCHENT)
-- =====================================================================================
SELECT setval('entreprise_id_seq', (SELECT MAX(id) FROM entreprise));
SELECT setval('personnel_id_seq', (SELECT MAX(id) FROM personnel));
SELECT setval('article_id_seq', (SELECT MAX(id) FROM article));
SELECT setval('stock_id_seq', (SELECT MAX(id) FROM stock));
SELECT setval('caisse_id_seq', (SELECT MAX(id) FROM caisse));
SELECT setval('caisse_mouvement_id_seq', (SELECT MAX(id) FROM caisse_mouvement));
SELECT setval('mouvement_stock_id_seq', (SELECT MAX(id) FROM mouvement_stock));
SELECT setval('bon_commande_achat_id_seq', (SELECT MAX(id) FROM bon_commande_achat));
SELECT setval('facture_achat_id_seq', (SELECT MAX(id) FROM facture_achat));
SELECT setval('bon_commande_vente_id_seq', (SELECT MAX(id) FROM bon_commande_vente));
SELECT setval('facture_vente_id_seq', (SELECT MAX(id) FROM facture_vente));