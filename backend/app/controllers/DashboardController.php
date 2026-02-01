<?php

namespace app\controllers;

use Exception;
use Flight;
use app\models\StatistiqueModel;

class DashboardController {

    public function getStatistics() {
        try {
            $periode = Flight::request()->query->periode ?? null;
            $statModel = new StatistiqueModel(Flight::db());
            $stats = [
                'totalCA' => $statModel->getCA_Total($periode),
                'margeBrute' => $statModel->getMargeBrute_Total($periode),
                'topClients' => $statModel->getTop5_Clients($periode),
                'topArticles' => $statModel->getTop5_Articles($periode),
                'tauxRentabilite' => $statModel->getTaux_Rentabilite($periode)
            ];
            Flight::json($stats);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getRecentVentes() {
        try {
            $limit = Flight::request()->query->limit ?? 10;
            $ventes = Flight::dashboardModel()->getRecentVentes($limit);
            Flight::json($ventes);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getRecentAchats() {
        try {
            $limit = Flight::request()->query->limit ?? 10;
            $achats = Flight::dashboardModel()->getRecentAchats($limit);
            Flight::json($achats);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
