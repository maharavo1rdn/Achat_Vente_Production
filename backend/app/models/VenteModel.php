<?php

namespace app\models;

use Exception;
use InvalidArgumentException;
use PDO;

class VenteModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }


    public function getAllDevis($filters = [])
    {
        error_log("VenteModel::getAllDevis called with filters: " . json_encode($filters));

        $query = "
            SELECT
                dv.id,
                dv.numero_devis,
                dv.date_devis,
                dv.entreprise_client_id,
                dv.entreprise_filiale_id,
                dv.personnel_id,
                dv.statut_id,
                dv.montant_ttc,
                ec.nom as client_nom,
                efi.nom as filiale_nom,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom,
                s.libelle as statut_libelle
            FROM devis_vente dv
            INNER JOIN entreprise ec ON dv.entreprise_client_id = ec.id
            INNER JOIN entreprise efi ON dv.entreprise_filiale_id = efi.id
            INNER JOIN personnel p ON dv.personnel_id = p.id
            INNER JOIN statut s ON dv.statut_id = s.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['client_id'])) {
            $query .= " AND dv.entreprise_client_id = ?";
            $params[] = $filters['client_id'];
        }

        if (isset($filters['filiale_id'])) {
            $query .= " AND dv.entreprise_filiale_id = ?";
            $params[] = $filters['filiale_id'];
        }

        if (isset($filters['statut_id'])) {
            $query .= " AND dv.statut_id = ?";
            $params[] = $filters['statut_id'];
        }

        if (isset($filters['date_debut'])) {
            $query .= " AND dv.date_devis >= ?";
            $params[] = $filters['date_debut'];
        }

        if (isset($filters['date_fin'])) {
            $query .= " AND dv.date_devis <= ?";
            $params[] = $filters['date_fin'];
        }

        $query .= " ORDER BY dv.date_devis DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("VenteModel::getAllDevis retrieved " . count($results) . " devis");

        return $results;
    }

    public function getDevisById($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("VenteModel::getDevisById called with id=$id");

        $query = "
            SELECT
                dv.*,
                ec.nom as client_nom,
                efi.nom as filiale_nom,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom,
                s.libelle as statut_libelle
            FROM devis_vente dv
            INNER JOIN entreprise ec ON dv.entreprise_client_id = ec.id
            INNER JOIN entreprise efi ON dv.entreprise_filiale_id = efi.id
            INNER JOIN personnel p ON dv.personnel_id = p.id
            INNER JOIN statut s ON dv.statut_id = s.id
            WHERE dv.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['details'] = $this->getDevisDetails($id);
            error_log("VenteModel::getDevisById devis found with " . count($result['details']) . " details");
        } else {
            error_log("VenteModel::getDevisById devis with id=$id not found");
        }

        return $result ?: null;
    }

    public function createDevis($data)
    {
        $this->validateDevisData($data);

        error_log("VenteModel::createDevis called with data: " . json_encode($data));

        $query = "
            INSERT INTO devis_vente (
                numero_devis, date_devis, entreprise_client_id,
                entreprise_filiale_id, personnel_id, statut_id, montant_ttc
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['numero_devis'],
            $data['date_devis'] ?? date('Y-m-d'),
            $data['entreprise_client_id'],
            $data['entreprise_filiale_id'],
            $data['personnel_id'],
            $data['statut_id'],
            $data['montant_ttc'] ?? 0
        ]);

        $newId = $this->db->lastInsertId();

        if (isset($data['details']) && is_array($data['details'])) {
            $this->createDevisDetails($newId, $data['details']);
        }

        error_log("VenteModel::createDevis created devis with id=$newId");
        return (int)$newId;
    }

    public function updateDevis($id, $data)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        $this->validateDevisData($data, false);

        error_log("VenteModel::updateDevis called with id=$id, data: " . json_encode($data));

        $query = "
            UPDATE devis_vente SET
                numero_devis = ?,
                date_devis = ?,
                entreprise_client_id = ?,
                entreprise_filiale_id = ?,
                personnel_id = ?,
                statut_id = ?,
                montant_ttc = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([
            $data['numero_devis'],
            $data['date_devis'] ?? date('Y-m-d'),
            $data['entreprise_client_id'],
            $data['entreprise_filiale_id'],
            $data['personnel_id'],
            $data['statut_id'],
            $data['montant_ttc'] ?? 0,
            $id
        ]);

        if ($result && $stmt->rowCount() > 0) {
            if (isset($data['details']) && is_array($data['details'])) {
                $this->updateDevisDetails($id, $data['details']);
            }

            error_log("VenteModel::updateDevis updated devis with id=$id");
            return true;
        }

        error_log("VenteModel::updateDevis no devis updated with id=$id");
        return false;
    }

    public function deleteDevis($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("VenteModel::deleteDevis called with id=$id");

        $query = "DELETE FROM devis_vente WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("VenteModel::deleteDevis deleted devis with id=$id");
            return true;
        }

        error_log("VenteModel::deleteDevis no devis deleted with id=$id");
        return false;
    }

    public function convertDevisToBonCommande($devisId)
    {
        if ($devisId <= 0) {
            throw new InvalidArgumentException("L'ID de devis doit être un entier positif");
        }

        error_log("VenteModel::convertDevisToBonCommande called with devisId=$devisId");

        $devis = $this->getDevisById($devisId);
        if (!$devis) {
            throw new InvalidArgumentException("Devis non trouvé");
        }

        $numeroBc = $this->generateNumeroBonCommande();

        $bcData = [
            'numero_bc' => $numeroBc,
            'date_commande' => date('Y-m-d'),
            'devis_vente_id' => $devisId,
            'entreprise_client_id' => $devis['entreprise_client_id'],
            'entreprise_filiale_id' => $devis['entreprise_filiale_id'],
            'personnel_id' => $devis['personnel_id'],
            'statut_id' => $this->getStatutIdByCode('VALIDE'),
            'montant_ttc' => $devis['montant_ttc']
        ];

        $bcId = $this->createBonCommande($bcData);
        $this->copyDevisDetailsToBonCommande($devisId, $bcId);

        error_log("VenteModel::convertDevisToBonCommande created BC with id=$bcId");
        return $bcId;
    }

    // =================== BON DE COMMANDE VENTE ===================

    public function getAllBonCommande($filters = [])
    {
        error_log("VenteModel::getAllBonCommande called with filters: " . json_encode($filters));

        $query = "
            SELECT
                bcv.id,
                bcv.numero_bc,
                bcv.date_commande,
                bcv.devis_vente_id,
                bcv.entreprise_client_id,
                bcv.entreprise_filiale_id,
                bcv.personnel_id,
                bcv.statut_id,
                bcv.montant_ttc,
                ec.nom as client_nom,
                efi.nom as filiale_nom,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom,
                s.libelle as statut_libelle,
                dv.numero_devis
            FROM bon_commande_vente bcv
            INNER JOIN entreprise ec ON bcv.entreprise_client_id = ec.id
            INNER JOIN entreprise efi ON bcv.entreprise_filiale_id = efi.id
            INNER JOIN personnel p ON bcv.personnel_id = p.id
            INNER JOIN statut s ON bcv.statut_id = s.id
            LEFT JOIN devis_vente dv ON bcv.devis_vente_id = dv.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['client_id'])) {
            $query .= " AND bcv.entreprise_client_id = ?";
            $params[] = $filters['client_id'];
        }

        if (isset($filters['filiale_id'])) {
            $query .= " AND bcv.entreprise_filiale_id = ?";
            $params[] = $filters['filiale_id'];
        }

        if (isset($filters['statut_id'])) {
            $query .= " AND bcv.statut_id = ?";
            $params[] = $filters['statut_id'];
        }

        $query .= " ORDER BY bcv.date_commande DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("VenteModel::getAllBonCommande retrieved " . count($results) . " bon commandes");

        return $results;
    }

    public function getBonCommandeById($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("VenteModel::getBonCommandeById called with id=$id");

        $query = "
            SELECT
                bcv.*,
                ec.nom as client_nom,
                efi.nom as filiale_nom,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom,
                s.libelle as statut_libelle,
                dv.numero_devis
            FROM bon_commande_vente bcv
            INNER JOIN entreprise ec ON bcv.entreprise_client_id = ec.id
            INNER JOIN entreprise efi ON bcv.entreprise_filiale_id = efi.id
            INNER JOIN personnel p ON bcv.personnel_id = p.id
            INNER JOIN statut s ON bcv.statut_id = s.id
            LEFT JOIN devis_vente dv ON bcv.devis_vente_id = dv.id
            WHERE bcv.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['details'] = $this->getBonCommandeDetails($id);
            error_log("VenteModel::getBonCommandeById BC found with " . count($result['details']) . " details");
        } else {
            error_log("VenteModel::getBonCommandeById BC with id=$id not found");
        }

        return $result ?: null;
    }

    public function createBonCommande($data)
    {
        $this->validateBonCommandeData($data);

        error_log("VenteModel::createBonCommande called with data: " . json_encode($data));

        $query = "
            INSERT INTO bon_commande_vente (
                numero_bc, date_commande, devis_vente_id, entreprise_client_id,
                entreprise_filiale_id, personnel_id, statut_id, montant_ttc
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['numero_bc'],
            $data['date_commande'] ?? date('Y-m-d'),
            $data['devis_vente_id'] ?? null,
            $data['entreprise_client_id'],
            $data['entreprise_filiale_id'],
            $data['personnel_id'],
            $data['statut_id'],
            $data['montant_ttc'] ?? 0
        ]);

        $newId = $this->db->lastInsertId();

        if (isset($data['details']) && is_array($data['details'])) {
            $this->createBonCommandeDetails($newId, $data['details']);
        }

        error_log("VenteModel::createBonCommande created BC with id=$newId");
        return (int)$newId;
    }

    public function updateBonCommande($id, $data)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        $this->validateBonCommandeData($data, false);

        error_log("VenteModel::updateBonCommande called with id=$id, data: " . json_encode($data));

        $query = "
            UPDATE bon_commande_vente SET
                numero_bc = ?,
                date_commande = ?,
                devis_vente_id = ?,
                entreprise_client_id = ?,
                entreprise_filiale_id = ?,
                personnel_id = ?,
                statut_id = ?,
                montant_ttc = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([
            $data['numero_bc'],
            $data['date_commande'] ?? date('Y-m-d'),
            $data['devis_vente_id'] ?? null,
            $data['entreprise_client_id'],
            $data['entreprise_filiale_id'],
            $data['personnel_id'],
            $data['statut_id'],
            $data['montant_ttc'] ?? 0,
            $id
        ]);

        if ($result && $stmt->rowCount() > 0) {
            if (isset($data['details']) && is_array($data['details'])) {
                $this->updateBonCommandeDetails($id, $data['details']);
            }

            error_log("VenteModel::updateBonCommande updated BC with id=$id");
            return true;
        }

        error_log("VenteModel::updateBonCommande no BC updated with id=$id");
        return false;
    }

    public function deleteBonCommande($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("VenteModel::deleteBonCommande called with id=$id");

        $query = "DELETE FROM bon_commande_vente WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("VenteModel::deleteBonCommande deleted BC with id=$id");
            return true;
        }

        error_log("VenteModel::deleteBonCommande no BC deleted with id=$id");
        return false;
    }

    public function convertBonCommandeToFacture($bcId)
    {
        if ($bcId <= 0) {
            throw new InvalidArgumentException("L'ID de bon de commande doit être un entier positif");
        }

        error_log("VenteModel::convertBonCommandeToFacture called with bcId=$bcId");

        $bc = $this->getBonCommandeById($bcId);
        if (!$bc) {
            throw new InvalidArgumentException("Bon de commande non trouvé");
        }

        $numeroFacture = $this->generateNumeroFacture();

        $factureData = [
            'numero_facture' => $numeroFacture,
            'date_facture' => date('Y-m-d'),
            'bon_commande_vente_id' => $bcId,
            'entreprise_client_id' => $bc['entreprise_client_id'],
            'entreprise_filiale_id' => $bc['entreprise_filiale_id'],
            'personnel_id' => $bc['personnel_id'],
            'statut_id' => $this->getStatutIdByCode('PAYE'),
            'montant_ttc' => $bc['montant_ttc'],
            'reste_a_payer' => $bc['montant_ttc']
        ];

        $factureId = $this->createFacture($factureData);
        $this->copyBonCommandeDetailsToFacture($bcId, $factureId);

        error_log("VenteModel::convertBonCommandeToFacture created facture with id=$factureId");
        return $factureId;
    }

    // =================== FACTURES VENTE ===================

    public function getAllFactures($filters = [])
    {
        error_log("VenteModel::getAllFactures called with filters: " . json_encode($filters));

        $query = "
            SELECT
                fv.id,
                fv.numero_facture,
                fv.date_facture,
                fv.bon_commande_vente_id,
                fv.entreprise_client_id,
                fv.entreprise_filiale_id,
                fv.personnel_id,
                fv.statut_id,
                fv.montant_ttc,
                fv.reste_a_payer,
                ec.nom as client_nom,
                efi.nom as filiale_nom,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom,
                s.libelle as statut_libelle,
                bcv.numero_bc
            FROM facture_vente fv
            INNER JOIN entreprise ec ON fv.entreprise_client_id = ec.id
            INNER JOIN entreprise efi ON fv.entreprise_filiale_id = efi.id
            INNER JOIN personnel p ON fv.personnel_id = p.id
            INNER JOIN statut s ON fv.statut_id = s.id
            LEFT JOIN bon_commande_vente bcv ON fv.bon_commande_vente_id = bcv.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['client_id'])) {
            $query .= " AND fv.entreprise_client_id = ?";
            $params[] = $filters['client_id'];
        }

        if (isset($filters['filiale_id'])) {
            $query .= " AND fv.entreprise_filiale_id = ?";
            $params[] = $filters['filiale_id'];
        }

        if (isset($filters['statut_id'])) {
            $query .= " AND fv.statut_id = ?";
            $params[] = $filters['statut_id'];
        }

        $query .= " ORDER BY fv.date_facture DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("VenteModel::getAllFactures retrieved " . count($results) . " factures");

        return $results;
    }

    public function getFactureById($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("VenteModel::getFactureById called with id=$id");

        $query = "
            SELECT
                fv.*,
                ec.nom as client_nom,
                efi.nom as filiale_nom,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom,
                s.libelle as statut_libelle,
                bcv.numero_bc
            FROM facture_vente fv
            INNER JOIN entreprise ec ON fv.entreprise_client_id = ec.id
            INNER JOIN entreprise efi ON fv.entreprise_filiale_id = efi.id
            INNER JOIN personnel p ON fv.personnel_id = p.id
            INNER JOIN statut s ON fv.statut_id = s.id
            LEFT JOIN bon_commande_vente bcv ON fv.bon_commande_vente_id = bcv.id
            WHERE fv.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['details'] = $this->getFactureDetails($id);
            error_log("VenteModel::getFactureById facture found with " . count($result['details']) . " details");
        } else {
            error_log("VenteModel::getFactureById facture with id=$id not found");
        }

        return $result ?: null;
    }

    public function createFacture($data)
    {
        $this->validateFactureData($data);

        error_log("VenteModel::createFacture called with data: " . json_encode($data));

        $query = "
            INSERT INTO facture_vente (
                numero_facture, date_facture, bon_commande_vente_id,
                entreprise_client_id, entreprise_filiale_id, personnel_id,
                statut_id, montant_ttc, reste_a_payer
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['numero_facture'],
            $data['date_facture'] ?? date('Y-m-d'),
            $data['bon_commande_vente_id'] ?? null,
            $data['entreprise_client_id'],
            $data['entreprise_filiale_id'],
            $data['personnel_id'],
            $data['statut_id'],
            $data['montant_ttc'] ?? 0,
            $data['reste_a_payer'] ?? $data['montant_ttc'] ?? 0
        ]);

        $newId = $this->db->lastInsertId();

        if (isset($data['details']) && is_array($data['details'])) {
            $this->createFactureDetails($newId, $data['details']);
        }

        error_log("VenteModel::createFacture created facture with id=$newId");
        return (int)$newId;
    }

    public function updateFacture($id, $data)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        $this->validateFactureData($data, false);

        error_log("VenteModel::updateFacture called with id=$id, data: " . json_encode($data));

        $query = "
            UPDATE facture_vente SET
                numero_facture = ?,
                date_facture = ?,
                bon_commande_vente_id = ?,
                entreprise_client_id = ?,
                entreprise_filiale_id = ?,
                personnel_id = ?,
                statut_id = ?,
                montant_ttc = ?,
                reste_a_payer = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([
            $data['numero_facture'],
            $data['date_facture'] ?? date('Y-m-d'),
            $data['bon_commande_vente_id'] ?? null,
            $data['entreprise_client_id'],
            $data['entreprise_filiale_id'],
            $data['personnel_id'],
            $data['statut_id'],
            $data['montant_ttc'] ?? 0,
            $data['reste_a_payer'] ?? $data['montant_ttc'] ?? 0,
            $id
        ]);

        if ($result && $stmt->rowCount() > 0) {
            if (isset($data['details']) && is_array($data['details'])) {
                $this->updateFactureDetails($id, $data['details']);
            }

            error_log("VenteModel::updateFacture updated facture with id=$id");
            return true;
        }

        error_log("VenteModel::updateFacture no facture updated with id=$id");
        return false;
    }

    public function deleteFacture($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("VenteModel::deleteFacture called with id=$id");

        $query = "DELETE FROM facture_vente WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("VenteModel::deleteFacture deleted facture with id=$id");
            return true;
        }

        error_log("VenteModel::deleteFacture no facture deleted with id=$id");
        return false;
    }

    // =================== MÉTHODES UTILITAIRES ===================

    private function getDevisDetails(int $devisId): array
    {
        $query = "
            SELECT dvd.*, a.reference, a.designation
            FROM devis_vente_details dvd
            INNER JOIN article a ON dvd.article_id = a.id
            WHERE dvd.devis_vente_id = ?
            ORDER BY dvd.id
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$devisId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getBonCommandeDetails(int $bcId): array
    {
        $query = "
            SELECT bcvd.*, a.reference, a.designation
            FROM bon_commande_vente_details bcvd
            INNER JOIN article a ON bcvd.article_id = a.id
            WHERE bcvd.bon_commande_vente_id = ?
            ORDER BY bcvd.id
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$bcId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getFactureDetails(int $factureId): array
    {
        $query = "
            SELECT fvd.*, a.reference, a.designation
            FROM facture_vente_details fvd
            INNER JOIN article a ON fvd.article_id = a.id
            WHERE fvd.facture_vente_id = ?
            ORDER BY fvd.id
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$factureId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function createDevisDetails(int $devisId, array $details): void
    {
        foreach ($details as $detail) {
            $query = "INSERT INTO devis_vente_details (devis_vente_id, article_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$devisId, $detail['article_id'], $detail['quantite'], $detail['prix_unitaire']]);
        }
    }

    private function createBonCommandeDetails(int $bcId, array $details): void
    {
        foreach ($details as $detail) {
            $query = "INSERT INTO bon_commande_vente_details (bon_commande_vente_id, article_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$bcId, $detail['article_id'], $detail['quantite'], $detail['prix_unitaire']]);
        }
    }

    private function createFactureDetails(int $factureId, array $details): void
    {
        foreach ($details as $detail) {
            $query = "INSERT INTO facture_vente_details (facture_vente_id, article_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$factureId, $detail['article_id'], $detail['quantite'], $detail['prix_unitaire']]);
        }
    }

    private function updateDevisDetails(int $devisId, array $details): void
    {
        $query = "DELETE FROM devis_vente_details WHERE devis_vente_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$devisId]);
        $this->createDevisDetails($devisId, $details);
    }

    private function updateBonCommandeDetails(int $bcId, array $details): void
    {
        $query = "DELETE FROM bon_commande_vente_details WHERE bon_commande_vente_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$bcId]);
        $this->createBonCommandeDetails($bcId, $details);
    }

    private function updateFactureDetails(int $factureId, array $details): void
    {
        $query = "DELETE FROM facture_vente_details WHERE facture_vente_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$factureId]);
        $this->createFactureDetails($factureId, $details);
    }

    private function copyDevisDetailsToBonCommande(int $devisId, int $bcId): void
    {
        $details = $this->getDevisDetails($devisId);
        foreach ($details as $detail) {
            $query = "INSERT INTO bon_commande_vente_details (bon_commande_vente_id, article_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$bcId, $detail['article_id'], $detail['quantite'], $detail['prix_unitaire']]);
        }
    }

    private function copyBonCommandeDetailsToFacture(int $bcId, int $factureId): void
    {
        $details = $this->getBonCommandeDetails($bcId);
        foreach ($details as $detail) {
            $query = "INSERT INTO facture_vente_details (facture_vente_id, article_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$factureId, $detail['article_id'], $detail['quantite'], $detail['prix_unitaire']]);
        }
    }

    private function generateNumeroBonCommande(): string
    {
        $date = date('Ym');
        $query = "SELECT COUNT(*) as count FROM bon_commande_vente WHERE numero_bc LIKE ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$date . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $numero = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
        return $date . $numero;
    }

    private function generateNumeroFacture(): string
    {
        $date = date('Ym');
        $query = "SELECT COUNT(*) as count FROM facture_vente WHERE numero_facture LIKE ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$date . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $numero = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
        return 'FV' . $date . $numero;
    }

    private function getStatutIdByCode(string $code): int
    {
        $query = "SELECT id FROM statut WHERE code = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : 1;
    }

    private function validateDevisData(array $data, bool $isCreation = true): void
    {
        if ($isCreation || isset($data['numero_devis'])) {
            if (empty($data['numero_devis'])) {
                throw new InvalidArgumentException("Le numéro de devis est obligatoire");
            }
        }

        if (($isCreation || isset($data['entreprise_client_id'])) && (!isset($data['entreprise_client_id']) || $data['entreprise_client_id'] <= 0)) {
            throw new InvalidArgumentException("Le client est obligatoire");
        }

        if (($isCreation || isset($data['entreprise_filiale_id'])) && (!isset($data['entreprise_filiale_id']) || $data['entreprise_filiale_id'] <= 0)) {
            throw new InvalidArgumentException("La filiale est obligatoire");
        }

        if (($isCreation || isset($data['personnel_id'])) && (!isset($data['personnel_id']) || $data['personnel_id'] <= 0)) {
            throw new InvalidArgumentException("Le personnel est obligatoire");
        }

        if (($isCreation || isset($data['statut_id'])) && (!isset($data['statut_id']) || $data['statut_id'] <= 0)) {
            throw new InvalidArgumentException("Le statut est obligatoire");
        }
    }

    private function validateBonCommandeData(array $data, bool $isCreation = true): void
    {
        if ($isCreation || isset($data['numero_bc'])) {
            if (empty($data['numero_bc'])) {
                throw new InvalidArgumentException("Le numéro BC est obligatoire");
            }
        }

        if (($isCreation || isset($data['entreprise_client_id'])) && (!isset($data['entreprise_client_id']) || $data['entreprise_client_id'] <= 0)) {
            throw new InvalidArgumentException("Le client est obligatoire");
        }

        if (($isCreation || isset($data['entreprise_filiale_id'])) && (!isset($data['entreprise_filiale_id']) || $data['entreprise_filiale_id'] <= 0)) {
            throw new InvalidArgumentException("La filiale est obligatoire");
        }

        if (($isCreation || isset($data['personnel_id'])) && (!isset($data['personnel_id']) || $data['personnel_id'] <= 0)) {
            throw new InvalidArgumentException("Le personnel est obligatoire");
        }

        if (($isCreation || isset($data['statut_id'])) && (!isset($data['statut_id']) || $data['statut_id'] <= 0)) {
            throw new InvalidArgumentException("Le statut est obligatoire");
        }
    }

    private function validateFactureData(array $data, bool $isCreation = true): void
    {
        if ($isCreation || isset($data['numero_facture'])) {
            if (empty($data['numero_facture'])) {
                throw new InvalidArgumentException("Le numéro de facture est obligatoire");
            }
        }

        if (($isCreation || isset($data['entreprise_client_id'])) && (!isset($data['entreprise_client_id']) || $data['entreprise_client_id'] <= 0)) {
            throw new InvalidArgumentException("Le client est obligatoire");
        }

        if (($isCreation || isset($data['entreprise_filiale_id'])) && (!isset($data['entreprise_filiale_id']) || $data['entreprise_filiale_id'] <= 0)) {
            throw new InvalidArgumentException("La filiale est obligatoire");
        }

        if (($isCreation || isset($data['personnel_id'])) && (!isset($data['personnel_id']) || $data['personnel_id'] <= 0)) {
            throw new InvalidArgumentException("Le personnel est obligatoire");
        }

        if (($isCreation || isset($data['statut_id'])) && (!isset($data['statut_id']) || $data['statut_id'] <= 0)) {
            throw new InvalidArgumentException("Le statut est obligatoire");
        }
    }

    public function payerFacture($id, $data)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        $this->db->beginTransaction();

        try {
            // Mettre à jour la facture
            $stmt = $this->db->prepare("
                UPDATE facture_vente SET 
                    statut_paiement = 'PAYEE',
                    date_paiement = NOW(),
                    mode_paiement = ?
                WHERE id = ?
            ");
            $stmt->execute([$data['mode_paiement'] ?? 'ESPECES', $id]);
            
            // Enregistrer dans la caisse (entrée)
            $stmt = $this->db->prepare("
                SELECT fv.montant_ttc, fv.entreprise_filiale_id, fv.numero
                FROM facture_vente fv
                WHERE fv.id = ?
            ");
            $stmt->execute([$id]);
            $facture = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($facture) {
                $stmt = $this->db->prepare("
                    INSERT INTO caisse_mouvement (
                        type_mouvement, montant, date_mouvement, description,
                        caisse_id, personnel_id, reference_document
                    )
                    SELECT 'ENTREE', ?, NOW(), ?, c.id, ?, ?
                    FROM caisse c
                    WHERE c.entreprise_id = ?
                    LIMIT 1
                ");
                $stmt->execute([
                    $facture['montant_ttc'],
                    'Paiement facture vente ' . $facture['numero'],
                    $data['personnel_id'],
                    'FACTURE_VENTE_' . $id,
                    $facture['entreprise_filiale_id']
                ]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}