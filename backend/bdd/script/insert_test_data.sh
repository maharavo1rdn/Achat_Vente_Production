#!/bin/bash

# Script d'insertion des données de test pour la valorisation de stock
# Usage: ./insert_test_data.sh

echo "=== INSERTION DES DONNÉES DE TEST VALORISATION ==="

# Vérifier si PostgreSQL est accessible
if ! command -v psql &> /dev/null; then
    echo "❌ PostgreSQL (psql) n'est pas installé ou pas dans le PATH"
    exit 1
fi

# Paramètres de connexion par défaut
DB_HOST=${DB_HOST:-localhost}
DB_PORT=${DB_PORT:-5432}
DB_NAME=${DB_NAME:-achat_vente_db}
DB_USER=${DB_USER:-postgres}

echo "🔄 Connexion à la base de données $DB_NAME..."

# Exécuter le script de données de test
psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME -f data_test_valorisation.sql

if [ $? -eq 0 ]; then
    echo "✅ Données de test insérées avec succès !"
    
    echo ""
    echo "📊 VÉRIFICATION DES DONNÉES INSÉRÉES :"
    echo ""
    
    # Afficher un résumé des données
    psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME -c "
    SELECT 'Articles' as table_name, count(*) as count FROM article
    UNION ALL
    SELECT 'Stock', count(*) FROM stock WHERE quantite_actuelle > 0
    UNION ALL  
    SELECT 'Lots actifs', count(*) FROM lot_stock WHERE statut = 'ACTIF'
    UNION ALL
    SELECT 'Mouvements', count(*) FROM mouvement_stock
    UNION ALL
    SELECT 'Factures achat', count(*) FROM facture_achat
    UNION ALL
    SELECT 'Personnel', count(*) FROM personnel;
    "
    
    echo ""
    echo "🔍 VALORISATIONS PAR MÉTHODE (LAPTOP001) :"
    echo ""
    
    psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME -c "
    SELECT * FROM v_stock_valorisation WHERE reference = 'LAPTOP001';
    "
    
else
    echo "❌ Erreur lors de l'insertion des données de test"
    exit 1
fi

echo ""
echo "🎯 PRÊT POUR LES TESTS !"
echo "Vous pouvez maintenant tester les différentes méthodes de valorisation"
echo "en consultant les vues v_stock_valorisation et v_lots_detail"