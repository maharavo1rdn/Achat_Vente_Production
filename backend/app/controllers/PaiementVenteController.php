<?php

namespace app\controllers;

use Exception;
use Flight;

class PaiementVenteController
{
    public function getAll()
    {
        try {
            $filters = Flight::request()->query;
            $result = Flight::paiementVenteModel()->getAll($filters);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($id)
    {
        try {
            $paiement = Flight::paiementVenteModel()->getById($id);
            if ($paiement) {
                Flight::json($paiement);
            } else {
                Flight::json(['error' => 'Paiement non trouvé'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        try {
            $data = (array)Flight::request()->data;
            $id = Flight::paiementVenteModel()->create($data);
            Flight::json(['id' => $id], 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function applyPayment($id)
    {
        try {
            $data = (array)Flight::request()->data;
            $montant = $data['montant'] ?? null;
            if ($montant === null) throw new Exception('montant requis');

            $ok = Flight::paiementVenteModel()->applyPayment($id, $montant);
            Flight::json(['success' => $ok]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
    public function validate($id)
    {
        try {
            $data = (array)Flight::request()->data;
            $userId = $data['user_id'] ?? null;
            $caisseId = $data['caisse_id'] ?? null;
            $personnelId = $data['personnel_id'] ?? null;
            $libelle = $data['libelle'] ?? null;

            $ok = Flight::paiementVenteModel()->validate((int)$id, $userId, $caisseId, $personnelId, $libelle);
            Flight::json(['success' => $ok]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
