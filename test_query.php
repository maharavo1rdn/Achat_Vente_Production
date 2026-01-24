<?php
require 'backend/vendor/autoload.php';
// Mock Flight or minimal setup to test the model
$config = require 'backend/app/config/config.php';
$dsn = 'pgsql:host=' . $config['database']['host'] . ';port=' . ($config['database']['port'] ?? 5432) . ';dbname=' . $config['database']['dbname'];
try {
    $pdo = new PDO($dsn, $config['database']['user'], $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    require_once 'backend/app/models/AppModel.php';
    require_once 'backend/app/models/StatStockModel.php';
    
    $model = new app\models\StatStockModel($pdo);
    $data = $model->getTauxRotationStock(null, date('Y-m-d', strtotime('-1 year')), date('Y-m-d'));
    
    echo "SUCCESS: " . count($data) . " rows\n";
    print_r(array_slice($data, 0, 2));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}
