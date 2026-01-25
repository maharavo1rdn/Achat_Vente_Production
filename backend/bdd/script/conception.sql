-- ==============================================================================
-- FICHIER 1 : SCHEMA_STRUCTURE_V3.5.SQL
-- Architecture ERP V3.5 (Avec renommage convention proforma_demande_achat)
-- ==============================================================================

\c postgres;
DROP DATABASE IF EXISTS achat_vente_db;
CREATE DATABASE achat_vente_db;
\c achat_vente_db;

-- 1. NETTOYAGE
DROP TABLE IF EXISTS paiement_vente_details CASCADE;
DROP TABLE IF EXISTS paiement_vente CASCADE;
DROP TABLE IF EXISTS paiement_achat CASCADE;
DROP TABLE IF EXISTS mode_paiement CASCADE;
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
DROP TABLE IF EXISTS proforma_fournisseur_details CASCADE;
DROP TABLE IF EXISTS proforma_fournisseur CASCADE;

-- NOUVEAU NETTOYAGE (Noms mis à jour)
DROP TABLE IF EXISTS proforma_demande_achat CASCADE;

DROP TABLE IF EXISTS devis_vente_details CASCADE;
DROP TABLE IF EXISTS devis_vente CASCADE;
DROP TABLE IF EXISTS sortie_lot_detail CASCADE;
DROP TABLE IF EXISTS lot_stock CASCADE;
DROP TABLE IF EXISTS mouvement_stock CASCADE;
DROP TABLE IF EXISTS stock CASCADE;
DROP TABLE IF EXISTS methode_valorisation_stock CASCADE;
DROP TABLE IF EXISTS article CASCADE;
DROP TABLE IF EXISTS article_categorie CASCADE;
DROP TABLE IF EXISTS unite CASCADE;
DROP TABLE IF EXISTS personnel CASCADE;
DROP TABLE IF EXISTS personnel_role CASCADE;
DROP TABLE IF EXISTS depot CASCADE;
DROP TABLE IF EXISTS site CASCADE;
DROP TABLE IF EXISTS entreprise CASCADE;
DROP TABLE IF EXISTS groupe CASCADE;
DROP TABLE IF EXISTS statut CASCADE;

-- ==========================================
-- 2. TABLES DE RÉFÉRENCE
-- ==========================================

CREATE TABLE statut (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    niveau INTEGER DEFAULT 0
);

CREATE TABLE unite (
    id SERIAL PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    libelle VARCHAR(50)
);

CREATE TABLE article_categorie (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    libelle VARCHAR(100) NOT NULL
);

CREATE TABLE personnel_role (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    niveau_acces INTEGER DEFAULT 1
);

CREATE TABLE mode_paiement (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    libelle VARCHAR(100) NOT NULL
);

CREATE TABLE methode_valorisation_stock (
    id SERIAL PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    libelle VARCHAR(100),
    description TEXT
);

-- ==========================================
-- 3. ARCHITECTURE (GROUPE > ENTREPRISE > SITE > DEPOT)
-- ==========================================

CREATE TABLE groupe (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    description TEXT
);

CREATE TABLE entreprise (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    groupe_id INTEGER,
    type_entreprise VARCHAR(20) NOT NULL CHECK (type_entreprise IN ('CLIENT', 'FOURNISSEUR', 'INTERNE', 'PARTENAIRE')),
    matricule_fiscal VARCHAR(100),
    adresse VARCHAR(200),
    telephone VARCHAR(50),
    email VARCHAR(100),
    est_actif BOOLEAN DEFAULT true,
    date_creation TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (groupe_id) REFERENCES groupe(id) ON DELETE SET NULL
);

CREATE TABLE site (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    adresse VARCHAR(200),
    telephone VARCHAR(50),
    email VARCHAR(100),
    entreprise_id INTEGER NOT NULL,
    est_actif BOOLEAN DEFAULT true,
    date_creation TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (entreprise_id) REFERENCES entreprise(id) ON DELETE CASCADE
);

CREATE TABLE depot (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    adresse VARCHAR(200),
    site_id INTEGER NOT NULL,
    est_actif BOOLEAN DEFAULT true,
    date_creation TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (site_id) REFERENCES site(id) ON DELETE CASCADE
);

CREATE TABLE personnel (
    id SERIAL PRIMARY KEY,
    code_employe VARCHAR(20) UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100),
    email VARCHAR(150) UNIQUE,
    mot_de_passe_hash VARCHAR(255),
    telephone VARCHAR(50),
    est_actif BOOLEAN DEFAULT true,
    personnel_role_id INTEGER NOT NULL,
    entreprise_id INTEGER, 
    site_defaut_id INTEGER,
    FOREIGN KEY (personnel_role_id) REFERENCES personnel_role(id),
    FOREIGN KEY (entreprise_id) REFERENCES entreprise(id),
    FOREIGN KEY (site_defaut_id) REFERENCES site(id)
);

-- ==========================================
-- 4. PRODUITS & STOCKS (NIVEAU DEPOT)
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

CREATE TABLE stock (
    id SERIAL PRIMARY KEY,
    article_id INTEGER NOT NULL,
    depot_id INTEGER NOT NULL,
    methode_valorisation_stock_id INTEGER NOT NULL, 
    quantite_actuelle NUMERIC(15,2) DEFAULT 0,
    cmup_actuel NUMERIC(15,2) DEFAULT 0,
    valeur_stock_total NUMERIC(15,2) DEFAULT 0,
    date_maj TIMESTAMP DEFAULT NOW(),
    UNIQUE(article_id, depot_id),
    FOREIGN KEY (article_id) REFERENCES article(id),
    FOREIGN KEY (depot_id) REFERENCES depot(id),
    FOREIGN KEY (methode_valorisation_stock_id) REFERENCES methode_valorisation_stock(id)
);

CREATE TABLE mouvement_stock (
    id SERIAL PRIMARY KEY,
    date_mouvement TIMESTAMP DEFAULT NOW(),
    type_mouvement VARCHAR(20) NOT NULL,
    quantite_stock_avant NUMERIC(15,2) NOT NULL DEFAULT 0,
    quantite_entree NUMERIC(15,2) DEFAULT 0,
    quantite_sortie NUMERIC(15,2) DEFAULT 0,
    quantite_stock_apres NUMERIC(15,2) NOT NULL DEFAULT 0,
    prix_unitaire_mouvement NUMERIC(15,2),
    article_id INTEGER NOT NULL,
    depot_id INTEGER NOT NULL,
    personnel_id INTEGER NOT NULL,
    reference_document VARCHAR(100),
    FOREIGN KEY (article_id) REFERENCES article(id),
    FOREIGN KEY (depot_id) REFERENCES depot(id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id)
);

CREATE TABLE lot_stock (
    id SERIAL PRIMARY KEY,
    numero_lot VARCHAR(50) UNIQUE NOT NULL,
    article_id INTEGER NOT NULL,
    depot_id INTEGER NOT NULL,
    date_entree TIMESTAMP DEFAULT NOW(),
    mouvement_entree_id INTEGER,
    quantite_initiale NUMERIC(15,2) NOT NULL,
    quantite_restante NUMERIC(15,2) NOT NULL,
    prix_unitaire_achat NUMERIC(15,2) NOT NULL,
    statut VARCHAR(20) DEFAULT 'ACTIF' CHECK (statut IN ('ACTIF', 'EPUISE')),
    FOREIGN KEY (article_id) REFERENCES article(id),
    FOREIGN KEY (depot_id) REFERENCES depot(id),
    FOREIGN KEY (mouvement_entree_id) REFERENCES mouvement_stock(id)
);

CREATE TABLE sortie_lot_detail (
    id SERIAL PRIMARY KEY,
    mouvement_sortie_id INTEGER NOT NULL,
    lot_stock_id INTEGER NOT NULL,
    quantite_prelevee NUMERIC(15,2) NOT NULL,
    prix_unitaire_lot NUMERIC(15,2) NOT NULL,
    FOREIGN KEY (mouvement_sortie_id) REFERENCES mouvement_stock(id) ON DELETE CASCADE,
    FOREIGN KEY (lot_stock_id) REFERENCES lot_stock(id)
);

-- ==========================================
-- 5. ACHATS (LOGIQUE ENTREPRISE -> DEPOT)
-- ==========================================

-- [[[ MODULE DEMANDE D'ACHAT (Renommé) ]]] --
CREATE TABLE proforma_demande_achat (
    id SERIAL PRIMARY KEY,
    numero_da VARCHAR(50) UNIQUE NOT NULL, -- Ex: DA-2023-0001
    date_demande DATE DEFAULT CURRENT_DATE,
    
    personnel_demandeur_id INTEGER NOT NULL,
    entreprise_id INTEGER NOT NULL, -- L'entreprise/Filiale qui a le besoin
    depot_cible_id INTEGER, -- Pour quel dépôt le besoin est exprimé
    
    date_souhaitee DATE,
    motif_achat TEXT,
    montant_ttc NUMERIC(15,2) DEFAULT 0,
    statut_id INTEGER NOT NULL, 
    date_creation TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (personnel_demandeur_id) REFERENCES personnel(id),
    FOREIGN KEY (entreprise_id) REFERENCES entreprise(id),
    FOREIGN KEY (depot_cible_id) REFERENCES depot(id),
    FOREIGN KEY (statut_id) REFERENCES statut(id)
);

CREATE TABLE proforma_demande_achat_details (
    id SERIAL PRIMARY KEY,
    proforma_demande_achat_id INTEGER NOT NULL, -- FK renommée
    article_id INTEGER NOT NULL,
    quantite_demandee NUMERIC(15,2) NOT NULL,
    prix_estime NUMERIC(15,2) DEFAULT 0,
    FOREIGN KEY (proforma_demande_achat_id) REFERENCES proforma_demande_achat(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES article(id)
);

-- [[[ FIN MODULE ]]] --
CREATE TABLE proforma_fournisseur (
    id SERIAL PRIMARY KEY,
    numero_proforma VARCHAR(50) NOT NULL,
    date_emission DATE DEFAULT CURRENT_DATE,
    date_validite DATE,
    entreprise_fournisseur_id INTEGER NOT NULL,
    entreprise_filiale_id INTEGER NOT NULL,
    personnel_id INTEGER NOT NULL,
    statut_id INTEGER NOT NULL,
    montant_ttc NUMERIC(15,2) DEFAULT 0,
    
    -- LIAISON MISE À JOUR VERS LA TABLE RENOMMÉE
    proforma_demande_achat_id INTEGER, 
    
    FOREIGN KEY (entreprise_fournisseur_id) REFERENCES entreprise(id),
    FOREIGN KEY (entreprise_filiale_id) REFERENCES entreprise(id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (statut_id) REFERENCES statut(id),
    
    -- FK MISE À JOUR
    FOREIGN KEY (proforma_demande_achat_id) REFERENCES proforma_demande_achat(id)
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

CREATE TABLE bon_commande_achat (
    id SERIAL PRIMARY KEY,
    numero_bc VARCHAR(50) UNIQUE NOT NULL,
    date_commande DATE DEFAULT CURRENT_DATE,
    proforma_fournisseur_id INTEGER,
    entreprise_fournisseur_id INTEGER NOT NULL,
    entreprise_filiale_id INTEGER NOT NULL,
    personnel_id INTEGER NOT NULL,
    statut_id INTEGER NOT NULL,
    montant_ttc NUMERIC(15,2) NOT NULL,
    depot_livraison_id INTEGER, 
    FOREIGN KEY (proforma_fournisseur_id) REFERENCES proforma_fournisseur(id),
    FOREIGN KEY (entreprise_fournisseur_id) REFERENCES entreprise(id),
    FOREIGN KEY (entreprise_filiale_id) REFERENCES entreprise(id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (statut_id) REFERENCES statut(id),
    FOREIGN KEY (depot_livraison_id) REFERENCES depot(id)
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
    depot_reception_id INTEGER NOT NULL,
    FOREIGN KEY (bon_commande_achat_id) REFERENCES bon_commande_achat(id),
    FOREIGN KEY (entreprise_filiale_id) REFERENCES entreprise(id),
    FOREIGN KEY (statut_id) REFERENCES statut(id),
    FOREIGN KEY (depot_reception_id) REFERENCES depot(id)
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
-- 6. VENTES (LOGIQUE ENTREPRISE -> DEPOT)
-- ==========================================

CREATE TABLE devis_vente (
    id SERIAL PRIMARY KEY,
    numero_devis VARCHAR(50) UNIQUE NOT NULL,
    date_devis DATE DEFAULT CURRENT_DATE,
    entreprise_client_id INTEGER NOT NULL,
    entreprise_filiale_id INTEGER NOT NULL,
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

CREATE TABLE bon_commande_vente (
    id SERIAL PRIMARY KEY,
    numero_bc VARCHAR(50) UNIQUE NOT NULL,
    date_commande DATE DEFAULT CURRENT_DATE,
    devis_vente_id INTEGER,
    entreprise_client_id INTEGER NOT NULL,
    entreprise_filiale_id INTEGER NOT NULL,
    personnel_id INTEGER NOT NULL,
    statut_id INTEGER NOT NULL,
    montant_ttc NUMERIC(15,2) NOT NULL,
    depot_expedition_id INTEGER, 
    FOREIGN KEY (devis_vente_id) REFERENCES devis_vente(id),
    FOREIGN KEY (entreprise_client_id) REFERENCES entreprise(id),
    FOREIGN KEY (entreprise_filiale_id) REFERENCES entreprise(id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (statut_id) REFERENCES statut(id),
    FOREIGN KEY (depot_expedition_id) REFERENCES depot(id)
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

CREATE TABLE facture_vente (
    id SERIAL PRIMARY KEY,
    numero_facture VARCHAR(50) UNIQUE NOT NULL,
    date_facture DATE DEFAULT CURRENT_DATE,
    bon_commande_vente_id INTEGER,
    entreprise_client_id INTEGER NOT NULL,
    entreprise_filiale_id INTEGER NOT NULL,
    personnel_id INTEGER NOT NULL,
    statut_id INTEGER NOT NULL,
    montant_ttc NUMERIC(15,2) NOT NULL,
    reste_a_payer NUMERIC(15,2) NOT NULL,
    remarques TEXT,
    depot_expedition_id INTEGER NOT NULL,
    FOREIGN KEY (bon_commande_vente_id) REFERENCES bon_commande_vente(id),
    FOREIGN KEY (entreprise_client_id) REFERENCES entreprise(id),
    FOREIGN KEY (entreprise_filiale_id) REFERENCES entreprise(id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (statut_id) REFERENCES statut(id),
    FOREIGN KEY (depot_expedition_id) REFERENCES depot(id)
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
-- 7. CAISSE & PAIEMENTS
-- ==========================================

CREATE TABLE caisse (
    id SERIAL PRIMARY KEY,
    code_caisse VARCHAR(50) UNIQUE,
    libelle VARCHAR(100) NOT NULL,
    solde_actuel NUMERIC(15,2) DEFAULT 0,
    entreprise_id INTEGER NOT NULL,
    FOREIGN KEY (entreprise_id) REFERENCES entreprise(id)
);

CREATE TABLE caisse_mouvement (
    id SERIAL PRIMARY KEY,
    date_mouvement TIMESTAMP DEFAULT NOW(),
    statut_id INTEGER NOT NULL,
    libelle_operation VARCHAR(200),
    montant_entree NUMERIC(15,2) DEFAULT 0,
    montant_sortie NUMERIC(15,2) DEFAULT 0,
    solde_avant NUMERIC(15,2) NOT NULL,
    solde_apres NUMERIC(15,2) NOT NULL,
    caisse_id INTEGER NOT NULL,
    personnel_id INTEGER NOT NULL,
    FOREIGN KEY (statut_id) REFERENCES statut(id),
    FOREIGN KEY (caisse_id) REFERENCES caisse(id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id)
);

CREATE TABLE paiement_vente (
    id SERIAL PRIMARY KEY,
    numero_recu VARCHAR(50) UNIQUE,
    mode_paiement_id INTEGER NOT NULL,
    statut_id INTEGER NOT NULL DEFAULT 1,
    facture_vente_id INTEGER NOT NULL,
    caisse_mouvement_id INTEGER,
    montant NUMERIC(15,2) NOT NULL DEFAULT 0,
    date_paiement DATE DEFAULT CURRENT_DATE,
    reference_externe VARCHAR(100),
    FOREIGN KEY (statut_id) REFERENCES statut(id),
    FOREIGN KEY (facture_vente_id) REFERENCES facture_vente(id),
    FOREIGN KEY (mode_paiement_id) REFERENCES mode_paiement(id),
    FOREIGN KEY (caisse_mouvement_id) REFERENCES caisse_mouvement(id)
);

CREATE TABLE paiement_achat (
    id SERIAL PRIMARY KEY,
    numero_paiement VARCHAR(50) UNIQUE,
    mode_paiement_id INTEGER NOT NULL,
    statut_id INTEGER NOT NULL DEFAULT 1,
    facture_achat_id INTEGER NOT NULL,
    caisse_mouvement_id INTEGER,
    montant NUMERIC(15,2) NOT NULL DEFAULT 0,
    date_paiement DATE DEFAULT CURRENT_DATE,
    reference_externe VARCHAR(100),
    FOREIGN KEY (statut_id) REFERENCES statut(id),
    FOREIGN KEY (facture_achat_id) REFERENCES facture_achat(id),
    FOREIGN KEY (mode_paiement_id) REFERENCES mode_paiement(id),
    FOREIGN KEY (caisse_mouvement_id) REFERENCES caisse_mouvement(id)
);

-- ==========================================
-- 8. INDEX
-- ==========================================

CREATE INDEX idx_stock_depot ON stock(depot_id, article_id);
CREATE INDEX idx_lot_actif ON lot_stock(article_id, depot_id) WHERE statut = 'ACTIF';
CREATE INDEX idx_fac_vente_depot ON facture_vente(depot_expedition_id);
CREATE INDEX idx_fac_achat_depot ON facture_achat(depot_reception_id);
CREATE INDEX idx_mvt_date ON mouvement_stock(date_mouvement);
CREATE INDEX idx_fac_vente_num ON facture_vente(numero_facture);
CREATE INDEX idx_paiement_vente_fac ON paiement_vente(facture_vente_id);
-- Nouvel index mis à jour avec le nouveau nom
CREATE INDEX idx_da_date ON proforma_demande_achat(date_demande);