<?php

namespace app\controllers;

use Exception;
use Flight;

class StatutController
{
    public function getAll()
    {
        try {
            $statuts = Flight::statutModel()->getAll();
            Flight::json($statuts);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
