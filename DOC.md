psql -U postgres -f conception.sql
psql -U postgres -d achat_vente_db -f add_depot_to_devis_vente.sql
psql -U postgres -d achat_vente_db -f triggers_valorisation.sql
psql -U postgres -d achat_vente_db -f data_test_complet.sql
psql -U postgres -d achat_vente_db -f views.sql
psql -U postgres -d achat_vente_db -f update_trigger_valeur_stock.sql
