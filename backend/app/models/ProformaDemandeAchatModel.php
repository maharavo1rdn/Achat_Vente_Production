<?php

namespace app\models;

use InvalidArgumentException;
use PDO;
use Exception;

class ProformaDemandeAchatModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    public function getAll($filters = [])
    {
        error_log("ProformaDemandeAchatModel::getAll called with filters: " . json_encode($filters));

        $query = "
            SELECT
                pda.id,
                pda.numero_da,
                pda.date_demande,
                pda.personnel_demandeur_id,
                p.nom || ' ' || COALESCE(p.prenom, '') AS demandeur_nom,
                pda.entreprise_id,
                e.nom AS entreprise_nom,
                pda.depot_cible_id,
                d.nom AS depot_nom,
                pda.date_souhaitee,
                pda.motif_achat,
                pda.montant_ttc,
                pda.statut_id,
                s.libelle AS statut_libelle,
                s.code AS statut_code,
                pda.date_creation
            FROM proforma_demande_achat pda
            LEFT JOIN personnel p ON p.id = pda.personnel_demandeur_id
            LEFT JOIN entreprise e ON e.id = pda.entreprise_id
            LEFT JOIN depot d ON d.id = pda.depot_cible_id
            LEFT JOIN statut s ON s.id = pda.statut_id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['entreprise_id'])) {
            $query .= " AND pda.entreprise_id = ?";
            $params[] = (int)$filters['entreprise_id'];
        }

        if (isset($filters['depot_cible_id'])) {
            $query .= " AND pda.depot_cible_id = ?";
            $params[] = (int)$filters['depot_cible_id'];
        }

        if (isset($filters['statut_id'])) {
            $query .= " AND pda.statut_id = ?";
            $params[] = (int)$filters['statut_id'];
        }

        if (isset($filters['personnel_demandeur_id'])) {
            $query .= " AND pda.personnel_demandeur_id = ?";
            $params[] = (int)$filters['personnel_demandeur_id'];
        }

        if (isset($filters['date_debut']) && $filters['date_debut'] !== '') {
            $query .= " AND pda.date_demande >= ?";
            $params[] = $filters['date_debut'];
        }

        if (isset($filters['date_fin']) && $filters['date_fin'] !== '') {
            $query .= " AND pda.date_demande <= ?";
            $params[] = $filters['date_fin'];
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $s = '%' . mb_strtoupper($filters['search'], 'UTF-8') . '%';
            $query .= " AND (UPPER(pda.numero_da) LIKE ? OR UPPER(p.nom) LIKE ? OR UPPER(e.nom) LIKE ?)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        $query .= " ORDER BY pda.date_demande DESC, pda.id DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        if ($id <= 0) throw new InvalidArgumentException("L'ID doit être un entier positif");

        $query = "
            SELECT
                pda.id,
                pda.numero_da,
                pda.date_demande,
                pda.personnel_demandeur_id,
                p.nom || ' ' || COALESCE(p.prenom, '') AS demandeur_nom,
                pda.entreprise_id,
                e.nom AS entreprise_nom,
                pda.depot_cible_id,
                d.nom AS depot_nom,
                pda.date_souhaitee,
                pda.motif_achat,
                pda.montant_ttc,
                pda.statut_id,
                s.libelle AS statut_libelle,
                s.code AS statut_code,
                pda.date_creation
            FROM proforma_demande_achat pda
            LEFT JOIN personnel p ON p.id = pda.personnel_demandeur_id
            LEFT JOIN entreprise e ON e.id = pda.entreprise_id
            LEFT JOIN depot d ON d.id = pda.depot_cible_id
            LEFT JOIN statut s ON s.id = pda.statut_id
            WHERE pda.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create($data)
    {
        $this->validateDemandeData($data);

        try {
            $this->db->beginTransaction();

            $numeroDA = $this->generateNumeroDA();

            // compute montant_ttc from details (denormalisation)
            $montantTTC = 0;
            if (isset($data['details']) && is_array($data['details']) && count($data['details']) > 0) {
                $montantTTC = $this->computeMontantFromDetails($data['details']);
            }

            $query = "
                INSERT INTO proforma_demande_achat 
                (numero_da, date_demande, personnel_demandeur_id, entreprise_id, depot_cible_id, date_souhaitee, motif_achat, montant_ttc, statut_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $numeroDA,
                $data['date_demande'] ?? date('Y-m-d'),
                (int)$data['personnel_demandeur_id'],
                (int)$data['entreprise_id'],
                isset($data['depot_cible_id']) && $data['depot_cible_id'] ? (int)$data['depot_cible_id'] : null,
                $data['date_souhaitee'] ?? null,
                $data['motif_achat'] ?? null,
                $montantTTC,
                2
            ]);

            $demandeId = (int)$this->db->lastInsertId();

            if (isset($data['details']) && is_array($data['details']) && count($data['details']) > 0) {
                $this->insertDetails($demandeId, $data['details']);
            }

            $this->db->commit();
            
            return $demandeId;
            exit();

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update($id, $data)
    {
        if ($id <= 0) throw new InvalidArgumentException("L'ID doit être un entier positif");
        $this->validateDemandeData($data, false);

        try {
            $this->db->beginTransaction();

            $montantTTC = null;
            if (isset($data['details']) && is_array($data['details'])) {
                $montantTTC = $this->computeMontantFromDetails($data['details']);
            }

            if ($montantTTC !== null) {
                $query = "
                    UPDATE proforma_demande_achat 
                    SET date_demande = ?, personnel_demandeur_id = ?, entreprise_id = ?, 
                        depot_cible_id = ?, date_souhaitee = ?, motif_achat = ?, montant_ttc = ?
                    WHERE id = ?
                ";

                $params = [
                    $data['date_demande'] ?? date('Y-m-d'),
                    (int)$data['personnel_demandeur_id'],
                    (int)$data['entreprise_id'],
                    isset($data['depot_cible_id']) && $data['depot_cible_id'] ? (int)$data['depot_cible_id'] : null,
                    $data['date_souhaitee'] ?? null,
                    $data['motif_achat'] ?? null,
                    $montantTTC,
                    $id
                ];
            } else {
                $query = "
                    UPDATE proforma_demande_achat 
                    SET date_demande = ?, personnel_demandeur_id = ?, entreprise_id = ?, 
                        depot_cible_id = ?, date_souhaitee = ?, motif_achat = ?
                    WHERE id = ?
                ";

                $params = [
                    $data['date_demande'] ?? date('Y-m-d'),
                    (int)$data['personnel_demandeur_id'],
                    (int)$data['entreprise_id'],
                    isset($data['depot_cible_id']) && $data['depot_cible_id'] ? (int)$data['depot_cible_id'] : null,
                    $data['date_souhaitee'] ?? null,
                    $data['motif_achat'] ?? null,
                    $id
                ];
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            if (isset($data['details']) && is_array($data['details'])) {
                $this->updateDetails($id, $data['details']);
            }

            $this->db->commit();
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        if ($id <= 0) throw new InvalidArgumentException("L'ID doit être un entier positif");

        $stmt = $this->db->prepare("DELETE FROM proforma_demande_achat WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function getDetails($demandeId)
    {
        $query = "
            SELECT
                pdad.id,
                pdad.proforma_demande_achat_id,
                pdad.article_id,
                a.reference AS article_reference,
                a.designation AS article_designation,
                pdad.quantite_demandee,
                pdad.prix_estime
            FROM proforma_demande_achat_details pdad
            LEFT JOIN article a ON a.id = pdad.article_id
            WHERE pdad.proforma_demande_achat_id = ?
            ORDER BY pdad.id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$demandeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatut($id, $nouveauStatut)
    {
        if ($id <= 0) throw new InvalidArgumentException("L'ID doit être un entier positif");

        $stmt = $this->db->prepare("UPDATE proforma_demande_achat SET statut_id = ? WHERE id = ?");
        $stmt->execute([$nouveauStatut, $id]);
        return $stmt->rowCount() > 0;
    }

    private function insertDetails($demandeId, $details)
    {
        $query = "
            INSERT INTO proforma_demande_achat_details 
            (proforma_demande_achat_id, article_id, quantite_demandee, prix_estime)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);

        foreach ($details as $detail) {
            if (!isset($detail['article_id']) || !isset($detail['quantite_demandee'])) {
                throw new InvalidArgumentException("Chaque détail doit contenir article_id et quantite_demandee");
            }

            $stmt->execute([
                $demandeId,
                (int)$detail['article_id'],
                $detail['quantite_demandee'],
                $detail['prix_estime'] ?? 0
            ]);
        }
    }

    private function updateDetails($demandeId, $details)
    {
        $stmt = $this->db->prepare("DELETE FROM proforma_demande_achat_details WHERE proforma_demande_achat_id = ?");
        $stmt->execute([$demandeId]);

        if (count($details) > 0) {
            $this->insertDetails($demandeId, $details);
        }
    }

    private function computeMontantFromDetails($details)
    {
        $total = 0.0;
        foreach ($details as $d) {
            $q = isset($d['quantite_demandee']) ? (float)$d['quantite_demandee'] : 0;
            $p = isset($d['prix_estime']) ? (float)$d['prix_estime'] : 0;
            $total += $q * $p;
        }
        return round($total, 2);
    }

    public function checkStockAvailability($depotId, $details)
    {
        if (empty($depotId) || !is_numeric($depotId)) {
            throw new InvalidArgumentException('depot_cible_id invalide');
        }

        if (!is_array($details) || count($details) === 0) {
            return ['in_stock' => [], 'count' => 0, 'all_clear' => true];
        }

        $articleIds = [];
        foreach ($details as $d) {
            if (isset($d['article_id']) && (int)$d['article_id'] > 0) {
                $articleIds[] = (int)$d['article_id'];
            }
        }

        if (count($articleIds) === 0) {
            return ['in_stock' => [], 'count' => 0, 'all_clear' => true];
        }

        $placeholders = implode(',', array_fill(0, count($articleIds), '?'));
        $params = array_merge([$depotId], $articleIds);

        $query = "
            SELECT s.article_id, s.quantite_actuelle, a.reference, a.designation, u.libelle as unite
            FROM stock s
            LEFT JOIN article a ON a.id = s.article_id
            LEFT JOIN unite u ON a.unite_id = u.id
            WHERE s.depot_id = ? AND s.article_id IN ($placeholders)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stockMap = [];
        foreach ($stocks as $s) {
            $stockMap[(int)$s['article_id']] = $s;
        }

        $inStock = [];
        foreach ($details as $d) {
            $aid = isset($d['article_id']) ? (int)$d['article_id'] : null;
            $reqQty = isset($d['quantite_demandee']) ? (float)$d['quantite_demandee'] : 0;
            $avail = 0;
            $ref = null;
            $designation = null;
            $unite = null;
            if ($aid !== null && isset($stockMap[$aid])) {
                $avail = (float)$stockMap[$aid]['quantite_actuelle'];
                $ref = $stockMap[$aid]['reference'] ?? null;
                $designation = $stockMap[$aid]['designation'] ?? null;
                $unite = $stockMap[$aid]['unite'] ?? null;
            }

            if ($avail > 0) {
                $inStock[] = [
                    'article_id' => $aid,
                    'reference' => $ref,
                    'designation' => $designation,
                    'requested_qty' => $reqQty,
                    'available_qty' => $avail,
                    'unite' => $unite
                ];
            }
        }

        return [
            'in_stock' => $inStock,
            'count' => count($inStock),
            'all_clear' => count($inStock) === 0
        ];
    }

    private function generateNumeroDA()
    {
        $year = date('Y');
        $prefix = "DA-{$year}-";

        $stmt = $this->db->prepare("
            SELECT numero_da 
            FROM proforma_demande_achat 
            WHERE numero_da LIKE ? 
            ORDER BY numero_da DESC 
            LIMIT 1
        ");
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();

        if ($last) {
            $num = (int)substr($last, -4);
            $num++;
        } else {
            $num = 1;
        }

        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    private function validateDemandeData($data, $isCreation = true)
    {
        if ($isCreation || isset($data['personnel_demandeur_id'])) {
            if (empty($data['personnel_demandeur_id'])) {
                throw new InvalidArgumentException("Le demandeur est obligatoire");
            }
        }

        if ($isCreation || isset($data['entreprise_id'])) {
            if (empty($data['entreprise_id'])) {
                throw new InvalidArgumentException("L'entreprise est obligatoire");
            }
        }

        if ($isCreation && (!isset($data['details']) || !is_array($data['details']) || count($data['details']) === 0)) {
            throw new InvalidArgumentException("Au moins un article doit être demandé");
        }
    }
}
