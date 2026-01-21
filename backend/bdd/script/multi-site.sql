create table groupe (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    description TEXT
);

CREATE TABLE entreprise (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    id_groupe INT REFERENCES groupe(id) ON DELETE SET NULL,
    type_entreprise VARCHAR(20) NOT NULL CHECK (type_entreprise IN ('CLIENT', 'FOURNISSEUR', 'INTERNE', 'PARTENAIRE')),
    matricule_fiscal VARCHAR(100),
    adresse VARCHAR(200),
    telephone VARCHAR(50),
    email VARCHAR(100),
    est_actif BOOLEAN DEFAULT true,
    date_creation TIMESTAMP DEFAULT NOW()
);

CREATE table site (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    adresse VARCHAR(200),
    telephone VARCHAR(50),
    email VARCHAR(100),
    entreprise_id INT REFERENCES entreprise(id) ON DELETE CASCADE,
    est_actif BOOLEAN DEFAULT true,
    date_creation TIMESTAMP DEFAULT NOW()
);

CREATE table depot (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    adresse VARCHAR(200),
    site_id INT REFERENCES site(id) ON DELETE CASCADE,
    est_actif BOOLEAN DEFAULT true,
    date_creation TIMESTAMP DEFAULT NOW()
);