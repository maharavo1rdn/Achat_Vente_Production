-- ==============================================================================
-- FICHIER 1 : SCHEMA_STRUCTURE.SQL
-- Architecture ERP V3.0 (Avec gestion Entrée/Sortie explicite)
-- ==============================================================================

\c postgres;
DROP DATABASE IF EXISTS achat_vente_db;
CREATE DATABASE achat_vente_db;
\c achat_vente_db;

-- 1. NETTOYAGE (Ordre inverse des dépendances pour éviter les erreurs)
DROP TABLE IF EXISTS paiement_vente CASCADE;
DROP TABLE IF EXISTS paiement_achat CASCADE;
DROP TABLE IF EXISTS caisse_mouvement CASCADE;
DROP TABLE IF EXISTS caisse CASCADE;
DROP TABLE IF EXISTS facture_vente_details CASCADE;
DROP TABLE IF EXISTS facture_vente CASCADE;
DROP TABLE IF EXISTS facture_achat_details CASCADE;
DROP TABLE IF EXISTS facture_achat CASCADE;
DROP TABLE IF EXISTS bon_commande_vente_details CASCADE;
DROP TABLE IF EXISTS bon_commande_vente CASCADE;
DROP TABLE IF EXISTS bon_commande_achat_details CASCADE;
DROP TABLE IF EXISTS bon_commande_achat CASCADE;
DROP TABLE IF EXISTS devis_vente_details CASCADE;
DROP TABLE IF EXISTS devis_vente CASCADE;
DROP TABLE IF EXISTS proforma_fournisseur_details CASCADE;
DROP TABLE IF EXISTS proforma_fournisseur CASCADE;
DROP TABLE IF EXISTS mouvement_stock CASCADE;
DROP TABLE IF EXISTS stock CASCADE;
DROP TABLE IF EXISTS article CASCADE;
DROP TABLE IF EXISTS article_categorie CASCADE;
DROP TABLE IF EXISTS unite CASCADE;
DROP TABLE IF EXISTS personnel CASCADE;
DROP TABLE IF EXISTS personnel_role CASCADE;
DROP TABLE IF EXISTS entreprise CASCADE;
DROP TABLE IF EXISTS statut CASCADE;

-- ==========================================
-- 2. TABLES DE RÉFÉRENCE (PARAMÈTRES)
-- ==========================================

CREATE TABLE statut (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL, -- BROUILLON, VALIDE, PAYE, LIVRE
    libelle VARCHAR(100) NOT NULL,
    niveau INTEGER DEFAULT 0
);

CREATE TABLE unite (
    id SERIAL PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL, -- KG, L, M, U
    libelle VARCHAR(50)
);

CREATE TABLE article_categorie (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    libelle VARCHAR(100) NOT NULL
);

CREATE TABLE personnel_role (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL, -- ADMIN, VENDEUR, MAGASINIER
    libelle VARCHAR(100) NOT NULL,
    niveau_acces INTEGER DEFAULT 1
);

-- ==========================================
-- 3. ACTEURS (ENTREPRISES & PERSONNEL)
-- ==========================================

CREATE TABLE entreprise (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    -- INTERNE = Une de tes filiales. CLIENT/FOURNISSEUR = Tiers externe.
    type_entreprise VARCHAR(20) NOT NULL CHECK (type_entreprise IN ('CLIENT', 'FOURNISSEUR', 'INTERNE', 'PARTENAIRE')),
    matricule_fiscal VARCHAR(100),
    adresse VARCHAR(200),
    telephone VARCHAR(50),
    email VARCHAR(100),
    est_actif BOOLEAN DEFAULT true,
    date_creation TIMESTAMP DEFAULT NOW()
);

CREATE TABLE personnel (
    id SERIAL PRIMARY KEY,
    code_employe VARCHAR(20) UNIQUE, -- Matricule RH
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100),
    email VARCHAR(150) UNIQUE,
    mot_de_passe_hash VARCHAR(255),
    telephone VARCHAR(50),
    est_actif BOOLEAN DEFAULT true,
    personnel_role_id INTEGER NOT NULL,
    entreprise_id INTEGER, -- A quelle filiale appartient cet employé ?
    FOREIGN KEY (personnel_role_id) REFERENCES personnel_role(id),
    FOREIGN KEY (entreprise_id) REFERENCES entreprise(id)
);

-- ==========================================
-- 4. PRODUITS & STOCKS
-- ==========================================

CREATE TABLE article (
    id SERIAL PRIMARY KEY,
    reference VARCHAR(50) UNIQUE NOT NULL,
    designation VARCHAR(200) NOT NULL,
    description TEXT,
    prix_achat_ref NUMERIC(15,2) DEFAULT 0,
    prix_vente_ref NUMERIC(15,2) DEFAULT 0,
    taux_tva NUMERIC(5,2) DEFAULT 20.00,
    est_actif BOOLEAN DEFAULT true,
    unite_id INTEGER NOT NULL,
    article_categorie_id INTEGER NOT NULL,
    FOREIGN KEY (unite_id) REFERENCES unite(id),
    FOREIGN KEY (article_categorie_id) REFERENCES article_categorie(id)
);

-- Table de l'état actuel du stock (Une ligne par article et par filiale)
CREATE TABLE stock (
    id SERIAL PRIMARY KEY,
    article_id INTEGER NOT NULL,
    entreprise_id INTEGER NOT NULL, -- Stock de la Filiale A, B...
    quantite_actuelle NUMERIC(15,2) DEFAULT 0,
    date_maj TIMESTAMP DEFAULT NOW(),
    UNIQUE(article_id, entreprise_id), -- Un article n'apparaît qu'une fois par filiale
    FOREIGN KEY (article_id) REFERENCES article(id),
    FOREIGN KEY (entreprise_id) REFERENCES entreprise(id)
);

-- Journal des mouvements (Historique complet Entrée/Sortie)
CREATE TABLE mouvement_stock (
    id SERIAL PRIMARY KEY,
    date_mouvement TIMESTAMP DEFAULT NOW(),
    type_mouvement VARCHAR(20) NOT NULL, -- ACHAT, VENTE, INVENTAIRE, TRANSFERT
    
    -- Colonnes demandées pour le suivi comptable matière
    quantite_stock_avant NUMERIC(15,2) NOT NULL DEFAULT 0,
    quantite_entree NUMERIC(15,2) DEFAULT 0, -- Rempli si c'est une entrée (sinon 0)
    quantite_sortie NUMERIC(15,2) DEFAULT 0, -- Rempli si c'est une sortie (sinon 0)
    quantite_stock_apres NUMERIC(15,2) NOT NULL DEFAULT 0,
    
    prix_unitaire_mouvement NUMERIC(15,2), -- Valeur du stock à ce moment
    
    article_id INTEGER NOT NULL,
    entreprise_id INTEGER NOT NULL, -- Filiale concernée
    personnel_id INTEGER NOT NULL, -- Qui a fait le mouvement
    reference_document VARCHAR(100), -- Numéro BL, Facture...
    
    FOREIGN KEY (article_id) REFERENCES article(id),
    FOREIGN KEY (entreprise_id) REFERENCES entreprise(id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id)
);

-- ==========================================
-- 5. PROCESSUS ACHAT (FOURNISSEURS)
-- ==========================================

-- Etape 1: Proforma / Devis reçu du fournisseur
CREATE TABLE proforma_fournisseur (
    id SERIAL PRIMARY KEY,
    numero_proforma VARCHAR(50) NOT NULL,
    date_emission DATE DEFAULT CURRENT_DATE,
    date_validite DATE,
    entreprise_fournisseur_id INTEGER NOT NULL,
    entreprise_filiale_id INTEGER NOT NULL, -- Nous (l'acheteur)
    personnel_id INTEGER NOT NULL, -- Qui a saisi
    statut_id INTEGER NOT NULL,
    montant_ht NUMERIC(15,2) DEFAULT 0,
    montant_ttc NUMERIC(15,2) DEFAULT 0,
    FOREIGN KEY (entreprise_fournisseur_id) REFERENCES entreprise(id),
    FOREIGN KEY (entreprise_filiale_id) REFERENCES entreprise(id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (statut_id) REFERENCES statut(id)
);

CREATE TABLE proforma_fournisseur_details (
    id SERIAL PRIMARY KEY,
    proforma_fournisseur_id INTEGER NOT NULL,
    article_id INTEGER NOT NULL,
    quantite NUMERIC(15,2) NOT NULL,
    prix_unitaire NUMERIC(15,2) NOT NULL,
    FOREIGN KEY (proforma_fournisseur_id) REFERENCES proforma_fournisseur(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES article(id)
);

-- Etape 2: Bon de Commande (Validé)
CREATE TABLE bon_commande_achat (
    id SERIAL PRIMARY KEY,
    numero_bc VARCHAR(50) UNIQUE NOT NULL,
    date_commande DATE DEFAULT CURRENT_DATE,
    proforma_fournisseur_id INTEGER, -- Lien vers le proforma origine
    entreprise_fournisseur_id INTEGER NOT NULL,
    entreprise_filiale_id INTEGER NOT NULL,
    personnel_id INTEGER NOT NULL,
    statut_id INTEGER NOT NULL,
    montant_ttc NUMERIC(15,2) NOT NULL,
    FOREIGN KEY (proforma_fournisseur_id) REFERENCES proforma_fournisseur(id),
    FOREIGN KEY (entreprise_fournisseur_id) REFERENCES entreprise(id),
    FOREIGN KEY (entreprise_filiale_id) REFERENCES entreprise(id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (statut_id) REFERENCES statut(id)
);

CREATE TABLE bon_commande_achat_details (
    id SERIAL PRIMARY KEY,
    bon_commande_achat_id INTEGER NOT NULL,
    article_id INTEGER NOT NULL,
    quantite NUMERIC(15,2) NOT NULL,
    prix_unitaire NUMERIC(15,2) NOT NULL,
    FOREIGN KEY (bon_commande_achat_id) REFERENCES bon_commande_achat(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES article(id)
);

-- Etape 3: Facture Achat (Ce qu'on doit payer)
CREATE TABLE facture_achat (
    id SERIAL PRIMARY KEY,
    numero_facture_fournisseur VARCHAR(50) NOT NULL,
    date_facture DATE DEFAULT CURRENT_DATE,
    bon_commande_achat_id INTEGER,
    entreprise_fournisseur_id INTEGER NOT NULL,
    entreprise_filiale_id INTEGER NOT NULL,
    statut_id INTEGER NOT NULL,
    montant_ttc NUMERIC(15,2) NOT NULL,
    reste_a_payer NUMERIC(15,2) NOT NULL,
    remarques TEXT,
    FOREIGN KEY (bon_commande_achat_id) REFERENCES bon_commande_achat(id),
    FOREIGN KEY (entreprise_filiale_id) REFERENCES entreprise(id),
    FOREIGN KEY (statut_id) REFERENCES statut(id)
);

CREATE TABLE facture_achat_details (
    id SERIAL PRIMARY KEY,
    facture_achat_id INTEGER NOT NULL,
    article_id INTEGER NOT NULL,
    quantite NUMERIC(15,2) NOT NULL,
    prix_unitaire NUMERIC(15,2) NOT NULL,
    FOREIGN KEY (facture_achat_id) REFERENCES facture_achat(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES article(id)
);

-- ==========================================
-- 6. PROCESSUS VENTE (CLIENTS)
-- ==========================================

-- Etape 1: Devis (Proforma Client)
CREATE TABLE devis_vente (
    id SERIAL PRIMARY KEY,
    numero_devis VARCHAR(50) UNIQUE NOT NULL,
    date_devis DATE DEFAULT CURRENT_DATE,
    entreprise_client_id INTEGER NOT NULL,
    entreprise_filiale_id INTEGER NOT NULL, -- Nous (le vendeur)
    personnel_id INTEGER NOT NULL,
    statut_id INTEGER NOT NULL,
    montant_ttc NUMERIC(15,2),
    FOREIGN KEY (entreprise_client_id) REFERENCES entreprise(id),
    FOREIGN KEY (entreprise_filiale_id) REFERENCES entreprise(id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (statut_id) REFERENCES statut(id)
);

CREATE TABLE devis_vente_details (
    id SERIAL PRIMARY KEY,
    devis_vente_id INTEGER NOT NULL,
    article_id INTEGER NOT NULL,
    quantite NUMERIC(15,2) NOT NULL,
    prix_unitaire NUMERIC(15,2) NOT NULL,
    FOREIGN KEY (devis_vente_id) REFERENCES devis_vente(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES article(id)
);

-- Etape 2: Bon de Commande Vente
CREATE TABLE bon_commande_vente (
    id SERIAL PRIMARY KEY,
    numero_bc VARCHAR(50) UNIQUE NOT NULL,
    date_commande DATE DEFAULT CURRENT_DATE,
    devis_vente_id INTEGER, -- Lien vers le devis
    entreprise_client_id INTEGER NOT NULL,
    entreprise_filiale_id INTEGER NOT NULL,
    personnel_id INTEGER NOT NULL,
    statut_id INTEGER NOT NULL,
    montant_ttc NUMERIC(15,2) NOT NULL,
    FOREIGN KEY (devis_vente_id) REFERENCES devis_vente(id),
    FOREIGN KEY (entreprise_client_id) REFERENCES entreprise(id),
    FOREIGN KEY (entreprise_filiale_id) REFERENCES entreprise(id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (statut_id) REFERENCES statut(id)
);

CREATE TABLE bon_commande_vente_details (
    id SERIAL PRIMARY KEY,
    bon_commande_vente_id INTEGER NOT NULL,
    article_id INTEGER NOT NULL,
    quantite NUMERIC(15,2) NOT NULL,
    prix_unitaire NUMERIC(15,2) NOT NULL,
    FOREIGN KEY (bon_commande_vente_id) REFERENCES bon_commande_vente(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES article(id)
);

-- Etape 3: Facture Vente
CREATE TABLE facture_vente (
    id SERIAL PRIMARY KEY,
    numero_facture VARCHAR(50) UNIQUE NOT NULL,
    date_facture DATE DEFAULT CURRENT_DATE,
    bon_commande_vente_id INTEGER,
    entreprise_client_id INTEGER NOT NULL,
    entreprise_filiale_id INTEGER NOT NULL,
    personnel_id INTEGER NOT NULL, -- Vendeur
    statut_id INTEGER NOT NULL,
    montant_ttc NUMERIC(15,2) NOT NULL,
    reste_a_payer NUMERIC(15,2) NOT NULL,
    FOREIGN KEY (bon_commande_vente_id) REFERENCES bon_commande_vente(id),
    FOREIGN KEY (entreprise_client_id) REFERENCES entreprise(id),
    FOREIGN KEY (entreprise_filiale_id) REFERENCES entreprise(id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (statut_id) REFERENCES statut(id)
);

CREATE TABLE facture_vente_details (
    id SERIAL PRIMARY KEY,
    facture_vente_id INTEGER NOT NULL,
    article_id INTEGER NOT NULL,
    quantite NUMERIC(15,2) NOT NULL,
    prix_unitaire NUMERIC(15,2) NOT NULL,
    FOREIGN KEY (facture_vente_id) REFERENCES facture_vente(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES article(id)
);

-- ==========================================
-- 7. CAISSE & PAIEMENTS (Structure Entrée/Sortie)
-- ==========================================

CREATE TABLE caisse (
    id SERIAL PRIMARY KEY,
    code_caisse VARCHAR(50) UNIQUE,
    libelle VARCHAR(100) NOT NULL,
    solde_actuel NUMERIC(15,2) DEFAULT 0,
    entreprise_id INTEGER NOT NULL, -- Caisse de quelle filiale ?
    FOREIGN KEY (entreprise_id) REFERENCES entreprise(id)
);

CREATE TABLE caisse_mouvement (
    id SERIAL PRIMARY KEY,
    date_mouvement TIMESTAMP DEFAULT NOW(),
    libelle_operation VARCHAR(200),
    
    -- Structure Entrée/Sortie explicite comme demandé
    montant_entree NUMERIC(15,2) DEFAULT 0,
    montant_sortie NUMERIC(15,2) DEFAULT 0,
    solde_avant NUMERIC(15,2) NOT NULL,
    solde_apres NUMERIC(15,2) NOT NULL,
    
    caisse_id INTEGER NOT NULL,
    personnel_id INTEGER NOT NULL, -- Caissier
    
    FOREIGN KEY (caisse_id) REFERENCES caisse(id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id)
);

-- Table de liaison pour dire "Ce mouvement de caisse paie cette facture"
CREATE TABLE paiement_vente (
    id SERIAL PRIMARY KEY,
    facture_vente_id INTEGER NOT NULL,
    caisse_mouvement_id INTEGER NOT NULL, -- Lien vers l'entrée d'argent
    montant_paye NUMERIC(15,2) NOT NULL,
    FOREIGN KEY (facture_vente_id) REFERENCES facture_vente(id),
    FOREIGN KEY (caisse_mouvement_id) REFERENCES caisse_mouvement(id)
);

CREATE TABLE paiement_achat (
    id SERIAL PRIMARY KEY,
    facture_achat_id INTEGER NOT NULL,
    caisse_mouvement_id INTEGER NOT NULL, -- Lien vers la sortie d'argent
    montant_paye NUMERIC(15,2) NOT NULL,
    FOREIGN KEY (facture_achat_id) REFERENCES facture_achat(id),
    FOREIGN KEY (caisse_mouvement_id) REFERENCES caisse_mouvement(id)
);

-- Index pour accélérer les recherches
CREATE INDEX idx_stock_filiale ON stock(entreprise_id, article_id);
CREATE INDEX idx_mvt_stock_date ON mouvement_stock(date_mouvement);
CREATE INDEX idx_fac_vente_num ON facture_vente(numero_facture);