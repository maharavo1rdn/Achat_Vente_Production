<?php
// New file: app\models\StatCommercialModel.php

namespace app\models;

use PDO;

class StatCommercialModel extends AppModel {


    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }
    
    public function getPerformanceCommercial($startDate = null, $endDate = null) {
        $query = "
            SELECT 
                p.id AS personnel_id,
                p.nom || ' ' || p.prenom AS nom_complet,
                COUNT(fv.id) AS nombre_ventes,
                SUM(fv.montant_ttc) AS total_ventes,
                AVG(fv.montant_ttc) AS panier_moyen
            FROM facture_vente fv
            JOIN personnel p ON fv.personnel_id = p.id
            WHERE 1=1
        ";

        $params = [];
        if ($startDate) {
            $query .= " AND fv.date_facture >= :startDate";
            $params[':startDate'] = $startDate;
        }
        if ($endDate) {
            $query .= " AND fv.date_facture <= :endDate";
            $params[':endDate'] = $endDate;
        }

        $query .= " GROUP BY p.id, nom_complet ORDER BY total_ventes DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTauxFidelisation($startDate = null, $endDate = null) {
        $query = "
            WITH client_achats AS (
                SELECT 
                    entreprise_client_id AS client_id,
                    COUNT(*) AS nombre_achats
                FROM facture_vente
                WHERE 1=1
        ";

        $params = [];
        if ($startDate) {
            $query .= " AND date_facture >= :startDate";
            $params[':startDate'] = $startDate;
        }
        if ($endDate) {
            $query .= " AND date_facture <= :endDate";
            $params[':endDate'] = $endDate;
        }

        $query .= "
                GROUP BY client_id
            )
            SELECT 
                (COUNT(CASE WHEN nombre_achats > 1 THEN 1 END) * 100.0 / COUNT(*)) AS taux_fidelisation,
                COUNT(*) AS total_clients,
                COUNT(CASE WHEN nombre_achats > 1 THEN 1 END) AS clients_fideles
            FROM client_achats
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getNouveauxClients($startDate, $endDate) {
        $query = "
            SELECT 
                COUNT(DISTINCT fv.entreprise_client_id) AS nouveaux_clients
            FROM facture_vente fv
            WHERE fv.date_facture >= :startDate
            AND fv.date_facture <= :endDate
            AND fv.entreprise_client_id NOT IN (
                SELECT DISTINCT entreprise_client_id 
                FROM facture_vente 
                WHERE date_facture < :startDate
            )
        ";

        $params = [':startDate' => $startDate, ':endDate' => $endDate];

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC)['nouveaux_clients'] ?? 0;
    }
}