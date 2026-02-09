<?php
require 'vendor/autoload.php';
require 'app/config/bootstrap.php';

echo "=== TEST CMUP vs FIFO/LIFO ===\n\n";

try {
    $db = Flight::db();
    
    // 1. Afficher les dépôts et leurs méthodes
    echo "1. Dépôts par méthode de valorisation:\n";
    $stmt = $db->query("
        SELECT 
            d.id,
            d.nom,
            mvs.code as methode
        FROM depot d
        LEFT JOIN methode_valorisation_stock mvs ON d.methode_valorisation_stock_id = mvs.id
        ORDER BY mvs.code, d.nom
    ");
    
    $depotCMUP = null;
    $depotFIFO = null;
    
    while ($row = $stmt->fetch()) {
        echo "   Dépôt {$row['id']}: {$row['nom']} → {$row['methode']}\n";
        if ($row['methode'] === 'CMUP' && !$depotCMUP) {
            $depotCMUP = $row['id'];
        }
        if ($row['methode'] === 'FIFO' && !$depotFIFO) {
            $depotFIFO = $row['id'];
        }
    }
    echo "\n";
    
    if (!$depotCMUP || !$depotFIFO) {
        die("❌ Impossible de trouver un dépôt CMUP et un dépôt FIFO\n");
    }
    
    // 2. Trouver un article test
    $stmt = $db->query("SELECT id, reference, designation FROM article WHERE est_actif = true LIMIT 1");
    $article = $stmt->fetch();
    
    if (!$article) {
        die("❌ Aucun article trouvé\n");
    }
    
    echo "2. Article test: {$article['reference']} - {$article['designation']}\n\n";
    
    // 3. Vérifier le stock dans les deux dépôts
    echo "3. État des stocks AVANT:\n";
    
    $stmt = $db->prepare("
        SELECT 
            s.*,
            d.nom as depot_nom,
            mvs.code as methode
        FROM stock s
        INNER JOIN depot d ON s.depot_id = d.id
        INNER JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
        WHERE s.article_id = ?
          AND s.depot_id IN (?, ?)
    ");
    $stmt->execute([$article['id'], $depotCMUP, $depotFIFO]);
    
    while ($row = $stmt->fetch()) {
        echo "   {$row['depot_nom']} ({$row['methode']}):\n";
        echo "     - Quantité: {$row['quantite_actuelle']}\n";
        echo "     - CMUP: " . ($row['cmup_actuel'] ?? 'NULL') . "\n";
        echo "     - Valeur: " . ($row['valeur_stock_total'] ?? 'NULL') . "\n";
    }
    echo "\n";
    
    // 4. Résumé des règles
    echo "✅ RÈGLES DE VALORISATION:\n";
    echo "   • Dépôt CMUP (ID $depotCMUP): DOIT avoir cmup_actuel calculé\n";
    echo "   • Dépôt FIFO (ID $depotFIFO): NE DOIT PAS avoir cmup_actuel (NULL)\n";
    echo "   • Les futurs mouvements respecteront ces règles automatiquement\n\n";
    
    // 5. Vérifier qu'il n'y a plus de stocks FIFO/LIFO avec CMUP
    $stmt = $db->query("
        SELECT COUNT(*) as count
        FROM stock s
        INNER JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
        WHERE mvs.code IN ('FIFO', 'LIFO')
          AND s.cmup_actuel IS NOT NULL
    ");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "❌ ATTENTION: Il reste {$result['count']} stock(s) FIFO/LIFO avec un CMUP\n";
        echo "   Exécutez à nouveau: php fix_cmup_fifo_lifo.php\n";
    } else {
        echo "✅ Tous les stocks FIFO/LIFO ont cmup_actuel = NULL\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
