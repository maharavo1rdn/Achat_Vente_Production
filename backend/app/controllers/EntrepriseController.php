<?php

namespace app\controllers;

use Exception;
use Flight;

class EntrepriseController
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

            $entreprises = Flight::entrepriseModel()->getAll($filters);
            Flight::json($entreprises);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($id)
    {
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

    public function create()
    {
        try {
            $data = Flight::request()->data;

            if (isset($data['est_actif'])) {
                $data['est_actif'] = filter_var($data['est_actif'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($data['est_actif'] === null) $data['est_actif'] = true;
            }

            if (isset($data['groupe_id']))
                $data['groupe_id'] =  $data['groupe_id'] === '' ? null : (int)$data['groupe_id'];

            $newId = Flight::entrepriseModel()->create($data);

            Flight::json(['id' => (int)$newId], 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id)
    {
        try {
            $data = Flight::request()->data;

            if (isset($data['est_actif'])) {
                $data['est_actif'] = filter_var($data['est_actif'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($data['est_actif'] === null) $data['est_actif'] = true;
            }

            if (isset($data['groupe_id']))
                $data['groupe_id'] = $data['groupe_id'] === '' ? null : (int)$data['groupe_id'];

            $result = Flight::entrepriseModel()->update($id, $data);
            if ($result) {
                Flight::json(['success' => true]);
            } else {
                Flight::json(['error' => 'Aucune modification effectuée ou entreprise introuvable'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $result = Flight::entrepriseModel()->delete($id);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getByType($type)
    {
        try {
            $entreprises = Flight::entrepriseModel()->getByType($type);
            Flight::json($entreprises);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getClients()
    {
        try {
            $clients = Flight::entrepriseModel()->getClients();
            Flight::json($clients);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getFournisseurs()
    {
        try {
            $fournisseurs = Flight::entrepriseModel()->getFournisseurs();
            Flight::json($fournisseurs);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getByGroupe($groupeId)
    {
        try {
            $result = Flight::entrepriseModel()->getByGroupe($groupeId);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function setActive($id)
    {
        try {
            $payload = Flight::request()->data;
            if (!isset($payload['active'])) {
                Flight::json(['error' => 'Paramètre "active" requis'], 400);
                return;
            }

            $active = filter_var($payload['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($active === null) {
                Flight::json(['error' => 'Valeur "active" invalide'], 400);
                return;
            }

            $result = Flight::entrepriseModel()->setActive($id, $active);
            if ($result) Flight::json(['success' => true]);
            else Flight::json(['error' => 'Entreprise introuvable ou non modifiée'], 404);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
