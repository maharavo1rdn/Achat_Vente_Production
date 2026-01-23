<?php

use app\controllers\ArticleController;
use app\controllers\StockController;
use app\controllers\AchatController;
use app\controllers\VenteController;
use app\controllers\CaisseController;
use app\controllers\EntrepriseController;
use app\controllers\PersonnelController;
use app\controllers\SiteController;
use app\controllers\DepotController;
use app\controllers\DashboardController;
use app\controllers\ProformaDemandeAchatController;

use flight\Engine;
use flight\net\Router;

/** 
 * @var Router $router 
 * @var Engine $app
 */

// Initialisation des controllers
$Dashboard_Controller = new DashboardController();
$Article_Controller = new ArticleController();
$Stock_Controller = new StockController();
$Achat_Controller = new AchatController();
$Vente_Controller = new VenteController();
$Caisse_Controller = new CaisseController();
$Entreprise_Controller = new EntrepriseController();
$Personnel_Controller = new PersonnelController();
$Site_Controller = new SiteController();
$Depot_Controller = new DepotController();
$ProformaDemandeAchat_Controller = new ProformaDemandeAchatController();

// Page d'accueil
$router->get('/', function () {
    Flight::json(['message' => 'API ERP Achat-Vente', 'version' => '1.0']);
});

$router->group('/api/dashboard', function () use ($router, $Dashboard_Controller) {
    $router->get('/stats', [$Dashboard_Controller, 'getStatistics']);
    $router->get('/recent-ventes', [$Dashboard_Controller, 'getRecentVentes']);
    $router->get('/recent-achats', [$Dashboard_Controller, 'getRecentAchats']);
});

$router->group('/api/articles', function () use ($router, $Article_Controller) {
    $router->get('/categories', [$Article_Controller, 'getCategories']);
    $router->get('', [$Article_Controller, 'getAll']);
    $router->get('/@id:[0-9]+', [$Article_Controller, 'getById']);
    $router->post('', [$Article_Controller, 'create']);
    $router->put('/@id:[0-9]+', [$Article_Controller, 'update']);
    $router->delete('/@id:[0-9]+', [$Article_Controller, 'delete']);
    $router->get('/categorie/@categorieId:[0-9]+', [$Article_Controller, 'getByCategorie']);
    $router->get('/@id:[0-9]+/stock', [$Article_Controller, 'getStockByArticle']);
    $router->get('/@id:[0-9]+/mouvements', [$Article_Controller, 'getMouvementsByArticle']);
});

$router->group('/api/stock', function () use ($router, $Stock_Controller) {
    $router->get('', [$Stock_Controller, 'getStock']);
    $router->get('/article/@articleId:[0-9]+', [$Stock_Controller, 'getStockByArticle']);
    $router->get('/mouvements', [$Stock_Controller, 'getMouvements']);
    $router->post('/mouvements', [$Stock_Controller, 'createMouvement']);
    $router->get('/historique/@articleId:[0-9]+', [$Stock_Controller, 'getHistoriqueArticle']);
    $router->get('/alerte', [$Stock_Controller, 'getStockAlerte']);
});

$router->group('/api/achats', function () use ($router, $Achat_Controller) {
    // Proforma Fournisseur
    $router->group('/proforma', function () use ($router, $Achat_Controller) {
        $router->get('', [$Achat_Controller, 'getAllProforma']);
        $router->get('/@id:[0-9]+', [$Achat_Controller, 'getProformaById']);
        $router->post('', [$Achat_Controller, 'createProforma']);
        $router->put('/@id:[0-9]+', [$Achat_Controller, 'updateProforma']);
        $router->delete('/@id:[0-9]+', [$Achat_Controller, 'deleteProforma']);
        $router->post('/@id:[0-9]+/convert-bc', [$Achat_Controller, 'convertProformaToBonCommande']);
    });

    // Bon de Commande Achat
    $router->group('/bon-commande', function () use ($router, $Achat_Controller) {
        $router->get('', [$Achat_Controller, 'getAllBonCommande']);
        $router->get('/@id:[0-9]+', [$Achat_Controller, 'getBonCommandeById']);
        $router->post('', [$Achat_Controller, 'createBonCommande']);
        $router->put('/@id:[0-9]+', [$Achat_Controller, 'updateBonCommande']);
        $router->delete('/@id:[0-9]+', [$Achat_Controller, 'deleteBonCommande']);
        $router->post('/@id:[0-9]+/convert-facture', [$Achat_Controller, 'convertBonCommandeToFacture']);
    });

    // Factures Achat
    $router->group('/factures', function () use ($router, $Achat_Controller) {
        $router->get('', [$Achat_Controller, 'getAllFactures']);
        $router->get('/@id:[0-9]+', [$Achat_Controller, 'getFactureById']);
        $router->post('', [$Achat_Controller, 'createFacture']);
        $router->put('/@id:[0-9]+', [$Achat_Controller, 'updateFacture']);
        $router->delete('/@id:[0-9]+', [$Achat_Controller, 'deleteFacture']);
        $router->post('/@id:[0-9]+/payer', [$Achat_Controller, 'payerFacture']);
    });
});

$router->group('/api/ventes', function () use ($router, $Vente_Controller) {
    // Devis Vente
    $router->group('/devis', function () use ($router, $Vente_Controller) {
        $router->get('', [$Vente_Controller, 'getAllDevis']);
        $router->get('/@id:[0-9]+', [$Vente_Controller, 'getDevisById']);
        $router->post('', [$Vente_Controller, 'createDevis']);
        $router->put('/@id:[0-9]+', [$Vente_Controller, 'updateDevis']);
        $router->delete('/@id:[0-9]+', [$Vente_Controller, 'deleteDevis']);
        $router->post('/@id:[0-9]+/convert-bc', [$Vente_Controller, 'convertDevisToBonCommande']);
    });

    $router->group('/bon-commande', function () use ($router, $Vente_Controller) {
        $router->get('', [$Vente_Controller, 'getAllBonCommande']);
        $router->get('/@id:[0-9]+', [$Vente_Controller, 'getBonCommandeById']);
        $router->post('', [$Vente_Controller, 'createBonCommande']);
        $router->put('/@id:[0-9]+', [$Vente_Controller, 'updateBonCommande']);
        $router->delete('/@id:[0-9]+', [$Vente_Controller, 'deleteBonCommande']);
        $router->post('/@id:[0-9]+/convert-facture', [$Vente_Controller, 'convertBonCommandeToFacture']);
    });

    $router->group('/factures', function () use ($router, $Vente_Controller) {
        $router->get('', [$Vente_Controller, 'getAllFactures']);
        $router->get('/@id:[0-9]+', [$Vente_Controller, 'getFactureById']);
        $router->post('', [$Vente_Controller, 'createFacture']);
        $router->put('/@id:[0-9]+', [$Vente_Controller, 'updateFacture']);
        $router->delete('/@id:[0-9]+', [$Vente_Controller, 'deleteFacture']);
        $router->post('/@id:[0-9]+/encaisser', [$Vente_Controller, 'encaisserFacture']);
    });
});

$router->group('/api/caisse', function () use ($router, $Caisse_Controller) {
    $router->get('', [$Caisse_Controller, 'getAllCaisses']);
    $router->get('/@id:[0-9]+', [$Caisse_Controller, 'getCaisseById']);
    $router->post('', [$Caisse_Controller, 'createCaisse']);
    $router->put('/@id:[0-9]+', [$Caisse_Controller, 'updateCaisse']);
    $router->get('/mouvements', [$Caisse_Controller, 'getAllMouvements']);
    $router->get('/@id:[0-9]+/mouvements', [$Caisse_Controller, 'getMouvementsByCaisse']);
    $router->post('/@id:[0-9]+/entree', [$Caisse_Controller, 'createEntree']);
    $router->post('/@id:[0-9]+/sortie', [$Caisse_Controller, 'createSortie']);
    $router->get('/@id:[0-9]+/solde', [$Caisse_Controller, 'getSolde']);
    $router->get('/paiements-vente', [$Caisse_Controller, 'getPaiementsVente']);
    $router->get('/paiements-achat', [$Caisse_Controller, 'getPaiementsAchat']);
    $router->post('/factures-vente/@id:[0-9]+/payer', [$Caisse_Controller, 'enregistrerPaiementVente']);
    $router->post('/factures-achat/@id:[0-9]+/payer', [$Caisse_Controller, 'enregistrerPaiementAchat']);
});

$router->group('/api/entreprises', function () use ($router, $Entreprise_Controller) {
    $router->get('', [$Entreprise_Controller, 'getAll']);
    $router->get('/@id:[0-9]+', [$Entreprise_Controller, 'getById']);
    $router->post('', [$Entreprise_Controller, 'create']);
    $router->put('/@id:[0-9]+', [$Entreprise_Controller, 'update']);
    $router->delete('/@id:[0-9]+', [$Entreprise_Controller, 'delete']);
    $router->get('/type/@type', [$Entreprise_Controller, 'getByType']);
});

$router->group('/api/sites', function () use ($router, $Site_Controller) {
    $router->get('', [$Site_Controller, 'getAll']);
    $router->get('/@id:[0-9]+', [$Site_Controller, 'getById']);
    $router->post('', [$Site_Controller, 'create']);
    $router->put('/@id:[0-9]+', [$Site_Controller, 'update']);
    $router->delete('/@id:[0-9]+', [$Site_Controller, 'delete']);
    $router->get('/entreprise/@entrepriseId:[0-9]+', [$Site_Controller, 'getByEntreprise']);
    $router->post('/@id:[0-9]+/active', [$Site_Controller, 'setActive']);
});

$router->group('/api/depots', function () use ($router, $Depot_Controller) {
    $router->get('', [$Depot_Controller, 'getAll']);
    $router->get('/@id:[0-9]+', [$Depot_Controller, 'getById']);
    $router->post('', [$Depot_Controller, 'create']);
    $router->put('/@id:[0-9]+', [$Depot_Controller, 'update']);
    $router->delete('/@id:[0-9]+', [$Depot_Controller, 'delete']);
    $router->get('/site/@siteId:[0-9]+', [$Depot_Controller, 'getBySite']);
    $router->get('/entreprise/@entrepriseId:[0-9]+', [$Depot_Controller, 'getByEntreprise']);
    $router->post('/@id:[0-9]+/active', [$Depot_Controller, 'setActive']);
});

$router->group('/api/personnel', function () use ($router, $Personnel_Controller) {
    $router->get('', [$Personnel_Controller, 'getAll']);
    $router->get('/@id:[0-9]+', [$Personnel_Controller, 'getById']);
    $router->post('', [$Personnel_Controller, 'create']);
    $router->put('/@id:[0-9]+', [$Personnel_Controller, 'update']);
    $router->delete('/@id:[0-9]+', [$Personnel_Controller, 'delete']);
    $router->post('/@id:[0-9]+/reset-password', [$Personnel_Controller, 'resetPassword']);
    $router->get('/role/@roleId:[0-9]+', [$Personnel_Controller, 'getByRole']);
    $router->get('/filiale/@filialeId:[0-9]+', [$Personnel_Controller, 'getByFiliale']);
    $router->get('/roles', [$Personnel_Controller, 'getRoles']);
    $router->post('/authenticate', [$Personnel_Controller, 'authenticate']);
});

$router->group('/api/proforma-demande-achat', function () use ($router, $ProformaDemandeAchat_Controller) {
    $router->get('', [$ProformaDemandeAchat_Controller, 'getAll']);
    $router->get('/@id:[0-9]+', [$ProformaDemandeAchat_Controller, 'getById']);
    $router->post('', [$ProformaDemandeAchat_Controller, 'create']);
    $router->put('/@id:[0-9]+', [$ProformaDemandeAchat_Controller, 'update']);
    $router->delete('/@id:[0-9]+', [$ProformaDemandeAchat_Controller, 'delete']);
    $router->get('/@id:[0-9]+/details', [$ProformaDemandeAchat_Controller, 'getDetails']);
    $router->post('/@id:[0-9]+/valider', [$ProformaDemandeAchat_Controller, 'valider']);
    $router->post('/@id:[0-9]+/annuler', [$ProformaDemandeAchat_Controller, 'annuler']);
    $router->post('/@id:[0-9]+/generer-proforma', [$ProformaDemandeAchat_Controller, 'genererProforma']);
});

$router->map('/*', function () {
    Flight::json([
        'error' => true,
        'message' => 'Route not found'
    ], 404);
});
