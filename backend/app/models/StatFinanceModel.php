<?php

namespace app\models;

use PDO;

class StatFinanceModel extends AppModel {

    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    /**
     * Encours clients par société (Somme des restes à payer sur les factures de vente)
     */
    public function getEncoursClients() {
        $query = "
            SELECT 
                e.id AS entreprise_id,
                e.nom AS client_nom,
                COALESCE(SUM(fv.reste_a_payer), 0) AS total_encours
            FROM entreprise e
            LEFT JOIN facture_vente fv ON e.id = fv.entreprise_client_id
            WHERE e.type_entreprise = 'CLIENT'
            GROUP BY e.id, e.nom
            HAVING SUM(fv.reste_a_payer) > 0
            ORDER BY total_encours DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Encours fournisseurs par société (Somme des restes à payer sur les factures d'achat)
     */
    public function getEncoursFournisseurs() {
        $query = "
            SELECT 
                e.id AS entreprise_id,
                e.nom AS fournisseur_nom,
                COALESCE(SUM(fa.reste_a_payer), 0) AS total_encours
            FROM entreprise e
            LEFT JOIN facture_achat fa ON e.id = fa.entreprise_fournisseur_id
            WHERE e.type_entreprise = 'FOURNISSEUR'
            GROUP BY e.id, e.nom
            HAVING SUM(fa.reste_a_payer) > 0
            ORDER BY total_encours DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trésorerie nette par caisse (Solde actuel des caisses)
     */
    public function getTresorerieNet() {
        $query = "
            SELECT 
                c.id,
                c.libelle,
                c.solde_actuel,
                e.nom AS entreprise_nom
            FROM caisse c
            JOIN entreprise e ON c.entreprise_id = e.id
            ORDER BY c.solde_actuel DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcul du BFR (Besoin en Fonds de Roulement)
     * BFR = (Stocks + Créances Clients) - Dettes Fournisseurs
     */
    public function getBFR() {
        // 1. Valeur totale du stock
        $queryStock = "SELECT COALESCE(SUM(valeur_stock_total), 0) FROM stock";
        $stmtStock = $this->db->query($queryStock);
        $valeurStock = (float) $stmtStock->fetchColumn();

        // 2. Créances clients (Encours clients)
        $queryClients = "SELECT COALESCE(SUM(reste_a_payer), 0) FROM facture_vente";
        $stmtClients = $this->db->query($queryClients);
        $creancesClients = (float) $stmtClients->fetchColumn();

        // 3. Dettes fournisseurs (Encours fournisseurs)
        $queryFournisseurs = "SELECT COALESCE(SUM(reste_a_payer), 0) FROM facture_achat";
        $stmtFournisseurs = $this->db->query($queryFournisseurs);
        $dettesFournisseurs = (float) $stmtFournisseurs->fetchColumn();

        $bfr = ($valeurStock + $creancesClients) - $dettesFournisseurs;

        return [
            'valeur_stock' => $valeurStock,
            'creances_clients' => $creancesClients,
            'dettes_fournisseurs' => $dettesFournisseurs,
            'bfr' => $bfr
        ];
    }
}
