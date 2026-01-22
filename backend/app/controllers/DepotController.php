<?php

namespace app\controllers;

use Exception;
use Flight;

class DepotController
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

            $depots = Flight::depotModel()->getAll($filters);
            Flight::json($depots);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($id)
    {
        try {
            $depot = Flight::depotModel()->getById($id);
            if ($depot) Flight::json($depot);
            else Flight::json(['error' => 'Dépôt non trouvé'], 404);
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

            if (isset($data['site_id']))
                $data['site_id'] = $data['site_id'] === '' ? null : (int)$data['site_id'];

            $newId = Flight::depotModel()->create($data);
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

            if (isset($data['site_id']))
                $data['site_id'] = $data['site_id'] === '' ? null : (int)$data['site_id'];

            $result = Flight::depotModel()->update($id, $data);
            if ($result) Flight::json(['success' => true]);
            else Flight::json(['error' => 'Aucune modification effectuée ou dépôt introuvable'], 404);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $result = Flight::depotModel()->delete($id);
            Flight::json(['success' => (bool)$result]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getByEntreprise($entrepriseId)
    {
        try {
            $result = Flight::depotModel()->getByEntreprise($entrepriseId);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getBySite($siteId)
    {
        try {
            $result = Flight::depotModel()->getBySite($siteId);
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

            $result = Flight::depotModel()->setActive($id, $active);
            if ($result) Flight::json(['success' => true]);
            else Flight::json(['error' => 'Dépôt introuvable ou non modifié'], 404);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
