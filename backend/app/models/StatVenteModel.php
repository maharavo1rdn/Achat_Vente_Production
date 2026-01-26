<?php

namespace app\models;

use PDO;
use Flight;

class StatVenteModel {
    private PDO $db;

    public function __construct($base_db = null)
    {
        $this->db = $base_db ?? Flight::db();
    }

    /**
     * Chiffre d'affaires par société du groupe
     */
    public function getCA_ParSociete($periode = null): array
    {
        [$where, $params] = $this->buildPeriodClause('fv.date_facture', $periode);

        $sql = "
            SELECT
                efi.id,
                efi.nom as societe,
                efi.type_entreprise,
                COUNT(DISTINCT fv.id) AS nombre_factures,
                SUM(fv.montant_ttc) AS chiffre_affaires,
                AVG(fv.montant_ttc) AS ca_moyen_facture
            FROM facture_vente fv
            INNER JOIN entreprise efi ON fv.entreprise_filiale_id = efi.id
            WHERE fv.statut_id = 3
            {$where}
            GROUP BY efi.id, efi.nom, efi.type_entreprise
            ORDER BY chiffre_affaires DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return [
                'id' => (int)$row['id'],
                'societe' => $row['societe'],
                'nombre_factures' => (int)$row['nombre_factures'],
                'chiffre_affaires' => (float)$row['chiffre_affaires'],
                'ca_moyen_facture' => (float)$row['ca_moyen_facture']
            ];
        }, $rows);
    }

    /**
     * Évolution du CA mensuel sur N mois
     */
    public function getEvolutionCA($nbMois = 12): array
    {
        $sql = "
            SELECT
                TO_CHAR(fv.date_facture, 'YYYY-MM') AS mois,
                TO_CHAR(fv.date_facture, 'Mon YYYY') AS mois_libelle,
                COUNT(DISTINCT fv.id) AS nombre_factures,
                SUM(fv.montant_ttc) AS chiffre_affaires
            FROM facture_vente fv
            WHERE fv.statut_id = 3
                AND fv.date_facture >= CURRENT_DATE - INTERVAL '1 month' * ?
            GROUP BY TO_CHAR(fv.date_facture, 'YYYY-MM'), TO_CHAR(fv.date_facture, 'Mon YYYY')
            ORDER BY mois ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nbMois]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return [
                'mois' => $row['mois'],
                'mois_libelle' => $row['mois_libelle'],
                'nombre_factures' => (int)$row['nombre_factures'],
                'chiffre_affaires' => (float)$row['chiffre_affaires']
            ];
        }, $rows);
    }

    /**
     * Panier moyen par client et par société
     */
    public function getPanierMoyen($periode = null): array
    {
        [$where, $params] = $this->buildPeriodClause('fv.date_facture', $periode);

        $sql = "
            SELECT
                ec.id,
                ec.nom as client,
                ec.type_entreprise,
                COUNT(DISTINCT fv.id) AS nombre_factures,
                SUM(fv.montant_ttc) AS total_achats,
                AVG(fv.montant_ttc) AS panier_moyen,
                MAX(fv.montant_ttc) AS panier_max,
                MIN(fv.montant_ttc) AS panier_min
            FROM facture_vente fv
            INNER JOIN entreprise ec ON fv.entreprise_client_id = ec.id
            WHERE fv.statut_id = 3
            {$where}
            GROUP BY ec.id, ec.nom, ec.type_entreprise
            HAVING COUNT(DISTINCT fv.id) > 0
            ORDER BY panier_moyen DESC
            LIMIT 20
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return [
                'id' => (int)$row['id'],
                'client' => $row['client'],
                'nombre_factures' => (int)$row['nombre_factures'],
                'total_achats' => (float)$row['total_achats'],
                'panier_moyen' => (float)$row['panier_moyen'],
                'panier_max' => (float)$row['panier_max'],
                'panier_min' => (float)$row['panier_min']
            ];
        }, $rows);
    }

    /**
     * Taux de conversion Devis → Facture
     */
    public function getTauxConversion($periode = null): array
    {
        [$whereDevis, $paramsDevis] = $this->buildPeriodClause('dv.date_devis', $periode);
        [$whereFacture, $paramsFacture] = $this->buildPeriodClause('fv.date_facture', $periode);

        // Compter les devis
        $sqlDevis = "
            SELECT
                ec.id,
                ec.nom as client,
                COUNT(DISTINCT dv.id) AS nombre_devis,
                SUM(dv.montant_ttc) AS montant_devis
            FROM devis_vente dv
            INNER JOIN entreprise ec ON dv.entreprise_client_id = ec.id
            WHERE 1=1
            {$whereDevis}
            GROUP BY ec.id, ec.nom
        ";

        // Compter les factures
        $sqlFactures = "
            SELECT
                ec.id,
                ec.nom as client,
                COUNT(DISTINCT fv.id) AS nombre_factures,
                SUM(fv.montant_ttc) AS montant_factures
            FROM facture_vente fv
            INNER JOIN entreprise ec ON fv.entreprise_client_id = ec.id
            WHERE fv.statut_id = 3
            {$whereFacture}
            GROUP BY ec.id, ec.nom
        ";

        $stmtDevis = $this->db->prepare($sqlDevis);
        $stmtDevis->execute($paramsDevis);
        $devis = $stmtDevis->fetchAll(PDO::FETCH_ASSOC);

        $stmtFactures = $this->db->prepare($sqlFactures);
        $stmtFactures->execute($paramsFacture);
        $factures = $stmtFactures->fetchAll(PDO::FETCH_ASSOC);

        // Créer un index des factures par client
        $facturesParClient = [];
        foreach ($factures as $f) {
            $facturesParClient[$f['id']] = $f;
        }

        // Calculer les taux de conversion
        $results = [];
        foreach ($devis as $d) {
            $clientId = $d['id'];
            $nbDevis = (int)$d['nombre_devis'];
            $nbFactures = isset($facturesParClient[$clientId]) 
                ? (int)$facturesParClient[$clientId]['nombre_factures'] 
                : 0;

            $tauxConversion = $nbDevis > 0 
                ? round(($nbFactures / $nbDevis) * 100, 2) 
                : 0;

            $results[] = [
                'id' => $clientId,
                'client' => $d['client'],
                'nombre_devis' => $nbDevis,
                'nombre_factures' => $nbFactures,
                'taux_conversion' => (float)$tauxConversion,
                'montant_devis' => (float)$d['montant_devis'],
                'montant_factures' => isset($facturesParClient[$clientId]) 
                    ? (float)$facturesParClient[$clientId]['montant_factures'] 
                    : 0
            ];
        }

        // Trier par taux de conversion décroissant
        usort($results, function($a, $b) {
            return $b['taux_conversion'] <=> $a['taux_conversion'];
        });

        return array_slice($results, 0, 20);
    }

    /**
     * Construit une clause de filtre période
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
