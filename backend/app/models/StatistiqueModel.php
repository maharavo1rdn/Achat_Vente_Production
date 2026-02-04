<?php

namespace app\models;

use PDO;
use Flight;

class StatistiqueModel {
    private PDO $db;

    public function __construct($base_db = null)
    {
        // Fallback sur Flight::db() pour rester cohérent avec les autres modèles
        $this->db = $base_db ?? Flight::db();
    }

    /**
     * Total du chiffre d'affaires encaissé (paiements ventes validés)
     */
    public function getCA_Total($periode = null): float
    {
        [$where, $params] = $this->buildPeriodClause('pv.date_paiement', $periode);

        $sql = "
            SELECT COALESCE(SUM(pv.montant_total_paye), 0) AS total
            FROM paiement_vente pv
            
            {$where}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($row['total'] ?? 0);
    }

    /**
     * Marge brute = CA encaissé - paiements achats validés
     */
    public function getMargeBrute_Total($periode = null): float
    {
        $ca = $this->getCA_Total($periode);
        [$where, $params] = $this->buildPeriodClause('pa.date_paiement', $periode);

        $sql = "
            SELECT COALESCE(SUM(pa.montant_total_paye), 0) AS total
            FROM paiement_achat pa
            WHERE 1=1
            {$where}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $achats = (float)($row['total'] ?? 0);

        return $ca - $achats;
    }

    /**
     * Top 5 clients par montant encaissé
     */
    public function getTop5_Clients($periode = null): array
    {
        [$where, $params] = $this->buildPeriodClause('pv.date_paiement', $periode);

        $sql = "
            SELECT
                ec.id,
                ec.nom,
                ec.type_entreprise,
                COALESCE(SUM(pv.montant_total_paye), 0) AS total,
                COUNT(DISTINCT fv.id) AS factures
            FROM paiement_vente pv
            INNER JOIN facture_vente fv ON pv.facture_vente_id = fv.id
            INNER JOIN entreprise ec ON fv.entreprise_client_id = ec.id
            WHERE 1=1
            {$where}
            GROUP BY ec.id, ec.nom, ec.type_entreprise
            ORDER BY factures DESC, total DESC
            LIMIT 5
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return [
                'id' => (int)$row['id'],
                'nom' => $row['nom'],
                'type' => $row['type_entreprise'],
                'total' => (float)$row['total'],
                'factures' => (int)$row['factures'],
            ];
        }, $rows);
    }

    /**
     * Top 5 articles vendus (quantité et CA)
     */
    public function getTop5_Articles($periode = null): array
    {
        [$where, $params] = $this->buildPeriodClause('fv.date_facture', $periode);

        $sql = "
            SELECT
                a.id,
                a.reference,
                a.designation,
                COALESCE(SUM(fvd.quantite), 0) AS quantite,
                COALESCE(SUM(fvd.quantite * fvd.prix_unitaire), 0) AS montant
            FROM facture_vente_details fvd
            INNER JOIN facture_vente fv ON fvd.facture_vente_id = fv.id
            INNER JOIN article a ON fvd.article_id = a.id
            WHERE 1=1
            {$where}
            GROUP BY a.id, a.reference, a.designation
            ORDER BY quantite DESC, montant DESC
            LIMIT 5
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return [
                'id' => (int)$row['id'],
                'reference' => $row['reference'],
                'designation' => $row['designation'],
                'quantite' => (float)$row['quantite'],
                'montant' => (float)$row['montant'],
            ];
        }, $rows);
    }

    /**
     * Taux de rentabilité global = (CA - Achats) / CA
     */
    public function getTaux_Rentabilite($periode = null): float
    {
        $ca = $this->getCA_Total($periode);
        if ($ca <= 0) {
            return 0.0;
        }

        [$where, $params] = $this->buildPeriodClause('pa.date_paiement', $periode);
        $sql = "
            SELECT COALESCE(SUM(pa.montant_total_paye), 0) AS total
            FROM paiement_achat pa
            WHERE 1=1
            {$where}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $achats = (float)($row['total'] ?? 0);

        $benefice = $ca - $achats;
        return ($benefice / $ca) * 100;
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

        if (is_string($periode) && preg_match('/^\\d{4}-\\d{2}$/', $periode)) {
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
