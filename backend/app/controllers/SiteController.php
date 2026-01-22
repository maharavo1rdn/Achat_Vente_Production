<?php

namespace app\controllers;

use Exception;
use Flight;

class SiteController
{

    public function getAll()
    {
        try {
            $raw = (array) Flight::request()->query;
            $filters = [];
            foreach ($raw as $k => $v) {
                if ($v === null || $v === '') continue;
                $filters[$k] = is_string($v) ? trim($v) : $v;
            }

            $sites = Flight::siteModel()->getAll($filters);
            Flight::json($sites);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($id)
    {
        try {
            $site = Flight::siteModel()->getById($id);
            if ($site) Flight::json($site);
            else Flight::json(['error' => 'Site non trouvé'], 404);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        try {
            $data = (array) Flight::request()->data;

            if (isset($data['est_actif'])) {
                $data['est_actif'] = filter_var($data['est_actif'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($data['est_actif'] === null) $data['est_actif'] = true;
            }

            if (isset($data['entreprise_id']))
                $data['entreprise_id'] = $data['entreprise_id'] === '' ? null : (int)$data['entreprise_id'];

            $newId = Flight::siteModel()->create($data);
            Flight::json(['id' => (int)$newId], 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id)
    {
        try {
            $data = (array) Flight::request()->data;

            if (isset($data['est_actif'])) {
                $data['est_actif'] = filter_var($data['est_actif'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($data['est_actif'] === null) $data['est_actif'] = true;
            }

            if (isset($data['entreprise_id']))
                $data['entreprise_id'] === '' ? $data['entreprise_id'] = null : $data['entreprise_id'] = (int)$data['entreprise_id'];


            $result = Flight::siteModel()->update($id, $data);
            if ($result) Flight::json(['success' => true]);
            else Flight::json(['error' => 'Aucune modification effectuée ou site introuvable'], 404);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $result = Flight::siteModel()->delete($id);
            Flight::json(['success' => (bool)$result]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getByEntreprise($entrepriseId)
    {
        try {
            $result = Flight::siteModel()->getByEntreprise($entrepriseId);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function setActive($id)
    {
        try {
            $payload = (array) Flight::request()->data;
            if (!isset($payload['active'])) {
                Flight::json(['error' => 'Paramètre "active" requis'], 400);
                return;
            }

            $active = filter_var($payload['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($active === null) {
                Flight::json(['error' => 'Valeur "active" invalide'], 400);
                return;
            }

            $result = Flight::siteModel()->setActive($id, $active);
            if ($result) Flight::json(['success' => true]);
            else Flight::json(['error' => 'Site introuvable ou non modifié'], 404);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
