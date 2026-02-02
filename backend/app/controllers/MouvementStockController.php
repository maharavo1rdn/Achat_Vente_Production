<?php

namespace app\controllers;

use Exception;
use Flight;
use InvalidArgumentException;

class MouvementStockController {

    public function getSituationGlobale() {
        try {
            $filters = Flight::request()->query->getData();
            $situation = Flight::mouvementStockModel()->getSituationGlobale($filters);
            Flight::json(['success' => true, 'data' => $situation]);
        } catch (Exception $e) {
            error_log("Erreur dans getSituationGlobale: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getSituationParDepot() {
        try {
            $filters = Flight::request()->query->getData();
            $situation = Flight::mouvementStockModel()->getSituationParDepot($filters);
            Flight::json(['success' => true, 'data' => $situation]);
        } catch (Exception $e) {
            error_log("Erreur dans getSituationParDepot: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getDernieresSorties() {
        try {
            $filters = Flight::request()->query->getData();
            $limit = $filters['limit'] ?? 10;
            unset($filters['limit']); // Retirer limit des filtres
            
            $sorties = Flight::mouvementStockModel()->getDernieresSortiesParDepot($limit, $filters);
            Flight::json(['success' => true, 'data' => $sorties]);
        } catch (Exception $e) {
            error_log("Erreur dans getDernieresSorties: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getDernieresTransactions() {
        try {
            $filters = Flight::request()->query->getData();
            $limit = $filters['limit'] ?? 20;
            unset($filters['limit']); // Retirer limit des filtres
            
            $transactions = Flight::mouvementStockModel()->getDernieresTransactions($limit, $filters);
            Flight::json(['success' => true, 'data' => $transactions]);
        } catch (Exception $e) {
            error_log("Erreur dans getDernieresTransactions: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getDepotFIFOPourSortie($articleId) {
        try {
            $data = Flight::request()->query->getData();
            $quantite = $data['quantite'] ?? 0;
            
            if ($quantite <= 0) {
                throw new InvalidArgumentException("La quantité doit être supérieure à 0");
            }
            
            $depots = Flight::mouvementStockModel()->getDepotFIFOPourSortie($articleId, $quantite);
            Flight::json(['success' => true, 'data' => $depots]);
        } catch (Exception $e) {
            error_log("Erreur dans getDepotFIFOPourSortie: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function creerSortieAvecFIFO() {
        try {
            $data = Flight::request()->data->getData();
            
            // Validation des données requises
            $requiredFields = ['article_id', 'quantite', 'personnel_id', 'reference_document'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new InvalidArgumentException("Le champ '$field' est requis");
                }
            }
            
            $result = Flight::mouvementStockModel()->creerSortieAvecFIFO(
                $data['article_id'],
                $data['quantite'],
                $data['personnel_id'],
                $data['reference_document'],
                $data['prix_unitaire'] ?? null
            );
            
            Flight::json([
                'success' => true, 
                'message' => 'Sortie créée avec succès',
                'data' => ['mouvements_ids' => $result]
            ], 201);
            exit();
            
        } catch (Exception $e) {
            error_log("Erreur dans creerSortieAvecFIFO: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function creerSortieAvecLIFO() {
        try {
            $data = Flight::request()->data->getData();
            
            // Validation des données requises
            $requiredFields = ['article_id', 'quantite', 'personnel_id', 'reference_document'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new InvalidArgumentException("Le champ '$field' est requis");
                }
            }
            
            $result = Flight::mouvementStockModel()->creerSortieAvecLIFO(
                $data['article_id'],
                $data['quantite'],
                $data['personnel_id'],
                $data['reference_document'],
                $data['prix_unitaire'] ?? null
            );
            
            Flight::json([
                'success' => true, 
                'message' => 'Sortie LIFO créée avec succès',
                'data' => $result
            ], 201);
            
        } catch (Exception $e) {
            error_log("Erreur dans creerSortieAvecLIFO: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Crée une sortie avec méthode CMUP
     */
    public function creerSortieAvecCMUP() {
        try {
            $data = Flight::request()->data->getData();
            
            // Validation des données requises
            $requiredFields = ['article_id', 'quantite', 'personnel_id', 'reference_document'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new InvalidArgumentException("Le champ '$field' est requis");
                }
            }
            
            $result = Flight::mouvementStockModel()->creerSortieAvecCMUP(
                $data['article_id'],
                $data['quantite'],
                $data['personnel_id'],
                $data['reference_document'],
                $data['prix_unitaire'] ?? null
            );
            
            Flight::json([
                'success' => true, 
                'message' => 'Sortie CMUP créée avec succès',
                'data' => $result
            ], 201);
            
        } catch (Exception $e) {
            error_log("Erreur dans creerSortieAvecCMUP: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    

    public function getMouvementsDetailles() {
        try {
            $filters = Flight::request()->query->getData();
            $mouvements = Flight::mouvementStockModel()->getMouvementsDetailles($filters);
            Flight::json(['success' => true, 'data' => $mouvements]);
        } catch (Exception $e) {
            error_log("Erreur dans getMouvementsDetailles: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getStatistiquesMouvements() {
        try {
            $filters = Flight::request()->query->getData();
            $statistiques = Flight::mouvementStockModel()->getStatistiquesMouvements($filters);
            Flight::json(['success' => true, 'data' => $statistiques]);
        } catch (Exception $e) {
            error_log("Erreur dans getStatistiquesMouvements: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getMouvementsParArticle($articleId) {
        try {
            $filters = Flight::request()->query->getData();
            $filters['article_id'] = $articleId;
            
            $mouvements = Flight::mouvementStockModel()->getMouvementsDetailles($filters);
            Flight::json(['success' => true, 'data' => $mouvements]);
        } catch (Exception $e) {
            error_log("Erreur dans getMouvementsParArticle: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getMouvementsParDepot($depotId) {
        try {
            $filters = Flight::request()->query->getData();
            $filters['depot_id'] = $depotId;
            
            $mouvements = Flight::mouvementStockModel()->getMouvementsDetailles($filters);
            Flight::json(['success' => true, 'data' => $mouvements]);
        } catch (Exception $e) {
            error_log("Erreur dans getMouvementsParDepot: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function createMouvement() {
        try {
            $data = Flight::request()->data->getData();
            
            // Validation des données requises pour un mouvement standard
            $requiredFields = ['type_mouvement', 'article_id', 'depot_id', 'personnel_id'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new InvalidArgumentException("Le champ '$field' est requis");
                }
            }
            
            // Vérifier qu'au moins une quantité est fournie
            $hasEntree = isset($data['quantite_entree']) && $data['quantite_entree'] > 0;
            $hasSortie = isset($data['quantite_sortie']) && $data['quantite_sortie'] > 0;
            
            if (!$hasEntree && !$hasSortie) {
                throw new InvalidArgumentException("Au moins une quantité d'entrée ou de sortie doit être spécifiée");
            }
            
            // Utiliser la méthode existante du modèle StockModel si elle existe
            // Sinon, créer une méthode dans MouvementStockModel
            if (method_exists(Flight::stockModel(), 'createMouvement')) {
                // Si on a un StockModel avec createMouvement
                $result = Flight::stockModel()->createMouvement($data);
            } else {
                // Créer une nouvelle méthode dans MouvementStockModel pour gérer les créations simples
                $result = Flight::mouvementStockModel()->createMouvementSimple($data);
            }
            
            Flight::json([
                'success' => true, 
                'message' => 'Mouvement créé avec succès',
                'data' => ['id' => $result]
            ], 201);
            
        } catch (Exception $e) {
            error_log("Erreur dans createMouvement: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getStockActuelParDepot($articleId, $depotId) {
        try {
            // Ajouter une méthode dans le modèle pour récupérer le stock actuel
            // Ou utiliser une méthode existante si disponible
            if (method_exists(Flight::mouvementStockModel(), 'getStockActuelParDepot')) {
                $stock = Flight::mouvementStockModel()->getStockActuelParDepot($articleId, $depotId);
            } else {
                // Sinon, utiliser une approche alternative
                $stock = $this->calculateStockAvantViaModel($articleId, $depotId);
            }
            
            Flight::json(['success' => true, 'data' => ['stock_actuel' => $stock]]);
        } catch (Exception $e) {
            error_log("Erreur dans getStockActuelParDepot: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function calculateStockAvantViaModel($articleId, $depotId) {
        // Cette méthode peut être déplacée dans le modèle si nécessaire
        // Pour l'instant, on utilise une approche simple via les mouvements
        $filters = [
            'article_id' => $articleId,
            'depot_id' => $depotId
        ];
        
        $mouvements = Flight::mouvementStockModel()->getMouvementsDetailles($filters);
        
        $stock = 0;
        foreach ($mouvements as $mouvement) {
            if (in_array($mouvement['type_mouvement'], ['INVENTAIRE', 'ACHAT'])) {
                $stock += $mouvement['quantite_entree'];
            } elseif ($mouvement['type_mouvement'] === 'VENTE') {
                $stock -= $mouvement['quantite_sortie'];
            }
        }
        
        return $stock;
    }

    public function searchMouvements() {
        try {
            $filters = Flight::request()->query->getData();
            
            // Recherche par référence document, article, etc.
            $mouvements = Flight::mouvementStockModel()->getMouvementsDetailles($filters);
            
            Flight::json(['success' => true, 'data' => $mouvements]);
        } catch (Exception $e) {
            error_log("Erreur dans searchMouvements: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getMouvementsParType($type) {
        try {
            $filters = Flight::request()->query->getData();
            $filters['type_mouvement'] = $type;
            
            $mouvements = Flight::mouvementStockModel()->getMouvementsDetailles($filters);
            Flight::json(['success' => true, 'data' => $mouvements]);
        } catch (Exception $e) {
            error_log("Erreur dans getMouvementsParType: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getMouvementsParPeriode() {
        try {
            $filters = Flight::request()->query->getData();
            
            // Validation des dates si fournies
            if (isset($filters['date_debut']) && isset($filters['date_fin'])) {
                $dateDebut = strtotime($filters['date_debut']);
                $dateFin = strtotime($filters['date_fin']);
                
                if ($dateDebut === false || $dateFin === false) {
                    throw new InvalidArgumentException("Format de date invalide");
                }
                
                if ($dateDebut > $dateFin) {
                    throw new InvalidArgumentException("La date de début doit être antérieure à la date de fin");
                }
            }
            
            $mouvements = Flight::mouvementStockModel()->getMouvementsDetailles($filters);
            Flight::json(['success' => true, 'data' => $mouvements]);
        } catch (Exception $e) {
            error_log("Erreur dans getMouvementsParPeriode: " . $e->getMessage());
            Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
}