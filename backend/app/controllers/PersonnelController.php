<?php

namespace app\controllers;

use Exception;
use Flight;

class PersonnelController
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

            $result = Flight::personnelModel()->getAll($filters);
            // Normalize response: { data, total, page, per_page }
            if (is_array($result) && isset($result['data'])) {
                Flight::json([
                    'data' => $result['data'],
                    'total' => $result['total'] ?? count($result['data']),
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? count($result['data'])
                ]);
            } else {
                Flight::json(['data' => $result]);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($id)
    {
        try {
            $personne = Flight::personnelModel()->getById($id);
            if ($personne) {
                Flight::json($personne);
            } else {
                Flight::json(['error' => 'Personnel non trouvé'], 404);
            }
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

            if (isset($data['site_defaut_id']))
                $data['site_defaut_id'] = $data['site_defaut_id'] === '' ? null : (int)$data['site_defaut_id'];

            $result = Flight::personnelModel()->create($data);
            Flight::json(['id' => (int)$result], 201);
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
                $data['entreprise_id'] = $data['entreprise_id'] === '' ? null : (int)$data['entreprise_id'];

            if (isset($data['site_defaut_id']))
                $data['site_defaut_id'] = $data['site_defaut_id'] === '' ? null : (int)$data['site_defaut_id'];

            $result = Flight::personnelModel()->update($id, $data);
            if ($result) Flight::json(['success' => true]);
            else Flight::json(['error' => 'Aucune modification effectuée ou personnel introuvable'], 404);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $result = Flight::personnelModel()->delete($id);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function resetPassword($id)
    {
        try {
            $payload = (array) Flight::request()->data;
            $newPassword = $payload['password'] ?? null;
            $result = Flight::personnelModel()->resetPassword($id, $newPassword);
            Flight::json(['success' => (bool)$result]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getByRole($roleId)
    {
        try {
            $personnel = Flight::personnelModel()->getByRole($roleId);
            Flight::json($personnel);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getByFiliale($filialeId)
    {
        try {
            $personnel = Flight::personnelModel()->getByFiliale($filialeId);
            Flight::json($personnel);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getRoles()
    {
        try {
            $roles = Flight::personnelModel()->getRoles();
            Flight::json($roles);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function authenticate()
    {
        try {
            $data = Flight::request()->data;
            $user = Flight::personnelModel()->authenticate($data->email, $data->password);
            if ($user) {
                Flight::json($user);
            } else {
                Flight::json(['error' => 'Identifiants incorrects'], 401);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function changePassword($id)
    {
        try {
            $data = Flight::request()->data;
            $result = Flight::personnelModel()->changePassword($id, $data->old_password, $data->new_password);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}