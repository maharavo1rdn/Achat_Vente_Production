<?php

namespace app\controllers;

use Flight;
use Exception;

class StatFinanceController {

    public function getEncoursClients() {
        try {
            $data = Flight::statFinanceModel()->getEncoursClients();
            Flight::json($data);
        } catch (Exception $e) {
            Flight::halt(500, json_encode(['error' => $e->getMessage()]));
        }
    }

    public function getEncoursFournisseurs() {
        try {
            $data = Flight::statFinanceModel()->getEncoursFournisseurs();
            Flight::json($data);
        } catch (Exception $e) {
            Flight::halt(500, json_encode(['error' => $e->getMessage()]));
        }
    }

    public function getTresorerieNet() {
        try {
            $data = Flight::statFinanceModel()->getTresorerieNet();
            Flight::json($data);
        } catch (Exception $e) {
            Flight::halt(500, json_encode(['error' => $e->getMessage()]));
        }
    }

    public function getBFR() {
        try {
            $data = Flight::statFinanceModel()->getBFR();
            Flight::json($data);
        } catch (Exception $e) {
            Flight::halt(500, json_encode(['error' => $e->getMessage()]));
        }
    }

    public function getAllStats() {
        try {
            $model = Flight::statFinanceModel();
            Flight::json([
                'encours_clients' => $model->getEncoursClients(),
                'encours_fournisseurs' => $model->getEncoursFournisseurs(),
                'tresorerie_net' => $model->getTresorerieNet(),
                'bfr' => $model->getBFR()
            ]);
        } catch (Exception $e) {
            Flight::halt(500, json_encode(['error' => $e->getMessage()]));
        }
    }
}
