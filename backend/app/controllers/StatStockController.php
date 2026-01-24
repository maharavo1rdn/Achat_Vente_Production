<?php

namespace app\controllers;

use Flight;
use Exception;

class StatStockController {
    
    /**
     * Récupère le taux de rotation du stock
     */
    public function getTauxRotationStock() {
        try {
            $model = Flight::statStockModel();
            $categorieId = Flight::request()->query['categorie_id'] ?? null;
            $startDate = Flight::request()->query['start_date'] ?? date('Y-m-d', strtotime('-1 year'));
            $endDate = Flight::request()->query['end_date'] ?? date('Y-m-d');
            
            $data = $model->getTauxRotationStock($categorieId, $startDate, $endDate);
            
            // S'assurer que les données sont un tableau
            if (!is_array($data)) {
                $data = [];
            }
            
            // Nettoyer les valeurs NaN ou infinies
            $data = array_map(function($item) {
                $item['taux_rotation'] = isset($item['taux_rotation']) && is_numeric($item['taux_rotation']) && is_finite($item['taux_rotation']) 
                    ? floatval($item['taux_rotation']) 
                    : 0;
                $item['total_sorties'] = isset($item['total_sorties']) && is_numeric($item['total_sorties']) 
                    ? floatval($item['total_sorties']) 
                    : 0;
                $item['stock_actuel'] = isset($item['stock_actuel']) && is_numeric($item['stock_actuel']) 
                    ? floatval($item['stock_actuel']) 
                    : 0;
                return $item;
            }, $data);
            
            Flight::json($data);
            
        } catch (Exception $e) {
            Flight::halt(500, json_encode([
                'error' => 'Erreur lors du calcul du taux de rotation',
                'message' => $e->getMessage()
            ]));
        }
    }

    /**
     * Récupère la valeur du stock immobilisé
     */
    public function getValeurStockImmobilise() {
        try {
            $model = Flight::statStockModel();
            $jours = Flight::request()->query['jours'] ?? 90;
            
            // Valider le paramètre jours
            $jours = is_numeric($jours) && $jours > 0 ? intval($jours) : 90;
            
            $data = $model->getValeurStockImmobilise($jours);
            
            // S'assurer que la structure est correcte
            if (!isset($data['total'])) {
                $data = ['total' => 0, 'details' => []];
            }
            
            // Nettoyer la valeur total
            $data['total'] = is_numeric($data['total']) && is_finite($data['total']) 
                ? floatval($data['total']) 
                : 0;
            
            Flight::json($data);
            
        } catch (Exception $e) {
            Flight::halt(500, json_encode([
                'error' => 'Erreur lors du calcul de la valeur du stock immobilisé',
                'message' => $e->getMessage()
            ]));
        }
    }

    /**
     * Récupère les articles en rupture de stock
     */
    public function getArticlesRupture() {
        try {
            $model = Flight::statStockModel();
            $seuil = Flight::request()->query['seuil'] ?? 10;
            
            // Valider le paramètre seuil
            $seuil = is_numeric($seuil) && $seuil >= 0 ? intval($seuil) : 10;
            
            $data = $model->getArticlesRupture($seuil);
            
            // S'assurer que les données sont un tableau
            if (!is_array($data)) {
                $data = [];
            }
            
            // Nettoyer les quantités
            $data = array_map(function($item) {
                $item['quantite_actuelle'] = is_numeric($item['quantite_actuelle']) 
                    ? intval($item['quantite_actuelle']) 
                    : 0;
                return $item;
            }, $data);
            
            Flight::json($data);
            
        } catch (Exception $e) {
            Flight::halt(500, json_encode([
                'error' => 'Erreur lors de la récupération des articles en rupture',
                'message' => $e->getMessage()
            ]));
        }
    }

    /**
     * Récupère la durée moyenne de stockage
     */
    public function getDureeStockMoyenne() {
        try {
            $model = Flight::statStockModel();
            $categorieId = Flight::request()->query['categorie_id'] ?? null;
            
            $result = $model->getDureeStockMoyenne($categorieId);
            
            // Extraire la durée moyenne
            $dureeMoyenne = 0;
            if (is_array($result) && isset($result['duree_moyenne'])) {
                $dureeMoyenne = $result['duree_moyenne'];
            } elseif (is_numeric($result)) {
                $dureeMoyenne = $result;
            }
            
            // Nettoyer la valeur
            $dureeMoyenne = is_numeric($dureeMoyenne) && is_finite($dureeMoyenne) 
                ? floatval($dureeMoyenne) 
                : 0;
            
            Flight::json([
                'duree_moyenne' => $dureeMoyenne
            ]);
            
        } catch (Exception $e) {
            Flight::halt(500, json_encode([
                'error' => 'Erreur lors du calcul de la durée moyenne de stockage',
                'message' => $e->getMessage()
            ]));
        }
    }
    
    /**
     * Récupère toutes les statistiques en une seule requête
     * (Optionnel - pour optimiser les performances)
     */
    public function getAllStats() {
        try {
            $model = Flight::statStockModel();
            
            // Récupérer toutes les stats
            $tauxRotation = $model->getTauxRotationStock(null, date('Y-m-d', strtotime('-1 year')), date('Y-m-d'));
            $valeurImmobilise = $model->getValeurStockImmobilise(90);
            $articlesRupture = $model->getArticlesRupture(10);
            $dureeMoyenne = $model->getDureeStockMoyenne(null);
            
            // Calculer le taux de rotation moyen
            $tauxRotationMoyen = 0;
            if (is_array($tauxRotation) && count($tauxRotation) > 0) {
                $sum = array_reduce($tauxRotation, function($carry, $item) {
                    $taux = is_numeric($item['taux_rotation']) && is_finite($item['taux_rotation']) 
                        ? floatval($item['taux_rotation']) 
                        : 0;
                    return $carry + $taux;
                }, 0);
                $tauxRotationMoyen = $sum / count($tauxRotation);
            }
            
            // Extraire la durée moyenne
            $duree = 0;
            if (is_array($dureeMoyenne) && isset($dureeMoyenne['duree_moyenne'])) {
                $duree = $dureeMoyenne['duree_moyenne'];
            } elseif (is_numeric($dureeMoyenne)) {
                $duree = $dureeMoyenne;
            }
            $duree = is_numeric($duree) && is_finite($duree) ? floatval($duree) : 0;
            
            // Construire la réponse
            $response = [
                'taux_rotation_moyenne' => $tauxRotationMoyen,
                'valeur_immobilise' => is_numeric($valeurImmobilise['total']) 
                    ? floatval($valeurImmobilise['total']) 
                    : 0,
                'duree_moyenne' => $duree,
                'articles_rupture' => is_array($articlesRupture) ? $articlesRupture : []
            ];
            
            Flight::json($response);
            
        } catch (Exception $e) {
            Flight::halt(500, json_encode([
                'error' => 'Erreur lors de la récupération des statistiques',
                'message' => $e->getMessage()
            ]));
        }
    }
}