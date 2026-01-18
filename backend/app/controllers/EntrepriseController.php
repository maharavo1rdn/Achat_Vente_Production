<?php

namespace app\controllers;

use Exception;
use Flight;

class EntrepriseController {

    public function getAll() {
        try {
            $filters = Flight::request()->query;
            $entreprises = Flight::entrepriseModel()->getAll($filters);
            Flight::json($entreprises);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($id) {
        try {
            $entreprise = Flight::entrepriseModel()->getById($id);
            if ($entreprise) {
                Flight::json($entreprise);
            } else {
                Flight::json(['error' => 'Entreprise non trouvée'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function create() {
        try {
            $data = Flight::request()->data;
            $result = Flight::entrepriseModel()->create($data);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id) {
        try {
            $data = Flight::request()->data;
            $result = Flight::entrepriseModel()->update($id, $data);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id) {
        try {
            $result = Flight::entrepriseModel()->delete($id);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getByType($type) {
        try {
            $entreprises = Flight::entrepriseModel()->getByType($type);
            Flight::json($entreprises);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getClients() {
        try {
            $clients = Flight::entrepriseModel()->getClients();
            Flight::json($clients);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getFournisseurs() {
        try {
            $fournisseurs = Flight::entrepriseModel()->getFournisseurs();
            Flight::json($fournisseurs);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
