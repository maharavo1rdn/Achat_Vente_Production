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
}
