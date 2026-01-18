<?php

namespace app\controllers;

use Exception;
use Flight;

class DashboardController {

    public function getStatistics() {
        try {
            $stats = Flight::dashboardModel()->getStatistics();
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
