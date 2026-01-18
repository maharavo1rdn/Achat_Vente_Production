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

    public function getMouvements() {
        try {
            $filters = Flight::request()->query;
            $mouvements = Flight::caisseModel()->getMouvements($filters);
            Flight::json($mouvements);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function createEntree($caisseId) {
        try {
            $data = Flight::request()->data;
            $result = Flight::caisseModel()->createEntree($caisseId, $data);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function createSortie($caisseId) {
        try {
            $data = Flight::request()->data;
            $result = Flight::caisseModel()->createSortie($caisseId, $data);
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

    public function enregistrerPaiementVente($factureId) {
        try {
            $data = Flight::request()->data;
            $result = Flight::caisseModel()->enregistrerPaiementVente($factureId, $data->caisse_mouvement_id, $data->montant);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function enregistrerPaiementAchat($factureId) {
        try {
            $data = Flight::request()->data;
            $result = Flight::caisseModel()->enregistrerPaiementAchat($factureId, $data->caisse_mouvement_id, $data->montant);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
