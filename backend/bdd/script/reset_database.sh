#!/bin/bash

# Script de reset complet de la base de données ERP Achat-Vente-Production
# Nécessite PostgreSQL installé et configuré

set -e  # Arrêter le script en cas d'erreur

echo "=== RESET COMPLET DE LA BASE DE DONNÉES ERP ACHAT-VENTE-PRODUCTION ==="
echo ""

# Vérifier que les fichiers nécessaires existent
if [ ! -f "conception.sql" ]; then
    echo "❌ Erreur: Fichier conception.sql introuvable"
    exit 1
fi

if [ ! -f "data.sql" ]; then
    echo "❌ Erreur: Fichier data.sql introuvable"
    exit 1
fi

if [ ! -f "views.sql" ]; then
    echo "❌ Erreur: Fichier views.sql introuvable"
    exit 1
fi

echo "✅ Fichiers SQL trouvés"
echo ""
echo "📋 Exécution du schéma (création base + tables)..."

# Exécuter le fichier conception.sql qui contient déjà DROP/CREATE DATABASE
sudo -u postgres psql -f conception.sql

sudo -u postgres psql -d achat_vente_db -f alter_facture_statut_livraison.sql
sudo -u postgres psql -d achat_vente_db -f migration_add_depot_livraison_to_proforma_fournisseur.sql
echo ""
echo "📊 Import des données..."

# Importer les données
sudo -u postgres psql -d achat_vente_db -f data_test_complet.sql
sudo -u postgres psql -d achat_vente_db -f data_test_valorisation.sql

echo ""
echo "👁️ Création des vues..."

# Importer les vues
sudo -u postgres psql -d achat_vente_db -f views.sql

sudo -u postgres psql -d achat_vente_db -f triggers_valorisation.sql


sudo -u postgres psql -d achat_vente_db -f update_trigger_valeur_stock.sql

sudo -u postgres psql -d achat_vente_db -f update_trigger_valeur_stock.sql