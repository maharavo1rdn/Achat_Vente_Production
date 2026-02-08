<?php

namespace app\models;

use InvalidArgumentException;
use PDO;
use Flight;

class AchatModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }


    public function convertProformaToBonCommande($proformaId)
    {
        if ($proformaId <= 0) {
            throw new InvalidArgumentException("L'ID de proforma doit être un entier positif");
        }

        error_log("AchatModel::convertProformaToBonCommande called with proformaId=$proformaId");

        // Récupérer la proforma depuis le modèle dédié
        $proforma = Flight::proformaFournisseurModel()->getById($proformaId);
        if (!$proforma) {
            throw new InvalidArgumentException("Proforma non trouvée");
        }

        // Récupérer les détails depuis le modèle dédié
        $details = Flight::proformaFournisseurModel()->getDetails($proformaId);

        // Générer numéro BC
        $numeroBc = $this->generateNumeroBonCommande();

        // Créer le bon de commande
        $bcData = [
            'numero_bc' => $numeroBc,
            'date_commande' => date('Y-m-d'),
            'proforma_fournisseur_id' => $proformaId,
            'entreprise_fournisseur_id' => $proforma['entreprise_fournisseur_id'],
            'entreprise_filiale_id' => $proforma['entreprise_filiale_id'],
            'personnel_id' => $proforma['personnel_id'],
            'statut_id' => $this->getStatutIdByCode('VALIDE'),
            'montant_ttc' => $proforma['montant_ttc'],
            'depot_livraison_id' => $proforma['depot_livraison_id'] ?? null
        ];

        $bcId = $this->createBonCommande($bcData);

        // Copier les détails fournis
        if ($details && count($details) > 0) {
            $this->createBonCommandeDetails($bcId, array_map(function($d){
                return [
                    'article_id' => $d['article_id'],
                    'quantite' => $d['quantite'],
                    'prix_unitaire' => $d['prix_unitaire'] ?? ($d['prix_unitaire'] ?? 0)
                ];
            }, $details));
        }

        error_log("AchatModel::convertProformaToBonCommande created BC with id=$bcId");
        return $bcId;
    }

    

    // =================== BON DE COMMANDE ACHAT ===================

    public function getAllBonCommande($filters = [])
    {
        error_log("AchatModel::getAllBonCommande called with filters: " . json_encode($filters));

        $query = "
            SELECT
                bca.id,
                bca.numero_bc,
                bca.date_commande,
                bca.proforma_fournisseur_id,
                bca.entreprise_fournisseur_id,
                bca.entreprise_filiale_id,
                bca.personnel_id,
                bca.statut_id,
                s.code as statut,
                s.libelle as statut_libelle,
                bca.montant_ttc,
                ef.nom as fournisseur_nom,
                efi.nom as filiale_nom,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom,
                s.libelle as statut_libelle,
                pf.numero_proforma as proforma_origine
            FROM bon_commande_achat bca
            INNER JOIN entreprise ef ON bca.entreprise_fournisseur_id = ef.id
            INNER JOIN entreprise efi ON bca.entreprise_filiale_id = efi.id
            INNER JOIN personnel p ON bca.personnel_id = p.id
            INNER JOIN statut s ON bca.statut_id = s.id
            LEFT JOIN proforma_fournisseur pf ON bca.proforma_fournisseur_id = pf.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['fournisseur_id'])) {
            $query .= " AND bca.entreprise_fournisseur_id = ?";
            $params[] = $filters['fournisseur_id'];
        }

        if (isset($filters['filiale_id'])) {
            $query .= " AND bca.entreprise_filiale_id = ?";
            $params[] = $filters['filiale_id'];
        }

        if (isset($filters['statut_id'])) {
            $query .= " AND bca.statut_id = ?";
            $params[] = $filters['statut_id'];
        }

        $query .= " ORDER BY bca.date_commande DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("AchatModel::getAllBonCommande retrieved " . count($results) . " bon commandes");

        return $results;
    }

    public function getBonCommandeById($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("AchatModel::getBonCommandeById called with id=$id");

        $query = "
            SELECT
                bca.*,
                ef.nom as fournisseur_nom,
                efi.nom as filiale_nom,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom,
                s.libelle as statut_libelle,
                pf.numero_proforma
            FROM bon_commande_achat bca
            INNER JOIN entreprise ef ON bca.entreprise_fournisseur_id = ef.id
            INNER JOIN entreprise efi ON bca.entreprise_filiale_id = efi.id
            INNER JOIN personnel p ON bca.personnel_id = p.id
            INNER JOIN statut s ON bca.statut_id = s.id
            LEFT JOIN proforma_fournisseur pf ON bca.proforma_fournisseur_id = pf.id
            WHERE bca.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['details'] = $this->getBonCommandeDetails($id);
            error_log("AchatModel::getBonCommandeById BC found with " . count($result['details']) . " details");
        } else {
            error_log("AchatModel::getBonCommandeById BC with id=$id not found");
        }

        return $result ?: null;
    }

    public function createBonCommande($data)
    {
        $this->validateBonCommandeData($data);

        error_log("AchatModel::createBonCommande called with data: " . json_encode($data));

        $query = "
            INSERT INTO bon_commande_achat (
                numero_bc, date_commande, proforma_fournisseur_id, entreprise_fournisseur_id,
                entreprise_filiale_id, personnel_id, statut_id, montant_ttc,depot_livraison_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['numero_bc'],
            $data['date_commande'] ?? date('Y-m-d'),
            $data['proforma_fournisseur_id'] ?? null,
            $data['entreprise_fournisseur_id'],
            $data['entreprise_filiale_id'],
            $data['personnel_id'],
            $data['statut_id'],
            $data['montant_ttc'] ?? 0,
            $data['depot_livraison_id'] ?? 1
        ]);

        $newId = $this->db->lastInsertId();

        if (isset($data['details']) && is_array($data['details'])) {
            $this->createBonCommandeDetails($newId, $data['details']);
        }

        error_log("AchatModel::createBonCommande created BC with id=$newId");
        return (int)$newId;
    }

    public function updateBonCommande($id, $data)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        $this->validateBonCommandeData($data, false);

        error_log("AchatModel::updateBonCommande called with id=$id, data: " . json_encode($data));

        $query = "
            UPDATE bon_commande_achat SET
                numero_bc = ?,
                date_commande = ?,
                proforma_fournisseur_id = ?,
                entreprise_fournisseur_id = ?,
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
            $data['proforma_fournisseur_id'] ?? null,
            $data['entreprise_fournisseur_id'],
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

            error_log("AchatModel::updateBonCommande updated BC with id=$id");
            return true;
        }

        error_log("AchatModel::updateBonCommande no BC updated with id=$id");
        return false;
    }

    public function deleteBonCommande($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("AchatModel::deleteBonCommande called with id=$id");

        $query = "DELETE FROM bon_commande_achat WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("AchatModel::deleteBonCommande deleted BC with id=$id");
            return true;
        }

        error_log("AchatModel::deleteBonCommande no BC deleted with id=$id");
        return false;
    }

    public function convertBonCommandeToFacture($bcId)
    {
        if ($bcId <= 0) {
            throw new InvalidArgumentException("L'ID de bon de commande doit être un entier positif");
        }

        error_log("AchatModel::convertBonCommandeToFacture called with bcId=$bcId");

        $bc = $this->getBonCommandeById($bcId);
        if (!$bc) {
            throw new InvalidArgumentException("Bon de commande non trouvé");
        }

        $numeroFacture = $this->generateNumeroFactureAchat();

        $factureData = [
            'numero_facture_fournisseur' => $numeroFacture,
            'date_facture' => date('Y-m-d'),
            'bon_commande_achat_id' => $bcId,
            'entreprise_fournisseur_id' => $bc['entreprise_fournisseur_id'],
            'entreprise_filiale_id' => $bc['entreprise_filiale_id'],
            'statut_id' => $this->getStatutIdByCode('PAYE'),
            'montant_ttc' => $bc['montant_ttc'],
            'reste_a_payer' => $bc['montant_ttc']
        ];

        $factureId = $this->createFacture($factureData);
        $fac=$this->getFactureById($factureId);
        $this->copyBonCommandeDetailsToFacture($bcId, $factureId,$fac['numero_facture_fournisseur'],$bc['personnel_id'],$bc['depot_livraison_id']);

        


        error_log("AchatModel::convertBonCommandeToFacture created facture with id=$factureId");
        return $factureId;
    }

    

    // =================== FACTURES ACHAT ===================

    public function getAllFactures($filters = [])
    {
        error_log("AchatModel::getAllFactures called with filters: " . json_encode($filters));

        $query = "
            SELECT
                fa.id,
                fa.numero_facture_fournisseur,
                fa.date_facture,
                fa.bon_commande_achat_id,
                fa.entreprise_fournisseur_id,
                fa.entreprise_filiale_id,
                fa.statut_id,
                fa.montant_ttc,
                fa.reste_a_payer,
                fa.depot_reception_id,
                ef.nom as fournisseur_nom,
                efi.nom as filiale_nom,
                s.libelle as statut_libelle,
                bca.numero_bc as bc_origine
            FROM facture_achat fa
            INNER JOIN entreprise ef ON fa.entreprise_fournisseur_id = ef.id
            INNER JOIN entreprise efi ON fa.entreprise_filiale_id = efi.id
            INNER JOIN statut s ON fa.statut_id = s.id
            LEFT JOIN bon_commande_achat bca ON fa.bon_commande_achat_id = bca.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['fournisseur_id'])) {
            $query .= " AND fa.entreprise_fournisseur_id = ?";
            $params[] = $filters['fournisseur_id'];
        }

        if (isset($filters['filiale_id'])) {
            $query .= " AND fa.entreprise_filiale_id = ?";
            $params[] = $filters['filiale_id'];
        }

        if (isset($filters['statut_id'])) {
            $query .= " AND fa.statut_id = ?";
            $params[] = $filters['statut_id'];
        }

        $query .= " ORDER BY fa.date_facture DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("AchatModel::getAllFactures retrieved " . count($results) . " factures");

        return $results;
    }

    public function getFactureById($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("AchatModel::getFactureById called with id=$id");

        $query = "
            SELECT
                fa.*,
                ef.nom as fournisseur_nom,
                efi.nom as filiale_nom,
                s.libelle as statut_libelle,
                bca.numero_bc
            FROM facture_achat fa
            INNER JOIN entreprise ef ON fa.entreprise_fournisseur_id = ef.id
            INNER JOIN entreprise efi ON fa.entreprise_filiale_id = efi.id
            INNER JOIN statut s ON fa.statut_id = s.id
            LEFT JOIN bon_commande_achat bca ON fa.bon_commande_achat_id = bca.id
            WHERE fa.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['details'] = $this->getFactureDetails($id);
            error_log("AchatModel::getFactureById facture found with " . count($result['details']) . " details");
        } else {
            error_log("AchatModel::getFactureById facture with id=$id not found");
        }

        return $result ?: null;
    }

    public function createFacture($data)
    {
        $this->validateFactureData($data);

        error_log("AchatModel::createFacture called with data: " . json_encode($data));

        $query = "
            INSERT INTO facture_achat (
                numero_facture_fournisseur, date_facture, bon_commande_achat_id,
                entreprise_fournisseur_id, entreprise_filiale_id, statut_id,
                montant_ttc, reste_a_payer, depot_reception_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['numero_facture_fournisseur'],
            $data['date_facture'] ?? date('Y-m-d'),
            $data['bon_commande_achat_id'] ?? null,
            $data['entreprise_fournisseur_id'],
            $data['entreprise_filiale_id'],
            $data['statut_id'],
            $data['montant_ttc'] ?? 0,
            $data['reste_a_payer'] ?? $data['montant_ttc'] ?? 0,
            $data['depot_reception_id'] ?? null
        ]);

        $newId = $this->db->lastInsertId();

        if (isset($data['details']) && is_array($data['details'])) {
            $this->createFactureDetails($newId, $data['details']);
        }

        error_log("AchatModel::createFacture created facture with id=$newId");
        return (int)$newId;
    }

    

    public function updateFacture($id, $data)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        $this->validateFactureData($data, false);

        error_log("AchatModel::updateFacture called with id=$id, data: " . json_encode($data));

        $query = "
            UPDATE facture_achat SET
                numero_facture_fournisseur = ?,
                date_facture = ?,
                bon_commande_achat_id = ?,
                entreprise_fournisseur_id = ?,
                entreprise_filiale_id = ?,
                statut_id = ?,
                montant_ttc = ?,
                reste_a_payer = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([
            $data['numero_facture_fournisseur'],
            $data['date_facture'] ?? date('Y-m-d'),
            $data['bon_commande_achat_id'] ?? null,
            $data['entreprise_fournisseur_id'],
            $data['entreprise_filiale_id'],
            $data['statut_id'],
            $data['montant_ttc'] ?? 0,
            $data['reste_a_payer'] ?? $data['montant_ttc'] ?? 0,
            $id
        ]);

        if ($result && $stmt->rowCount() > 0) {
            if (isset($data['details']) && is_array($data['details'])) {
                $this->updateFactureDetails($id, $data['details']);
            }

            error_log("AchatModel::updateFacture updated facture with id=$id");
            return true;
        }

        error_log("AchatModel::updateFacture no facture updated with id=$id");
        return false;
    }

    public function deleteFacture($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("AchatModel::deleteFacture called with id=$id");

        $query = "DELETE FROM facture_achat WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("AchatModel::deleteFacture deleted facture with id=$id");
            return true;
        }

        error_log("AchatModel::deleteFacture no facture deleted with id=$id");
        return false;
    }

    // =================== MÉTHODES UTILITAIRES ===================


    private function getBonCommandeDetails($bcId)
    {
        $query = "
            SELECT bcd.*, a.reference, a.designation
            FROM bon_commande_achat_details bcd
            INNER JOIN article a ON bcd.article_id = a.id
            WHERE bcd.bon_commande_achat_id = ?
            ORDER BY bcd.id
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$bcId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getFactureDetails($factureId)
    {
        $query = "
            SELECT fad.*, a.reference, a.designation
            FROM facture_achat_details fad
            INNER JOIN article a ON fad.article_id = a.id
            WHERE fad.facture_achat_id = ?
            ORDER BY fad.id
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$factureId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    private function createBonCommandeDetails($bcId, $details)
    {
        foreach ($details as $detail) {
            $query = "INSERT INTO bon_commande_achat_details (bon_commande_achat_id, article_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$bcId, $detail['article_id'], $detail['quantite'], $detail['prix_unitaire']]);
        }
    }

    private function createFactureDetails($factureId, $details)
    {
        foreach ($details as $detail) {
            $query = "INSERT INTO facture_achat_details (facture_achat_id, article_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$factureId, $detail['article_id'], $detail['quantite'], $detail['prix_unitaire']]);
        }
    }


    private function updateBonCommandeDetails($bcId, $details)
    {
        $query = "DELETE FROM bon_commande_achat_details WHERE bon_commande_achat_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$bcId]);
        $this->createBonCommandeDetails($bcId, $details);
    }

    private function updateFactureDetails($factureId, $details)
    {
        $query = "DELETE FROM facture_achat_details WHERE facture_achat_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$factureId]);
        $this->createFactureDetails($factureId, $details);
    }

    private function copyProformaDetailsToBonCommande($proformaId, $bcId)
    {
        // Use dedicated ProformaFournisseurModel to retrieve details (no duplication).
        $details = Flight::proformaFournisseurModel()->getDetails($proformaId);
        foreach ($details as $detail) {
            $query = "INSERT INTO bon_commande_achat_details (bon_commande_achat_id, article_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$bcId, $detail['article_id'], $detail['quantite'], $detail['prix_unitaire']]);
        }
    }

    private function copyBonCommandeDetailsToFacture($bcId, $factureId,$numeroFactureFournisseur,$personnelId,$depotId)
    {
        $details = $this->getBonCommandeDetails($bcId);
        foreach ($details as $detail) {
            $query = "INSERT INTO facture_achat_details (facture_achat_id, article_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$factureId, $detail['article_id'], $detail['quantite'], $detail['prix_unitaire']]);
            $this->createMouvementAchatFromLigne($detail['article_id'],$personnelId, $detail['quantite'], $numeroFactureFournisseur,$depotId,$detail['prix_unitaire']);
        }
    }

    private function createMouvementAchatFromLigne($article,$personnelId, $quantite, $numeroFacture,$depot,$prixUnitaire)
    {
        error_log("AchatModel::createMouvementAchatFromLigne called for article=$article, personnelId=$personnelId, quantite=$quantite, numeroFacture=$numeroFacture");
        
        // Données pour le mouvement de stock
        $mouvementData = [
            'type_mouvement' => 'ACHAT',
            'article_id' => $article,
            'personnel_id' => $personnelId,
            'quantite_entree' => $quantite,
            'reference_document' => $numeroFacture,
            'prix_unitaire' => $prixUnitaire
        ];

        // Ajouter depot_id si spécifié dans la ligne
        if (isset($depot)) {
            $mouvementData['depot_id'] = $depot;
        }

        // Créer le mouvement de stock via StockModel
        // Note: Assurez-vous que StockModel est disponible via Flight::stockModel()
        error_log("Checking if StockModel is available...");
        $stockModel = Flight::stockModel();
        error_log("StockModel object: " . get_class($stockModel));
        $stockModel = Flight::stockModel()->createMouvement($mouvementData);
        error_log("AchatModel::createMouvementAchatFromLigne calling StockModel::createMouvement with data: " . json_encode($mouvementData));
        return $stockModel;
    }

    private function generateNumeroBonCommande()
    {
        $date = date('Ym');
        $query = "SELECT COUNT(*) as count FROM bon_commande_achat WHERE numero_bc LIKE ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$date . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $numero = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
        return $date . $numero;
    }

    private function generateNumeroFactureAchat()
    {
        $date = date('Ym');
        $query = "SELECT COUNT(*) as count FROM facture_achat WHERE numero_facture_fournisseur LIKE ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$date . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $numero = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
        return 'FA' . $date . $numero;
    }

    private function getStatutIdByCode($code)
    {
        $query = "SELECT id FROM statut WHERE code = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : 1;
    }

    private function validateProformaData($data, $isCreation = true)
    {
        if ($isCreation || isset($data['numero_proforma'])) {
            if (empty($data['numero_proforma'])) {
                throw new InvalidArgumentException("Le numéro de proforma est obligatoire");
            }
        }

        if (($isCreation || isset($data['entreprise_fournisseur_id'])) && (!isset($data['entreprise_fournisseur_id']) || $data['entreprise_fournisseur_id'] <= 0)) {
            throw new InvalidArgumentException("Le fournisseur est obligatoire");
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

    private function validateBonCommandeData($data, $isCreation = true)
    {
        if ($isCreation || isset($data['numero_bc'])) {
            if (empty($data['numero_bc'])) {
                throw new InvalidArgumentException("Le numéro BC est obligatoire");
            }
        }

        if (($isCreation || isset($data['entreprise_fournisseur_id'])) && (!isset($data['entreprise_fournisseur_id']) || $data['entreprise_fournisseur_id'] <= 0)) {
            throw new InvalidArgumentException("Le fournisseur est obligatoire");
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

    private function validateFactureData($data, $isCreation = true)
    {
        if ($isCreation || isset($data['numero_facture_fournisseur'])) {
            if (empty($data['numero_facture_fournisseur'])) {
                throw new InvalidArgumentException("Le numéro de facture est obligatoire");
            }
        }

        if (($isCreation || isset($data['entreprise_fournisseur_id'])) && (!isset($data['entreprise_fournisseur_id']) || $data['entreprise_fournisseur_id'] <= 0)) {
            throw new InvalidArgumentException("Le fournisseur est obligatoire");
        }

        if (($isCreation || isset($data['entreprise_filiale_id'])) && (!isset($data['entreprise_filiale_id']) || $data['entreprise_filiale_id'] <= 0)) {
            throw new InvalidArgumentException("La filiale est obligatoire");
        }

        if (($isCreation || isset($data['statut_id'])) && (!isset($data['statut_id']) || $data['statut_id'] <= 0)) {
            throw new InvalidArgumentException("Le statut est obligatoire");
        }
    }
}