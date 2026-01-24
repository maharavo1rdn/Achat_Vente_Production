<?php
// New file: app\controllers\StatCommercialController.php

namespace app\controllers;

use Flight;

class StatCommercialController {
    public function getPerformanceCommercial() {
        $model = Flight::statCommercialModel();
        $startDate = Flight::request()->query['start_date'] ?? date('Y-m-01');
        $endDate = Flight::request()->query['end_date'] ?? date('Y-m-t');
        $data = $model->getPerformanceCommercial($startDate, $endDate);
        Flight::json($data);
    }

    public function getTauxFidelisation() {
        $model = Flight::statCommercialModel();
        $startDate = Flight::request()->query['start_date'] ?? date('Y-01-01');
        $endDate = Flight::request()->query['end_date'] ?? date('Y-m-d');
        $data = $model->getTauxFidelisation($startDate, $endDate);
        Flight::json($data);
    }

    public function getNouveauxClients() {
        $model = Flight::statCommercialModel();
        $startDate = Flight::request()->query['start_date'] ?? date('Y-m-01');
        $endDate = Flight::request()->query['end_date'] ?? date('Y-m-t');
        $data = $model->getNouveauxClients($startDate, $endDate);
        Flight::json(['nouveaux_clients' => $data]);
    }
}