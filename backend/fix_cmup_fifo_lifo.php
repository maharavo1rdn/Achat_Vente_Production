<?php
require 'vendor/autoload.php';
require 'app/config/bootstrap.php';

echo "=== CORRECTION CMUP POUR STOCKS FIFO/LIFO ===\n\n";

try {
    $db = Flight::db();
    
    // ÉTAPE 1 : Afficher les stocks FIFO/LIFO avec CMUP (AVANT)
    echo "1. Stocks FIFO/LIFO ayant un CMUP (AVANT correction):\n";
    $stmt = $db->query("
        SELECT 
            s.id,
            a.reference,
            a.designation,
            d.nom as depot_nom,
            mvs.code as methode_valorisation,
            s.quantite_actuelle,
            s.cmup_actuel,
            s.valeur_stock_total
        FROM stock s
        INNER JOIN article a ON s.article_id = a.id
        INNER JOIN depot d ON s.depot_id = d.id
        INNER JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
        WHERE mvs.code IN ('FIFO', 'LIFO')
          AND s.cmup_actuel IS NOT NULL
        ORDER BY d.nom, a.reference
    ");
    
    $stocksAvant = $stmt->fetchAll();
    if (count($stocksAvant) > 0) {
        foreach ($stocksAvant as $stock) {
            echo "   - {$stock['reference']} ({$stock['designation']}) dans {$stock['depot_nom']} ({$stock['methode_valorisation']})\n";
            echo "     Qté: {$stock['quantite_actuelle']}, CMUP: {$stock['cmup_actuel']}, Valeur: {$stock['valeur_stock_total']}\n";
        }
    } else {
        echo "   ✅ Aucun stock FIFO/LIFO avec CMUP trouvé\n";
    }
    echo "\n";
    
    // ÉTAPE 2 : Mettre à jour les stocks FIFO/LIFO
    echo "2. Mise à jour des stocks FIFO/LIFO...\n";
    $stmt = $db->exec("
        UPDATE stock
        SET 
            cmup_actuel = NULL,
            valeur_stock_total = NULL,
            date_maj = NOW()
        WHERE methode_valorisation_stock_id IN (
            SELECT id FROM methode_valorisation_stock WHERE code IN ('FIFO', 'LIFO')
        )
        AND cmup_actuel IS NOT NULL
    ");
    
    echo "   ✅ {$stmt} stock(s) mis à jour\n\n";
    
    // ÉTAPE 3 : Vérifier la correction
    echo "3. Récapitulatif par méthode de valorisation:\n";
    $stmt = $db->query("
        SELECT 
            mvs.code as methode,
            COUNT(*) as nb_stocks,
            COUNT(CASE WHEN s.cmup_actuel IS NOT NULL THEN 1 END) as nb_avec_cmup,
            COUNT(CASE WHEN s.cmup_actuel IS NULL THEN 1 END) as nb_sans_cmup
        FROM stock s
        INNER JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
        GROUP BY mvs.code
        ORDER BY mvs.code
    ");
    
    while ($row = $stmt->fetch()) {
        echo "   {$row['methode']}: {$row['nb_stocks']} stock(s) total, {$row['nb_avec_cmup']} avec CMUP, {$row['nb_sans_cmup']} sans CMUP ";
        if ($row['methode'] === 'CMUP' && $row['nb_sans_cmup'] > 0) {
            echo "⚠️ ATTENTION: Stocks CMUP sans valeur!\n";
        } elseif (($row['methode'] === 'FIFO' || $row['methode'] === 'LIFO') && $row['nb_avec_cmup'] > 0) {
            echo "❌ ERREUR: Stocks FIFO/LIFO avec CMUP!\n";
        } else {
            echo "✅\n";
        }
    }
    
    echo "\n✅ CORRECTION TERMINÉE\n";
    echo "\n📋 RÉSUMÉ:\n";
    echo "   - Les stocks FIFO/LIFO n'ont plus de CMUP calculé\n";
    echo "   - Les futurs mouvements calculeront le CMUP uniquement pour les dépôts CMUP\n";
    echo "   - Pour les dépôts FIFO/LIFO, la valorisation se fera via les lots\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
