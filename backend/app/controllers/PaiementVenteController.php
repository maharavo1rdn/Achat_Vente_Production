<?php

namespace app\controllers;

use InvalidArgumentException;
use Exception;
use PDO;
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
            $data = Flight::request()->data;
            $id = Flight::paiementVenteModel()->create($data);
            Flight::json(['id' => $id], 201);
        } catch (InvalidArgumentException $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id)
    {
        try {
            $data = Flight::request()->data;
            $ok = Flight::paiementVenteModel()->update((int)$id, (array)$data);
            Flight::json(['success' => $ok]);
        } catch (InvalidArgumentException $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function filters()
    {
        try {
            $entrepriseId = Flight::request()->query->entreprise_id ?? null;

            // modes
            $modes = Flight::modePaiementModel()->getAll();
            // statuts
            $stmt = Flight::db()->prepare('SELECT id, code, libelle FROM statut ORDER BY id');
            $stmt->execute();
            $statuts = $stmt->fetchAll(
                \PDO::FETCH_ASSOC
            );

            // caisses
            if ($entrepriseId) {
                $stmt2 = Flight::db()->prepare('SELECT id, libelle, solde_actuel FROM caisse WHERE entreprise_id = ?');
                $stmt2->execute([(int)$entrepriseId]);
                $caisses = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt2 = Flight::db()->prepare('SELECT id, libelle, solde_actuel FROM caisse');
                $stmt2->execute();
                $caisses = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            }

            // personnels (optionnel filter by entreprise)
            if ($entrepriseId) {
                $stmt3 = Flight::db()->prepare('SELECT id, nom, prenom FROM personnel WHERE entreprise_id = ?');
                $stmt3->execute([(int)$entrepriseId]);
                $personnels = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt3 = Flight::db()->prepare('SELECT id, nom, prenom FROM personnel');
                $stmt3->execute();
                $personnels = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            }

            Flight::json(['modes' => $modes, 'statuts' => $statuts, 'caisses' => $caisses, 'personnels' => $personnels]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function applyPayment($id)
    {
        try {
            $data = Flight::request()->data;
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
            $data = Flight::request()->data;
            $userId = $data['user_id'] ?? null;
            $caisseId = $data['caisse_id'] ?? null;
            $personnelId = $data['personnel_id'] ?? null;
            $libelle = $data['libelle'] ?? null;

            $ok = Flight::paiementVenteModel()->validate((int)$id, $userId, $caisseId, $personnelId, $libelle);
            Flight::json(['success' => $ok]);
        } catch (InvalidArgumentException $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
