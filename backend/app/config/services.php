<?php

use flight\Engine;
use flight\database\PdoWrapper;
use flight\debug\database\PdoQueryCapture;
use Tracy\Debugger;
use app\models\ProductModel;
use app\models\AppModel;
use app\models\ArticleModel;
use app\models\DepotModel;
use app\models\StockModel;
use app\models\AchatModel;
use app\models\VenteModel;
use app\models\CaisseModel;
use app\models\EntrepriseModel;
use app\models\PersonnelModel;
use app\models\DashboardModel;
use app\models\SiteModel;

/** 
 * @var array $config This comes from the returned array at the bottom of the config.php file
 * @var Engine $app
 */

// PostgreSQL DSN
$dsn = 'pgsql:host=' . $config['database']['host'] . ';port=' . ($config['database']['port'] ?? 5432) . ';dbname=' . $config['database']['dbname'];

// uncomment the following line for SQLite
// $dsn = 'sqlite:' . $config['database']['file_path'];

// Uncomment the below lines if you want to add a Flight::db() service
// In development, you'll want the class that captures the queries for you. In production, not so much.
$pdoClass = Debugger::$showBar === true ? PdoQueryCapture::class : PdoWrapper::class;
$app->register('db', $pdoClass, [$dsn, $config['database']['user'] ?? null, $config['database']['password'] ?? null]);

// Got google oauth stuff? You could register that here
// $app->register('google_oauth', Google_Client::class, [ $config['google_oauth'] ]);

// Redis? This is where you'd set that up
// $app->register('redis', Redis::class, [ $config['redis']['host'], $config['redis']['port'] ]);

Flight::db()->exec("SET client_encoding = 'UTF8'");

// Register models used by the application
Flight::map('productModel', function () {
    return new ProductModel(Flight::db());
});

Flight::map('appModel', function() {
    return new AppModel(Flight::db());
});

Flight::map('articleModel', function() {
    return new ArticleModel(Flight::db());
});

Flight::map('stockModel', function() {
    return new StockModel(Flight::db());
});

Flight::map('achatModel', function() {
    return new AchatModel(Flight::db());
});

Flight::map('venteModel', function() {
    return new VenteModel(Flight::db());
});

Flight::map('caisseModel', function() {
    return new CaisseModel(Flight::db());
});

Flight::map('entrepriseModel', function() {
    return new EntrepriseModel(Flight::db());
});

Flight::map('personnelModel', function() {
    return new PersonnelModel(Flight::db());
});

Flight::map('dashboardModel', function() {
    return new DashboardModel(Flight::db());
});

Flight::map('siteModel', function() {
    return new SiteModel(Flight::db());
});

Flight::map('depotModel', function() {
    return new DepotModel(Flight::db());
});