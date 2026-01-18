@echo off
REM Script de reset complet de la base de données ERP Achat-Vente-Production
REM Nécessite PostgreSQL installé et dans le PATH

echo === RESET COMPLET DE LA BASE DE DONNÉES ERP ACHAT-VENTE-PRODUCTION ===
echo.

REM Vérifier que les fichiers nécessaires existent
if not exist "conception.sql" (
    echo ❌ Erreur: Fichier conception.sql introuvable
    pause
    exit /b 1
)

if not exist "data.sql" (
    echo ❌ Erreur: Fichier data.sql introuvable
    pause
    exit /b 1
)

if not exist "views.sql" (
    echo ❌ Erreur: Fichier views.sql introuvable
    pause
    exit /b 1
)

echo ✅ Fichiers SQL trouvés
echo.
echo 📋 Exécution du schéma (création base + tables)...

REM Exécuter le fichier conception.sql qui contient déjà DROP/CREATE DATABASE
psql -U postgres -f conception.sql

if %errorlevel% neq 0 (
    echo ❌ Erreur lors de l'exécution du schéma
    echo Vérifiez que PostgreSQL est démarré et que l'utilisateur postgres existe
    pause
    exit /b 1
)

echo.
echo 📊 Import des données...

REM Importer les données
psql -U postgres -d achat_vente_db -f data.sql

if %errorlevel% neq 0 (
    echo ❌ Erreur lors de l'import des données
    pause
    exit /b 1
)

echo.
echo 👁️ Création des vues...

REM Importer les vues
psql -U postgres -d achat_vente_db -f views.sql

if %errorlevel% neq 0 (
    echo ❌ Erreur lors de la création des vues
    pause
    exit /b 1
)

echo.
echo 🔍 Vérification de l'import...

REM Vérifier que les tables principales contiennent des données
echo Vérification des tables principales:
psql -U postgres -d achat_vente_db -c "SELECT 'Articles:' as table_name, COUNT(*) as count FROM article UNION ALL SELECT 'Entreprises:', COUNT(*) FROM entreprise UNION ALL SELECT 'Personnel:', COUNT(*) FROM personnel UNION ALL SELECT 'Stock:', COUNT(*) FROM stock UNION ALL SELECT 'Mouvements Stock:', COUNT(*) FROM mouvement_stock UNION ALL SELECT 'Caisses:', COUNT(*) FROM caisse;"

echo.
echo Vérification des vues:
psql -U postgres -d achat_vente_db -c "SELECT 'Stock Valorisé:' as view_name, COUNT(*) as count FROM v_stock_valorise UNION ALL SELECT 'Fiche Stock:', COUNT(*) FROM v_fiche_stock;"

echo.
echo ✅ RESET COMPLET TERMINÉ AVEC SUCCÈS !
echo.
echo 📝 Résumé:
echo    - Base de données achat_vente_db recréée
echo    - Tables et données importées
echo    - Vues créées et fonctionnelles
echo.
echo 🏢 La base de données ERP est prête à être utilisée !
echo.
echo 🔗 Connexion: psql -U postgres -d achat_vente_db
echo.
pause

echo.
echo 🔍 Vérification de l'import...

REM Vérifier que les tables principales contiennent des données
echo Vérification des tables principales:
psql -U postgres -d achat_vente_db -c "SELECT 'Articles:' as table_name, COUNT(*) as count FROM article UNION ALL SELECT 'Entreprises:', COUNT(*) FROM entreprise UNION ALL SELECT 'Personnel:', COUNT(*) FROM personnel UNION ALL SELECT 'Stock:', COUNT(*) FROM stock UNION ALL SELECT 'Mouvements Stock:', COUNT(*) FROM mouvement_stock UNION ALL SELECT 'Caisses:', COUNT(*) FROM caisse UNION ALL SELECT 'Mouvements Caisse:', COUNT(*) FROM caisse_mouvement UNION ALL SELECT 'Bons Commande Achat:', COUNT(*) FROM bon_commande_achat UNION ALL SELECT 'Factures Achat:', COUNT(*) FROM facture_achat UNION ALL SELECT 'Bons Commande Vente:', COUNT(*) FROM bon_commande_vente UNION ALL SELECT 'Factures Vente:', COUNT(*) FROM facture_vente;"

echo.
echo Vérification des vues:
psql -U postgres -d achat_vente_db -c "SELECT 'Stock Valorisé:' as view_name, COUNT(*) as count FROM v_stock_valorise UNION ALL SELECT 'Fiche Stock:', COUNT(*) FROM v_fiche_stock UNION ALL SELECT 'Créances Clients:', COUNT(*) FROM v_creances_clients UNION ALL SELECT 'Dettes Fournisseurs:', COUNT(*) FROM v_dettes_fournisseurs;"

echo.
echo ✅ RESET COMPLET TERMINÉ AVEC SUCCÈS !
echo.
echo 📝 Résumé:
echo    - Base de données supprimée et recréée
echo    - Schéma importé
echo    - Données de test importées (articles, stock, mouvements, etc.)
echo    - Vues créées
echo    - Vérifications effectuées
echo.
echo 🏢 La base de données ERP Achat-Vente-Production est prête à être utilisée !
echo.
echo 🔗 Informations de connexion:
echo    - Base: achat_vente_db
echo    - User: postgres
echo    - Tables principales: article, entreprise, personnel, stock, mouvement_stock
echo    - Vues: v_stock_valorise, v_fiche_stock, v_creances_clients, v_dettes_fournisseurs
echo.
pause