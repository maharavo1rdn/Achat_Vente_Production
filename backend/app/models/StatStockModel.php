<?php

namespace app\models;

use PDO;

class StatStockModel extends AppModel {

    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    public function getTauxRotationStock($categorieId = null, $startDate = null, $endDate = null) {
        $params = [];
        $mouvementCondition = "";
        
        if ($startDate) {
            $mouvementCondition .= " AND ms.date_mouvement >= :startDate";
            $params[':startDate'] = $startDate;
        }
        if ($endDate) {
            $mouvementCondition .= " AND ms.date_mouvement <= :endDate";
            $params[':endDate'] = $endDate;
        }
        if ($categorieId) {
            $mouvementCondition .= " AND a.article_categorie_id = :categorieId";
            $params[':categorieId'] = $categorieId;
        }

        $query = "
            SELECT
                a.id AS article_id,
                a.designation,
                ac.libelle AS categorie,
                SUM(CASE WHEN ms.type_mouvement IN ('SORTIE', 'VENTE') THEN COALESCE(ms.quantite_sortie, 0) ELSE 0 END) AS total_sorties,
                COALESCE(stk.total_stock, 0) AS stock_actuel,
                CASE
                    WHEN COALESCE(stk.total_stock, 0) > 0
                    THEN (SUM(CASE WHEN ms.type_mouvement IN ('SORTIE', 'VENTE') THEN COALESCE(ms.quantite_sortie, 0) ELSE 0 END) / stk.total_stock) * 100
                    ELSE 0
                END AS taux_rotation
            FROM article a
            LEFT JOIN article_categorie ac ON a.article_categorie_id = ac.id
            LEFT JOIN mouvement_stock ms ON a.id = ms.article_id $mouvementCondition
            LEFT JOIN (
                SELECT article_id, SUM(quantite_actuelle) as total_stock
                FROM stock
                GROUP BY article_id
            ) stk ON a.id = stk.article_id
            WHERE a.est_actif = true
        ";

        if ($categorieId) {
            $query .= " AND a.article_categorie_id = :categorieId";
        }

        $query .= " GROUP BY a.id, a.designation, ac.libelle, stk.total_stock";
        $query .= " ORDER BY taux_rotation DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            return [
                'article_id' => $row['article_id'],
                'designation' => $row['designation'],
                'categorie' => $row['categorie'] ?? 'Sans catégorie',
                'total_sorties' => floatval($row['total_sorties']),
                'stock_actuel' => floatval($row['stock_actuel']),
                'taux_rotation' => floatval($row['taux_rotation'])
            ];
        }, $results);
    }

    public function getValeurStockImmobilise($joursImmobilise = 90) {
        $query = "
            SELECT
                ls.id AS lot_id,
                a.designation,
                ls.quantite_restante * ls.prix_unitaire_achat AS valeur_immobilise
            FROM lot_stock ls
            JOIN article a ON ls.article_id = a.id
            WHERE ls.statut = 'ACTIF'
            AND ls.date_entree < CURRENT_DATE - INTERVAL '1 day' * :jours
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([':jours' => $joursImmobilise]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = array_reduce($results, function($carry, $item) {
            return $carry + $item['valeur_immobilise'];
        }, 0);

        return ['total' => $total, 'details' => $results];
    }

    public function getArticlesRupture($seuil = 10) {
        $query = "
            SELECT 
                a.id,
                a.designation,
                s.quantite_actuelle,
                ac.libelle AS categorie
            FROM stock s
            JOIN article a ON s.article_id = a.id
            JOIN article_categorie ac ON a.article_categorie_id = ac.id
            WHERE s.quantite_actuelle < :seuil
            ORDER BY s.quantite_actuelle ASC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([':seuil' => $seuil]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getDureeStockMoyenne($categorieId = null)
        {
            $query = "
                SELECT
                    COALESCE(
                        AVG(EXTRACT(DAY FROM (CURRENT_DATE - ls.date_entree))),
                        0
                    ) AS duree_moyenne
                FROM lot_stock ls
                JOIN article a ON ls.article_id = a.id
                WHERE 1 = 1
            ";

            $params = [];

            if ($categorieId !== null) {
                $query .= " AND a.article_categorie_id = :categorieId";
                $params['categorieId'] = $categorieId;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'duree_moyenne' => (float) ($result['duree_moyenne'] ?? 0)
            ];
        }


}