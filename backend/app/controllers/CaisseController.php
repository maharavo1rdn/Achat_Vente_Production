<?php

namespace app\controllers;

use Exception;
use Flight;

class CaisseController {

    public function getAllCaisses() {
        try {
            $filters = Flight::request()->query;
            $caisses = Flight::caisseModel()->getAllCaisses($filters);
            Flight::json($caisses);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getCaisseById($id) {
        try {
            $caisse = Flight::caisseModel()->getCaisseById($id);
            if ($caisse) {
                Flight::json($caisse);
            } else {
                Flight::json(['error' => 'Caisse non trouvée'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function createCaisse() {
        try {
            $data = Flight::request()->data;
            $result = Flight::caisseModel()->createCaisse($data);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateCaisse($id) {
        try {
            $data = Flight::request()->data;
            $result = Flight::caisseModel()->updateCaisse($id, $data);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteCaisse($id) {
        try {
            $result = Flight::caisseModel()->deleteCaisse($id);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getSolde($id) {
        try {
            $solde = Flight::caisseModel()->getSolde($id);
            Flight::json(['solde' => $solde]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getAllMouvements() {
        try {
            $filters = Flight::request()->query;
            $mouvements = Flight::caisseModel()->getMouvements($filters);
            Flight::json($mouvements);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getMouvementsByCaisse($caisseId) {
        try {
            $mouvements = Flight::caisseModel()->getMouvements(['caisse_id' => $caisseId]);
            Flight::json($mouvements);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getMouvementById($id) {
        try {
            $mouvement = Flight::caisseModel()->getMouvementById($id);
            if ($mouvement) {
                Flight::json($mouvement);
            } else {
                Flight::json(['error' => 'Mouvement non trouvé'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateMouvement($id) {
        try {
            $data = Flight::request()->data;
            $result = Flight::caisseModel()->updateMouvement($id, $data);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function validateMouvement($id) {
        try {
            $result = Flight::caisseModel()->finalizeMouvement($id, 3);
            Flight::json(['success' => true, 'message' => 'Mouvement validé']);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function createEntree($caisseId) {
        try {
            $data = Flight::request()->data;
            $statutId = isset($data['statut_id']) ? $data['statut_id'] : 2;
            $result = Flight::caisseModel()->createEntree($caisseId, $data, $statutId);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function createSortie($caisseId) {
        try {
            $data = Flight::request()->data;
            // Allow caller to specify statut_id (default to EN_ATTENTE = 2)
            $statutId = isset($data['statut_id']) ? $data['statut_id'] : 2;
            $result = Flight::caisseModel()->createSortie($caisseId, $data, $statutId);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function createMouvement($caisseId) {
        try {
            $data = Flight::request()->data;
            $statutId = isset($data['statut_id']) ? $data['statut_id'] : 2;
            $result = Flight::caisseModel()->createMouvement($caisseId, $data, $statutId);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPaiementsVente() {
        try {
            $filters = Flight::request()->query;
            $paiements = Flight::caisseModel()->getPaiementsVente($filters);
            Flight::json($paiements);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPaiementsAchat() {
        try {
            $filters = Flight::request()->query;
            $paiements = Flight::caisseModel()->getPaiementsAchat($filters);
            Flight::json($paiements);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

}
