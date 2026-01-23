<?php

namespace app\controllers;

use Exception;
use Flight;

class ProformaFournisseurController
{
    public function getAll()
    {
        try {
            $raw = Flight::request()->query;
            $filters = [];
            foreach ($raw as $k => $v) {
                if ($v === null || $v === '') continue;
                $filters[$k] = is_string($v) ? trim($v) : $v;
            }

            $proformas = Flight::proformaFournisseurModel()->getAll($filters);
            Flight::json($proformas);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($id)
    {
        try {
            $proforma = Flight::proformaFournisseurModel()->getById($id);
            if (!$proforma) {
                Flight::json(['error' => 'Proforma non trouvée'], 404);
                return;
            }

            Flight::json($proforma);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        try {
            // Normalize request data to an array to avoid issues when Flight provides an object
            $data = Flight::request()->data;

            if (isset($data['details']) && is_string($data['details'])) {
                $data['details'] = json_decode($data['details'], true);
            }

            $newId = Flight::proformaFournisseurModel()->create($data);
            Flight::json(['id' => (int)$newId], 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id)
    {
        try {
            $data = Flight::request()->data;
            if (isset($data['details']) && is_string($data['details'])) {
                $data['details'] = json_decode($data['details'], true);
            }

            $result = Flight::proformaFournisseurModel()->update($id, $data);
            if ($result) {
                Flight::json(['success' => true]);
            } else {
                Flight::json(['error' => 'Aucune modification effectuée'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $result = Flight::proformaFournisseurModel()->delete($id);
            Flight::json(['success' => (bool)$result]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function valider($id)
    {
        try {
            $data = Flight::request()->data;
            $userId = isset($data['user_id']) ? (int)$data['user_id'] : null;

            if (!$userId) {
                Flight::json(['error' => 'user_id is required'], 400);
                return;
            }

            $personnel = Flight::personnelModel()->getById($userId);
            if (!$personnel) {
                Flight::json(['error' => 'Utilisateur introuvable'], 404);
                return;
            }

            if (!isset($personnel['niveau_acces']) || (int)$personnel['niveau_acces'] < 5) {
                throw new Exception('Droits insuffisants pour valider une proforma fournisseur');
            }

            $result = Flight::proformaFournisseurModel()->updateStatut($id, 3);
            if ($result) {
                Flight::json(['success' => true, 'message' => 'Proforma validée']);
            } else {
                Flight::json(['error' => 'Proforma non trouvée'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDetails($id)
    {
        try {
            $details = Flight::proformaFournisseurModel()->getDetails($id);
            Flight::json($details);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
