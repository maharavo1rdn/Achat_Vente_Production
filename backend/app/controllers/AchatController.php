<?php

namespace app\controllers;

use Exception;
use Flight;
class AchatController {

    public function convertProformaToBonCommande($id) {
        try {
            $result = Flight::achatModel()->convertProformaToBonCommande($id);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }


    public function getAllBonCommande() {
        try {
            $filters = Flight::request()->query;
            $bonCommandes = Flight::achatModel()->getAllBonCommande($filters);
            Flight::json($bonCommandes);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getBonCommandeById($id) {
        try {
            $bonCommande = Flight::achatModel()->getBonCommandeById($id);
            if ($bonCommande) {
                Flight::json($bonCommande);
            } else {
                Flight::json(['error' => 'Bon de commande non trouvé'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function createBonCommande() {
        try {
            $data = Flight::request()->data;
            $result = Flight::achatModel()->createBonCommande($data);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateBonCommande($id) {
        try {
            $data = Flight::request()->data;
            $result = Flight::achatModel()->updateBonCommande($id, $data);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteBonCommande($id) {
        try {
            $result = Flight::achatModel()->deleteBonCommande($id);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function convertBonCommandeToFacture($id) {
        try {
            $result = Flight::achatModel()->convertBonCommandeToFacture($id);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getAllFactures() {
        try {
            $filters = Flight::request()->query;
            $factures = Flight::achatModel()->getAllFactures($filters);
            Flight::json($factures);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getFactureById($id) {
        try {
            $facture = Flight::achatModel()->getFactureById($id);
            if ($facture) {
                Flight::json($facture);
            } else {
                Flight::json(['error' => 'Facture non trouvée'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function createFacture() {
        try {
            $data = Flight::request()->data;
            $result = Flight::achatModel()->createFacture($data);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateFacture($id) {
        try {
            $data = Flight::request()->data;
            $result = Flight::achatModel()->updateFacture($id, $data);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteFacture($id) {
        try {
            $result = Flight::achatModel()->deleteFacture($id);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
