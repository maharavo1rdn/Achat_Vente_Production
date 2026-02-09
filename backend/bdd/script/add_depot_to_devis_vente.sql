-- ============================================================================
-- MIGRATION : Ajouter le dépôt de sortie au devis de vente
-- Date : 09/02/2026
-- ============================================================================

-- ÉTAPE 1 : Ajouter la colonne depot_id à la table devis_vente
-- La colonne est nullable pour permettre de ne pas spécifier de dépôt
ALTER TABLE devis_vente 
ADD COLUMN depot_id INTEGER;

-- ÉTAPE 2 : Ajouter la contrainte de clé étrangère
ALTER TABLE devis_vente 
ADD CONSTRAINT fk_devis_vente_depot 
FOREIGN KEY (depot_id) REFERENCES depot(id);

-- ÉTAPE 3 : Ajouter un commentaire pour la documentation
COMMENT ON COLUMN devis_vente.depot_id IS 'Dépôt de sortie de la marchandise (optionnel)';

-- ÉTAPE 4 : Vérification
SELECT 
    column_name, 
    data_type, 
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_name = 'devis_vente'
  AND column_name = 'depot_id';

SELECT 'Migration terminée - depot_id ajouté à devis_vente' as resultat;
