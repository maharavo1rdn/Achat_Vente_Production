<?php

namespace app\models;

use PDO;
use Flight;

class StatAchatModel {
    private PDO $db;

    public function __construct($base_db = null)
    {
        $this->db = $base_db ?? Flight::db();
    }

    /**
     * Répartition des dépenses par fournisseur (top N)
     */
    public function getDepensesParFournisseur($periode = null, $limit = 10): array
    {
        [$where, $params] = $this->buildPeriodClause('fa.date_facture', $periode);

        $sql = "
            SELECT
                ef.id,
                ef.nom,
                ef.type_entreprise,
                COALESCE(SUM(pa.montant), 0) AS total_paye,
                COALESCE(SUM(fa.montant_ttc), 0) AS total_facture,
                COUNT(DISTINCT fa.id) AS nombre_factures
            FROM facture_achat fa
            INNER JOIN entreprise ef ON fa.entreprise_fournisseur_id = ef.id
            LEFT JOIN paiement_achat pa ON fa.id = pa.facture_achat_id AND pa.statut_id = 3
            WHERE 1=1
            {$where}
            GROUP BY ef.id, ef.nom, ef.type_entreprise
            ORDER BY total_facture DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $params[] = $limit;
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return [
                'id' => (int)$row['id'],
                'nom' => $row['nom'],
                'total_paye' => (float)$row['total_paye'],
                'total_facture' => (float)$row['total_facture'],
                'nombre_factures' => (int)$row['nombre_factures'],
                'reste_a_payer' => (float)($row['total_facture'] - $row['total_paye'])
            ];
        }, $rows);
    }

    /**
     * Délai moyen de livraison entre Bon de Commande et réception (via facture)
     * Calculé en jours
     */
    public function getDelaiMoyenLivraison($periode = null): array
    {
        [$where, $params] = $this->buildPeriodClause('fa.date_facture', $periode);

        $sql = "
            SELECT
                ef.id,
                ef.nom,
                AVG(fa.date_facture - bca.date_commande)::INTEGER AS delai_moyen_jours,
                COUNT(DISTINCT fa.id) AS nombre_livraisons,
                MIN(fa.date_facture - bca.date_commande)::INTEGER AS delai_min,
                MAX(fa.date_facture - bca.date_commande)::INTEGER AS delai_max
            FROM bon_commande_achat bca
            INNER JOIN facture_achat fa ON bca.id = fa.bon_commande_achat_id
            INNER JOIN entreprise ef ON bca.entreprise_fournisseur_id = ef.id
            WHERE 1=1
            {$where}
            GROUP BY ef.id, ef.nom
            ORDER BY delai_moyen_jours DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return [
                'id' => (int)$row['id'],
                'nom' => $row['nom'],
                'delai_moyen_jours' => (int)($row['delai_moyen_jours'] ?? 0),
                'nombre_livraisons' => (int)$row['nombre_livraisons'],
                'delai_min' => (int)($row['delai_min'] ?? 0),
                'delai_max' => (int)($row['delai_max'] ?? 0)
            ];
        }, $rows);
    }

    /**
     * Taux de service fournisseur = nombre de factures reçues / nombre de BC envoyées
     */
    public function getTauxServiceFournisseur($periode = null): array
    {
        [$where, $params] = $this->buildPeriodClause('bca.date_commande', $periode);

        $sql = "
            SELECT
                ef.id,
                ef.nom,
                COUNT(DISTINCT bca.id) AS nombre_bc,
                COUNT(DISTINCT fa.id) AS nombre_factures_recues,
                ROUND(
                    CASE 
                        WHEN COUNT(DISTINCT bca.id) = 0 THEN 0
                        ELSE (COUNT(DISTINCT fa.id)::NUMERIC / COUNT(DISTINCT bca.id)::NUMERIC) * 100
                    END, 
                    2
                ) AS taux_service_percent
            FROM bon_commande_achat bca
            INNER JOIN entreprise ef ON bca.entreprise_fournisseur_id = ef.id
            LEFT JOIN facture_achat fa ON bca.id = fa.bon_commande_achat_id
            WHERE 1=1
            {$where}
            GROUP BY ef.id, ef.nom
            ORDER BY taux_service_percent DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return [
                'id' => (int)$row['id'],
                'nom' => $row['nom'],
                'nombre_bc' => (int)$row['nombre_bc'],
                'nombre_factures_recues' => (int)$row['nombre_factures_recues'],
                'taux_service_percent' => (float)$row['taux_service_percent']
            ];
        }, $rows);
    }

    /**
     * Construit une clause de filtre période acceptant :
     * - null : pas de filtre
     * - string 'YYYY-MM' : filtrage par mois
     * - array[start, end] : bornes inclusives (dates)
     */
    private function buildPeriodClause(string $column, $periode): array
    {
        $where = '';
        $params = [];

        if (is_string($periode) && preg_match('/^\d{4}-\d{2}$/', $periode)) {
            $where = " AND TO_CHAR({$column}, 'YYYY-MM') = ?";
            $params[] = $periode;
        } elseif (is_array($periode) && count($periode) === 2) {
            $where = " AND {$column} BETWEEN ? AND ?";
            $params[] = $periode[0];
            $params[] = $periode[1];
        }

        return [$where, $params];
    }
}
