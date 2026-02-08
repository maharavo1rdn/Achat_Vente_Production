-- Migration: Ajouter depot_livraison_id à proforma_fournisseur
-- Date: 2026-02-08
-- Description: Permet de spécifier le dépôt de livraison dès la création du proforma fournisseur

-- Ajouter la colonne depot_livraison_id
ALTER TABLE proforma_fournisseur 
ADD COLUMN depot_livraison_id INTEGER;

-- Ajouter la contrainte de clé étrangère
ALTER TABLE proforma_fournisseur
ADD CONSTRAINT fk_proforma_fournisseur_depot_livraison
FOREIGN KEY (depot_livraison_id) REFERENCES depot(id);

-- Copier depot_cible_id depuis proforma_demande_achat pour les proforma existants
UPDATE proforma_fournisseur pf
SET depot_livraison_id = pda.depot_cible_id
FROM proforma_demande_achat pda
WHERE pf.proforma_demande_achat_id = pda.id
  AND pf.depot_livraison_id IS NULL
  AND pda.depot_cible_id IS NOT NULL;

-- Afficher le résultat
SELECT COUNT(*) as proformas_updated 
FROM proforma_fournisseur 
WHERE depot_livraison_id IS NOT NULL;

ALTER TABLE facture_achat ADD COLUMN remarques TEXT;