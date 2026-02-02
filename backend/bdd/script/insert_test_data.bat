@echo off
REM Script d'insertion des données de test pour la valorisation de stock
REM Usage: insert_test_data.bat

echo === INSERTION DES DONNEES DE TEST VALORISATION ===

REM Paramètres de connexion par défaut
if not defined DB_HOST set DB_HOST=localhost
if not defined DB_PORT set DB_PORT=5432
if not defined DB_NAME set DB_NAME=achat_vente_db
if not defined DB_USER set DB_USER=postgres

echo 🔄 Connexion à la base de données %DB_NAME%...

REM Exécuter le script de données de test
psql -h %DB_HOST% -p %DB_PORT% -U %DB_USER% -d %DB_NAME% -f data_test_valorisation.sql

if %ERRORLEVEL% EQU 0 (
    echo ✅ Données de test insérées avec succès !
    echo.
    echo 📊 VÉRIFICATION DES DONNÉES INSÉRÉES :
    echo.
    
    REM Afficher un résumé des données
    psql -h %DB_HOST% -p %DB_PORT% -U %DB_USER% -d %DB_NAME% -c "SELECT 'Articles' as table_name, count(*) as count FROM article UNION ALL SELECT 'Stock', count(*) FROM stock WHERE quantite_actuelle > 0 UNION ALL SELECT 'Lots actifs', count(*) FROM lot_stock WHERE statut = 'ACTIF' UNION ALL SELECT 'Mouvements', count(*) FROM mouvement_stock UNION ALL SELECT 'Factures achat', count(*) FROM facture_achat UNION ALL SELECT 'Personnel', count(*) FROM personnel;"
    
    echo.
    echo 🔍 VALORISATIONS PAR MÉTHODE ^(LAPTOP001^) :
    echo.
    
    psql -h %DB_HOST% -p %DB_PORT% -U %DB_USER% -d %DB_NAME% -c "SELECT * FROM v_stock_valorisation WHERE reference = 'LAPTOP001';"
    
    echo.
    echo 🎯 PRÊT POUR LES TESTS !
    echo Vous pouvez maintenant tester les différentes méthodes de valorisation
    echo en consultant les vues v_stock_valorisation et v_lots_detail
    
) else (
    echo ❌ Erreur lors de l'insertion des données de test
    pause
    exit /b 1
)

pause