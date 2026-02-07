-- Script pour ajouter le statut de livraison à facture_vente
-- La livraison ne peut être effectuée (LIVRE=4) que si la facture est payée (PAYE=5)

-- Ajouter la colonne statut_livraison_id (nullable, car pas encore livré par défaut)
ALTER TABLE facture_vente 
ADD COLUMN statut_livraison_id INTEGER DEFAULT NULL;

-- Ajouter la contrainte de clé étrangère
ALTER TABLE facture_vente 
ADD CONSTRAINT fk_facture_vente_statut_livraison 
FOREIGN KEY (statut_livraison_id) REFERENCES statut(id);

-- Ajouter un commentaire sur la colonne
COMMENT ON COLUMN facture_vente.statut_livraison_id IS 'Statut de livraison. Peut être LIVRE (4) seulement si statut_id = PAYE (5)';

-- Mettre à jour les factures existantes qui sont déjà livrées (si le statut_id actuel est LIVRE)
-- On conserve le statut_id comme statut de paiement principal
UPDATE facture_vente 
SET statut_livraison_id = 4 
WHERE statut_id = 4;

-- Pour les factures qui étaient marquées LIVRE, on les passe à PAYE (car livré implique payé)
UPDATE facture_vente 
SET statut_id = 5 
WHERE statut_id = 4;
