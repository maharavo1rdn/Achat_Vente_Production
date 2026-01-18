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

echo ""
echo "📊 Import des données..."

# Importer les données
sudo -u postgres psql -d achat_vente_db -f data.sql

echo ""
echo "👁️ Création des vues..."

# Importer les vues
sudo -u postgres psql -d achat_vente_db -f views.sql

echo ""
echo "🔍 Vérification de l'import..."

# Vérifier que les tables principales contiennent des données
echo "Vérification des tables principales:"
sudo -u postgres psql -d achat_vente_db -c "
SELECT
    'Articles:' as table_name, COUNT(*) as count FROM article
UNION ALL
SELECT 'Entreprises:', COUNT(*) FROM entreprise
UNION ALL
SELECT 'Personnel:', COUNT(*) FROM personnel
UNION ALL
SELECT 'Stock:', COUNT(*) FROM stock
UNION ALL
SELECT 'Mouvements Stock:', COUNT(*) FROM mouvement_stock
UNION ALL
SELECT 'Caisses:', COUNT(*) FROM caisse
UNION ALL
SELECT 'Mouvements Caisse:', COUNT(*) FROM caisse_mouvement;"

echo ""
echo "Vérification des vues:"
sudo -u postgres psql -d achat_vente_db -c "
SELECT
    'Stock Valorisé:' as view_name, COUNT(*) as count FROM v_stock_valorise
UNION ALL
SELECT 'Fiche Stock:', COUNT(*) FROM v_fiche_stock;"

echo ""
echo "✅ RESET COMPLET TERMINÉ AVEC SUCCÈS !"
echo ""
echo "📝 Résumé:"
echo "   - Base de données achat_vente_db recréée"
echo "   - $(sudo -u postgres psql -d achat_vente_db -tAc 'SELECT COUNT(*) FROM article;') articles importés"
echo "   - $(sudo -u postgres psql -d achat_vente_db -tAc 'SELECT COUNT(*) FROM stock;') lignes de stock"
echo "   - Vues créées et fonctionnelles"
echo ""
echo "🏢 La base de données ERP est prête à être utilisée !"
echo ""
echo "🔗 Connexion: psql -U postgres -d achat_vente_db"

echo ""
echo "🔍 Vérification de l'import..."

# Vérifier que les tables principales contiennent des données
echo "Vérification des tables principales:"
psql -U postgres -h localhost -d achat_vente_db -c "
SELECT
    'Articles:' as table_name, COUNT(*) as count FROM article
UNION ALL
SELECT 'Entreprises:', COUNT(*) FROM entreprise
UNION ALL
SELECT 'Personnel:', COUNT(*) FROM personnel
UNION ALL
SELECT 'Stock:', COUNT(*) FROM stock
UNION ALL
SELECT 'Mouvements Stock:', COUNT(*) FROM mouvement_stock
UNION ALL
SELECT 'Caisses:', COUNT(*) FROM caisse
UNION ALL
SELECT 'Mouvements Caisse:', COUNT(*) FROM caisse_mouvement
UNION ALL
SELECT 'Bons Commande Achat:', COUNT(*) FROM bon_commande_achat
UNION ALL
SELECT 'Factures Achat:', COUNT(*) FROM facture_achat
UNION ALL
SELECT 'Bons Commande Vente:', COUNT(*) FROM bon_commande_vente
UNION ALL
SELECT 'Factures Vente:', COUNT(*) FROM facture_vente;"

echo ""
echo "Vérification des vues:"
psql -U postgres -h localhost -d achat_vente_db -c "
SELECT
    'Stock Valorisé:' as view_name, COUNT(*) as count FROM v_stock_valorise
UNION ALL
SELECT 'Fiche Stock:', COUNT(*) FROM v_fiche_stock
UNION ALL
SELECT 'Créances Clients:', COUNT(*) FROM v_creances_clients;"

echo ""
echo "✅ RESET COMPLET TERMINÉ AVEC SUCCÈS !"
echo ""
echo "📝 Résumé:"
echo "   - Base de données supprimée et recréée"
echo "   - Schéma importé"
echo "   - Données de test importées (articles, stock, mouvements, etc.)"
echo "   - Vues créées"
echo "   - Vérifications effectuées"
echo ""
echo "🏢 La base de données ERP Achat-Vente-Production est prête à être utilisée !"
echo ""
echo "🔗 Informations de connexion:"
echo "   - Base: achat_vente_db"
echo "   - Host: localhost"
echo "   - User: postgres"
echo "   - Tables principales: article, entreprise, personnel, stock, mouvement_stock"
echo "   - Vues: v_stock_valorise, v_fiche_stock, v_creances_clients"