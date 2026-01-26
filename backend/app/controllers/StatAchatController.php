<?php

namespace app\controllers;

use Exception;
use Flight;

class StatAchatController {

    public function getDepensesParFournisseur() {
        try {
            $startDate = Flight::request()->query->start_date ?? null;
            $endDate = Flight::request()->query->end_date ?? null;
            $limit = Flight::request()->query->limit ?? 10;
            
            $periode = null;
            if ($startDate && $endDate) {
                $periode = [$startDate, $endDate];
            }
            
            $statModel = new \app\models\StatAchatModel(Flight::db());
            $data = $statModel->getDepensesParFournisseur($periode, $limit);
            Flight::json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getDelaiMoyenLivraison() {
        try {
            $startDate = Flight::request()->query->start_date ?? null;
            $endDate = Flight::request()->query->end_date ?? null;
            
            $periode = null;
            if ($startDate && $endDate) {
                $periode = [$startDate, $endDate];
            }
            
            $statModel = new \app\models\StatAchatModel(Flight::db());
            $data = $statModel->getDelaiMoyenLivraison($periode);
            Flight::json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getTauxServiceFournisseur() {
        try {
            $startDate = Flight::request()->query->start_date ?? null;
            $endDate = Flight::request()->query->end_date ?? null;
            
            $periode = null;
            if ($startDate && $endDate) {
                $periode = [$startDate, $endDate];
            }
            
            $statModel = new \app\models\StatAchatModel(Flight::db());
            $data = $statModel->getTauxServiceFournisseur($periode);
            Flight::json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint groupé pour tous les stats achats
     */
    public function getAllStats() {
        try {
            $startDate = Flight::request()->query->start_date ?? null;
            $endDate = Flight::request()->query->end_date ?? null;
            
            // Build period array if both dates provided
            $periode = null;
            if ($startDate && $endDate) {
                $periode = [$startDate, $endDate];
            }
            
            $statModel = new \app\models\StatAchatModel(Flight::db());
            
            $stats = [
                'success' => true,
                'data' => [
                    'depenses_par_fournisseur' => $statModel->getDepensesParFournisseur($periode),
                    'delai_moyen_livraison' => $statModel->getDelaiMoyenLivraison($periode),
                    'taux_service_fournisseur' => $statModel->getTauxServiceFournisseur($periode)
                ]
            ];
            Flight::json($stats);
        } catch (Exception $e) {
            error_log("StatAchatController ERROR: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Flight::json([
                'success' => false, 
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
