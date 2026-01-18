<?php

namespace app\controllers;

use Exception;
use Flight;

class PersonnelController
{

    public function getAll()
    {
        try {
            $filters = Flight::request()->query;
            $personnel = Flight::personnelModel()->getAll($filters);
            Flight::json($personnel);
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
            $data = Flight::request()->data;
            $result = Flight::personnelModel()->create($data);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id)
    {
        try {
            $data = Flight::request()->data;
            $result = Flight::personnelModel()->update($id, $data);
            Flight::json($result);
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
            $result = Flight::personnelModel()->resetPassword($id);
            Flight::json($result);
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