<?php

namespace app\controllers;

use Exception;
use Flight;

class StatVenteController {

    public function getCA_ParSociete() {
        try {
            $startDate = Flight::request()->query->start_date ?? null;
            $endDate = Flight::request()->query->end_date ?? null;
            
            $periode = null;
            if ($startDate && $endDate) {
                $periode = [$startDate, $endDate];
            }
            
            $statModel = new \app\models\StatVenteModel(Flight::db());
            $data = $statModel->getCA_ParSociete($periode);
            Flight::json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getEvolutionCA() {
        try {
            $nbMois = Flight::request()->query->nb_mois ?? 12;
            
            $statModel = new \app\models\StatVenteModel(Flight::db());
            $data = $statModel->getEvolutionCA($nbMois);
            Flight::json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getPanierMoyen() {
        try {
            $startDate = Flight::request()->query->start_date ?? null;
            $endDate = Flight::request()->query->end_date ?? null;
            
            $periode = null;
            if ($startDate && $endDate) {
                $periode = [$startDate, $endDate];
            }
            
            $statModel = new \app\models\StatVenteModel(Flight::db());
            $data = $statModel->getPanierMoyen($periode);
            Flight::json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getTauxConversion() {
        try {
            $startDate = Flight::request()->query->start_date ?? null;
            $endDate = Flight::request()->query->end_date ?? null;
            
            $periode = null;
            if ($startDate && $endDate) {
                $periode = [$startDate, $endDate];
            }
            
            $statModel = new \app\models\StatVenteModel(Flight::db());
            $data = $statModel->getTauxConversion($periode);
            Flight::json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint groupé pour toutes les stats ventes
     */
    public function getAllStats() {
        try {
            $startDate = Flight::request()->query->start_date ?? null;
            $endDate = Flight::request()->query->end_date ?? null;
            $nbMois = Flight::request()->query->nb_mois ?? 12;
            
            $periode = null;
            if ($startDate && $endDate) {
                $periode = [$startDate, $endDate];
            }
            
            $statModel = new \app\models\StatVenteModel(Flight::db());
            
            $stats = [
                'success' => true,
                'data' => [
                    'ca_par_societe' => $statModel->getCA_ParSociete($periode),
                    'evolution_ca' => $statModel->getEvolutionCA($nbMois),
                    'panier_moyen' => $statModel->getPanierMoyen($periode),
                    'taux_conversion' => $statModel->getTauxConversion($periode)
                ]
            ];
            Flight::json($stats);
        } catch (Exception $e) {
            error_log("StatVenteController ERROR: " . $e->getMessage());
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
