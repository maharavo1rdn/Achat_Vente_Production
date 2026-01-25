<?php

namespace app\controllers;

use Exception;
use Flight;

class ModePaiementController
{
    public function getAll()
    {
        try {
            $result = Flight::modePaiementModel()->getAll();
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($id)
    {
        try {
            $row = Flight::modePaiementModel()->getById($id);
            if ($row) Flight::json($row);
            else Flight::json(['error' => 'Not found'], 404);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        try {
            $data = Flight::request()->data;
            $id = Flight::modePaiementModel()->create($data);
            Flight::json(['id' => $id], 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id)
    {
        try {
            $data = Flight::request()->data;
            $ok = Flight::modePaiementModel()->update((int)$id, $data);
            Flight::json(['success' => $ok]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $ok = Flight::modePaiementModel()->delete((int)$id);
            Flight::json(['success' => $ok]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
