<?php

namespace app\controllers;

use Exception;
use Flight;

class StockController {

    public function getStock() {
        try {
            $filters = Flight::request()->query;
            $stocks = Flight::stockModel()->getStock($filters);
            Flight::json($stocks);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getMouvements() {
        try {
            $filters = Flight::request()->query;
            $mouvements = Flight::stockModel()->getMouvements($filters);
            Flight::json($mouvements);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function createMouvement() {
        try {
            $data = Flight::request()->data;
            $result = Flight::stockModel()->createMouvement($data);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getHistoriqueArticle($articleId) {
        try {
            $filialeId = Flight::request()->query->filiale_id ?? null;
            $historique = Flight::stockModel()->getHistoriqueArticle($articleId, $filialeId);
            Flight::json($historique);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getStockAlerte() {
        try {
            $alertes = Flight::stockModel()->getStockAlerte();
            Flight::json($alertes);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getStockByArticle($articleId) {
        try {
            $filialeId = Flight::request()->query->filiale_id ?? null;
            $stocks = Flight::stockModel()->getStockByArticle($articleId, $filialeId);
            Flight::json($stocks);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getStockValorise() {
        try {
            $filters = Flight::request()->query;
            $valorise = Flight::stockModel()->getStockValorise($filters);
            Flight::json($valorise);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getStockConsolideGroupe() {
        try {
            $res = Flight::stockModel()->getStockConsolideGroupe();
            Flight::json($res);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getStructureOrganisation() {
        try {
            $res = Flight::stockModel()->getStructureOrganisation();
            Flight::json($res);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDepotInfo($depotId) {
        try {
            $depotInfo = Flight::stockModel()->getDepotInfo($depotId);
            Flight::json($depotInfo);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getStockByDepot($depotId) {
        try {
            $stockArticles = Flight::stockModel()->getStockByDepot($depotId);
            Flight::json($stockArticles);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getLotsByDepot($depotId) {
        try {
            $lots = Flight::stockModel()->getLotsByDepot($depotId);
            Flight::json($lots);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getMouvementsByArticle($articleId) {
        try {
            $depotId = Flight::request()->query->depot_id ?? null;
            $mouvements = Flight::stockModel()->getMouvementsByArticle($articleId, $depotId);
            Flight::json($mouvements);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
