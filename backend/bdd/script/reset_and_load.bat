@echo off
REM Reset complet de la base avec donnees de test
echo ========================================
echo RESET COMPLET BASE DE DONNEES
echo ========================================

echo.
echo 1. Suppression de la base...
psql -U postgres -c "DROP DATABASE IF EXISTS achat_vente_db;"

echo.
echo 2. Recreation de la base...
psql -U postgres -c "CREATE DATABASE achat_vente_db WITH ENCODING='UTF8' LC_COLLATE='French_France.1252' LC_CTYPE='French_France.1252';"

echo.
echo 3. Creation du schema...
psql -U postgres -d achat_vente_db -f conception.sql

echo.
echo 4. Installation des triggers...
psql -U postgres -d achat_vente_db -f triggers_valorisation.sql

echo.
echo 5. Installation des vues...
psql -U postgres -d achat_vente_db -f views.sql

echo.
echo 6. Chargement des donnees de test...
psql -U postgres -d achat_vente_db -f data_test_valorisation.sql

echo.
echo ========================================
echo TERMINÉ !
echo ========================================
pause
