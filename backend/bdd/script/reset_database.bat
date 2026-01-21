@echo off
echo ==================================================
echo  RESET COMPLET DE LA BASE DE DONNÉES ERP
echo  Achat-Vente-Production - Architecture V3.3
echo ==================================================
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
psql -U postgres -f conception.sql

echo.
echo 📊 Import des données...
psql -U postgres -d achat_vente_db -f data.sql

echo.
echo 👁️ Création des vues...
psql -U postgres -d achat_vente_db -f views.sql

echo.
echo 🔍 Vérification de l'import...
echo.

echo Vérification des tables principales:
psql -U postgres -d achat_vente_db -c "
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

echo.
echo Vérification des vues:
psql -U postgres -d achat_vente_db -c "
SELECT
    'Structure Organisation:' as view_name, COUNT(*) as count FROM v_structure_organisation
UNION ALL
SELECT 'Stock Valorisé:', COUNT(*) FROM v_stock_valorise
UNION ALL
SELECT 'Stock Lots FIFO:', COUNT(*) FROM v_stock_lots_fifo
UNION ALL
SELECT 'Fiche Stock:', COUNT(*) FROM v_fiche_stock
UNION ALL
SELECT 'Créances Clients:', COUNT(*) FROM v_creances_clients
UNION ALL
SELECT 'Dettes Fournisseurs:', COUNT(*) FROM v_dettes_fournisseurs
UNION ALL
SELECT 'Journal Caisse:', COUNT(*) FROM v_journal_caisse
UNION ALL
SELECT 'Dashboard KPI:', COUNT(*) FROM v_dashboard_kpi
UNION ALL
SELECT 'Stock Consolidé Groupe:', COUNT(*) FROM v_stock_consolide_groupe
UNION ALL
SELECT 'Alerte Stock Dépôt:', COUNT(*) FROM v_alerte_stock_depot;"

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
echo    - Host: localhost
echo    - User: postgres
echo    - Tables principales: article, entreprise, personnel, stock, mouvement_stock
echo    - Vues: v_stock_valorise, v_fiche_stock, v_creances_clients, v_dettes_fournisseurs, v_stock_consolide_groupe
echo.
pause