<?php

namespace app\controllers;

use Exception;
use Flight;

class VenteController {

    public function getAllDevis() {
        try {
            $filters = Flight::request()->query;
            $devis = Flight::venteModel()->getAllDevis($filters);
            Flight::json($devis);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDevisById($id) {
        try {
            $devis = Flight::venteModel()->getDevisById($id);
            if ($devis) {
                Flight::json($devis);
            } else {
                Flight::json(['error' => 'Devis non trouvé'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function createDevis() {
        try {
            $data = Flight::request()->data;
            $result = Flight::venteModel()->createDevis($data);
            Flight::json($result, 201);
        } catch (Exception $e) {
            // Log full exception for debugging
            error_log("VenteController::createDevis Exception: " . $e->__toString());
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateDevis($id) {
        try {
            $data = Flight::request()->data;
            $result = Flight::venteModel()->updateDevis($id, $data);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteDevis($id) {
        try {
            $result = Flight::venteModel()->deleteDevis($id);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function convertDevisToBonCommande($id) {
        try {
            $result = Flight::venteModel()->convertDevisToBonCommande($id);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getAllBonCommande() {
        try {
            $filters = Flight::request()->query;
            $bonCommandes = Flight::venteModel()->getAllBonCommande($filters);
            Flight::json($bonCommandes);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getBonCommandeById($id) {
        try {
            $bonCommande = Flight::venteModel()->getBonCommandeById($id);
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
            $result = Flight::venteModel()->createBonCommande($data);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateBonCommande($id) {
        try {
            $data = Flight::request()->data;
            $result = Flight::venteModel()->updateBonCommande($id, $data);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteBonCommande($id) {
        try {
            $result = Flight::venteModel()->deleteBonCommande($id);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function convertBonCommandeToFacture($id) {
        try {
            $result = Flight::venteModel()->convertBonCommandeToFacture($id);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getAllFactures() {
        try {
            $filters = Flight::request()->query;
            $factures = Flight::venteModel()->getAllFactures($filters);
            Flight::json($factures);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getFactureById($id) {
        try {
            $facture = Flight::venteModel()->getFactureById($id);
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
            $result = Flight::venteModel()->createFacture($data);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateFacture($id) {
        try {
            $data = Flight::request()->data;
            $result = Flight::venteModel()->updateFacture($id, $data);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteFacture($id) {
        try {
            $result = Flight::venteModel()->deleteFacture($id);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function payerFacture($id) {
        try {
            $data = Flight::request()->data;
            $result = Flight::venteModel()->payerFacture($id, $data);
            Flight::json(['success' => true]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
