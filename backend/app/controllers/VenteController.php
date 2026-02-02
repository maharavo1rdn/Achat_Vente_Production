<?php

namespace app\controllers;

use Exception;
use InvalidArgumentException;
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

    public function updateDevisStatut($id) {
        try {
            $data = Flight::request()->data;
            $statutCode = $data['statut_code'] ?? null;
            
            if (!$statutCode) {
                Flight::json(['error' => 'Le code statut est obligatoire'], 400);
                return;
            }
            
            $result = Flight::venteModel()->updateDevisStatut($id, $statutCode);
            Flight::json(['success' => $result]);
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
            error_log("VenteController::createBonCommande received data: " . json_encode($data));
            $result = Flight::venteModel()->createBonCommande($data);
            Flight::json($result, 201);
        } catch (Exception $e) {
            error_log("VenteController::createBonCommande Exception: " . $e->__toString());
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

    public function convertBonCommandeToFactureWithCustomData($id) {
        try {
            $customData = Flight::request()->data;
            error_log("VenteController::convertBonCommandeToFactureWithCustomData received data: " . json_encode($customData));
            $result = Flight::venteModel()->convertBonCommandeToFactureWithCustomData($id, $customData);
            Flight::json($result, 201);
        } catch (InvalidArgumentException $e) {
            error_log("VenteController::convertBonCommandeToFactureWithCustomData InvalidArgumentException: " . $e->getMessage());
            Flight::json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            error_log("VenteController::convertBonCommandeToFactureWithCustomData Exception: " . $e->__toString());
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function checkIfBonCommandeIsFactured($id) {
        try {
            $isFactured = Flight::venteModel()->factureExistsForBonCommande($id);
            Flight::json(['isFactured' => $isFactured]);
        } catch (Exception $e) {
            error_log("VenteController::checkIfBonCommandeIsFactured Exception: " . $e->getMessage());
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
            error_log("VenteController::createFacture received data: " . json_encode($data));
            $result = Flight::venteModel()->createFacture($data);
            Flight::json($result, 201);
        } catch (Exception $e) {
            error_log("VenteController::createFacture Exception: " . $e->__toString());
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
