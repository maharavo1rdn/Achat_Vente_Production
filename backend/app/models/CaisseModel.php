<?php

namespace app\models;

use InvalidArgumentException;
use PDO;
use Exception;

class CaisseModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    public function getAllCaisses($filters = [])
    {
        error_log("CaisseModel::getAllCaisses called with filters: " . json_encode($filters));

        $query = "
            SELECT
                c.id,
                c.code_caisse,
                c.libelle,
                c.solde_actuel,
                c.entreprise_id,
                e.nom as entreprise_nom
            FROM caisse c
            INNER JOIN entreprise e ON c.entreprise_id = e.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['entreprise_id'])) {
            $query .= " AND c.entreprise_id = ?";
            $params[] = $filters['entreprise_id'];
        }

        $query .= " ORDER BY c.libelle";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("CaisseModel::getAllCaisses retrieved " . count($results) . " caisses");

        return $results;
    }

    public function getCaisseById($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("CaisseModel::getCaisseById called with id=$id");

        $query = "
            SELECT
                c.id,
                c.code_caisse,
                c.libelle,
                c.solde_actuel,
                c.entreprise_id,
                e.nom as entreprise_nom
            FROM caisse c
            INNER JOIN entreprise e ON c.entreprise_id = e.id
            WHERE c.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            error_log("CaisseModel::getCaisseById caisse found");
        } else {
            error_log("CaisseModel::getCaisseById caisse with id=$id not found");
        }

        return $result ?: null;
    }

    public function createCaisse($data)
    {
        $this->validateCaisseData($data);

        error_log("CaisseModel::createCaisse called with data: " . json_encode($data));

        $query = "
            INSERT INTO caisse (
                code_caisse, libelle, solde_actuel, entreprise_id
            ) VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['code_caisse'],
            $data['libelle'],
            $data['solde_actuel'] ?? 0,
            $data['entreprise_id']
        ]);

        $newId = $this->db->lastInsertId();
        error_log("CaisseModel::createCaisse created caisse with id=$newId");

        return (int)$newId;
    }

    public function updateCaisse($id, $data)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        $this->validateCaisseData($data, false);

        error_log("CaisseModel::updateCaisse called with id=$id, data: " . json_encode($data));

        $query = "
            UPDATE caisse SET
                code_caisse = ?,
                libelle = ?,
                solde_actuel = ?,
                entreprise_id = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([
            $data['code_caisse'],
            $data['libelle'],
            $data['solde_actuel'] ?? 0,
            $data['entreprise_id'],
            $id
        ]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("CaisseModel::updateCaisse updated caisse with id=$id");
            return true;
        }

        error_log("CaisseModel::updateCaisse no caisse updated with id=$id");
        return false;
    }

    public function deleteCaisse($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("CaisseModel::deleteCaisse called with id=$id");

        $query = "DELETE FROM caisse WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("CaisseModel::deleteCaisse deleted caisse with id=$id");
            return true;
        }

        error_log("CaisseModel::deleteCaisse no caisse deleted with id=$id");
        return false;
    }

    public function getSolde($caisseId)
    {
        if ($caisseId <= 0) {
            throw new InvalidArgumentException("L'ID de caisse doit être un entier positif");
        }

        error_log("CaisseModel::getSolde called with caisseId=$caisseId");

        $query = "SELECT solde_actuel FROM caisse WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$caisseId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            error_log("CaisseModel::getSolde solde found: " . $result['solde_actuel']);
            return (float)$result['solde_actuel'];
        }

        error_log("CaisseModel::getSolde no caisse found with id=$caisseId");
        return null;
    }

    // =================== MOUVEMENTS DE CAISSE ===================

    public function getMouvements($filters = [])
    {
        error_log("CaisseModel::getMouvements called with filters: " . json_encode($filters));

        $query = "
            SELECT
                cm.id,
                cm.date_mouvement,
                cm.libelle_operation,
                cm.montant_entree,
                cm.montant_sortie,
                cm.solde_avant,
                cm.solde_apres,
                cm.caisse_id,
                cm.personnel_id,
                c.libelle as caisse_libelle,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom
            FROM caisse_mouvement cm
            INNER JOIN caisse c ON cm.caisse_id = c.id
            INNER JOIN personnel p ON cm.personnel_id = p.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['caisse_id'])) {
            $query .= " AND cm.caisse_id = ?";
            $params[] = $filters['caisse_id'];
        }

        if (isset($filters['personnel_id'])) {
            $query .= " AND cm.personnel_id = ?";
            $params[] = $filters['personnel_id'];
        }

        if (isset($filters['date_debut'])) {
            $query .= " AND cm.date_mouvement >= ?";
            $params[] = $filters['date_debut'];
        }

        if (isset($filters['date_fin'])) {
            $query .= " AND cm.date_mouvement <= ?";
            $params[] = $filters['date_fin'];
        }

        // Optional filter: type (ENTREE | SORTIE) - server-side helper
        if (isset($filters['type'])) {
            if (strtoupper($filters['type']) === 'ENTREE') {
                $query .= " AND cm.montant_entree > 0";
            } elseif (strtoupper($filters['type']) === 'SORTIE') {
                $query .= " AND cm.montant_sortie > 0";
            }
        }

        $query .= " ORDER BY cm.date_mouvement DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("CaisseModel::getMouvements retrieved " . count($results) . " mouvements");

        return $results;
    }

    public function getMouvementById($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("CaisseModel::getMouvementById called with id=$id");

        $query = "
            SELECT
                cm.id,
                cm.date_mouvement,
                cm.libelle_operation,
                cm.montant_entree,
                cm.montant_sortie,
                cm.solde_avant,
                cm.solde_apres,
                cm.caisse_id,
                cm.personnel_id,
                c.libelle as caisse_libelle,
                c.code_caisse,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom
            FROM caisse_mouvement cm
            INNER JOIN caisse c ON cm.caisse_id = c.id
            INNER JOIN personnel p ON cm.personnel_id = p.id
            WHERE cm.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            error_log("CaisseModel::getMouvementById mouvement found");
        } else {
            error_log("CaisseModel::getMouvementById mouvement with id=$id not found");
        }

        return $result ?: null;
    }

    public function updateMouvement($id, $data)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("CaisseModel::updateMouvement called with id=$id, data: " . json_encode($data));

        // Get current mouvement
        $current = $this->getMouvementById($id);
        if (!$current) {
            throw new InvalidArgumentException("Mouvement non trouvé");
        }

        if ($current['solde_avant'] == $current['solde_apres'] && $current['montant_entree'] == 0 && $current['montant_sortie'] == 0) {
            // Mouvement vide, on peut le modifier
        }

        $query = "
            UPDATE caisse_mouvement SET
                libelle_operation = ?,
                montant_entree = ?,
                montant_sortie = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([
            $data['libelle_operation'] ?? $current['libelle_operation'],
            $data['montant_entree'] ?? $current['montant_entree'],
            $data['montant_sortie'] ?? $current['montant_sortie'],
            $id
        ]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("CaisseModel::updateMouvement updated mouvement with id=$id");
            return true;
        }

        error_log("CaisseModel::updateMouvement no mouvement updated with id=$id");
        return false;
    }

    public function createEntree($caisseId, $data, $statutId = 3)
    {
        if ($caisseId <= 0) {
            throw new InvalidArgumentException("L'ID de caisse doit être un entier positif");
        }

        $this->validateMouvementData($data);

        error_log("CaisseModel::createEntree called with caisseId=$caisseId, data: " . json_encode($data) . ", statutId=" . $statutId);

        $soldeAvant = $this->getSolde($caisseId) ?? 0;

        $soldeApres = $statutId == 2 ? $soldeAvant : $soldeAvant + $data['montant'];

        // Allow client to specify date_mouvement (optional)
        if (isset($data['date_mouvement'])) {
            $query = "
                INSERT INTO caisse_mouvement (
                    date_mouvement, libelle_operation, montant_entree, montant_sortie,
                    solde_avant, solde_apres, caisse_id, personnel_id
                ) VALUES (?, ?, ?, 0, ?, ?, ?, ?)
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['date_mouvement'],
                $data['libelle_operation'],
                $data['montant'],
                $soldeAvant,
                $soldeApres,
                $caisseId,
                $data['personnel_id']
            ]);
        } else {
            $query = "
                INSERT INTO caisse_mouvement (
                    libelle_operation, montant_entree, montant_sortie,
                    solde_avant, solde_apres, caisse_id, personnel_id
                ) VALUES (?, ?, 0, ?, ?, ?, ?)
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['libelle_operation'],
                $data['montant'],
                $soldeAvant,
                $soldeApres,
                $caisseId,
                $data['personnel_id']
            ]);
        }

        $mouvementId = $this->db->lastInsertId();

        // Mettre à jour le solde de la caisse uniquement si le mouvement est validé
        if ($statutId != 2) {
            $this->updateCaisseSolde($caisseId, $soldeApres);
        }

        error_log("CaisseModel::createEntree created entree with id=$mouvementId");
        return (int)$mouvementId;
    }

    public function createSortie($caisseId, $data, $statutId = 3)
    {
        if ($caisseId <= 0) {
            throw new InvalidArgumentException("L'ID de caisse doit être un entier positif");
        }

        $this->validateMouvementData($data);

        error_log("CaisseModel::createSortie called with caisseId=$caisseId, data: " . json_encode($data) . ", statutId=" . $statutId);

        $soldeAvant = $this->getSolde($caisseId) ?? 0;

        $soldeApres = $statutId == 2 ? $soldeAvant : $soldeAvant - $data['montant'];

        if ($soldeApres < 0) {
            throw new InvalidArgumentException("Solde insuffisant pour cette sortie");
        }

        // Allow client to specify date_mouvement (optional)
        if (isset($data['date_mouvement'])) {
            $query = "
                INSERT INTO caisse_mouvement (
                    date_mouvement, libelle_operation, montant_entree, montant_sortie,
                    solde_avant, solde_apres, caisse_id, personnel_id
                ) VALUES (?, ?, 0, ?, ?, ?, ?, ?)
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['date_mouvement'],
                $data['libelle_operation'],
                $data['montant'],
                $soldeAvant,
                $soldeApres,
                $caisseId,
                $data['personnel_id']
            ]);
        } else {
            $query = "
                INSERT INTO caisse_mouvement (
                    libelle_operation, montant_entree, montant_sortie,
                    solde_avant, solde_apres, caisse_id, personnel_id
                ) VALUES (?, 0, ?, ?, ?, ?, ?)
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['libelle_operation'],
                $data['montant'],
                $soldeAvant,
                $soldeApres,
                $caisseId,
                $data['personnel_id']
            ]);
        }

        $mouvementId = $this->db->lastInsertId();

        if ($statutId != 2) {
            $this->updateCaisseSolde($caisseId, $soldeApres);
        }

        error_log("CaisseModel::createSortie created sortie with id=$mouvementId");
        return (int)$mouvementId;
    }

    public function createMouvement($caisseId, $data, $statutId = 2) {
        $this->db->beginTransaction();

        try {
            $montantEntree = floatval($data['montant_entree'] ?? 0);
            $montantSortie = floatval($data['montant_sortie'] ?? 0);

            if ($montantEntree <= 0 && $montantSortie <= 0) {
                throw new InvalidArgumentException("Au moins un montant (entrée ou sortie) doit être supérieur à 0");
            }

            error_log("CaisseModel::createMouvement called with caisseId=$caisseId, entree=$montantEntree, sortie=$montantSortie, statutId=$statutId");

            $caisse = $this->getCaisseById($caisseId);
            if (!$caisse) {
                throw new InvalidArgumentException("Caisse introuvable");
            }

            $soldeAvant = floatval($caisse['solde_actuel']);
            $soldeApres = $soldeAvant + $montantEntree - $montantSortie;

            $query = "
                INSERT INTO caisse_mouvement (
                    libelle_operation, montant_entree, montant_sortie,
                    solde_avant, solde_apres, caisse_id, personnel_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['libelle_operation'],
                $montantEntree,
                $montantSortie,
                $soldeAvant,
                $soldeApres,
                $caisseId,
                $data['personnel_id']
            ]);

            $mouvementId = $this->db->lastInsertId();

            // Si le mouvement n'est pas EN_ATTENTE, mettre à jour le solde de la caisse
            if ($statutId != 2) {
                $this->updateCaisseSolde($caisseId, $soldeApres);
            }

            $this->db->commit();
            error_log("CaisseModel::createMouvement created movement with id=$mouvementId");
            return (int)$mouvementId;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("CaisseModel::createMouvement error: " . $e->getMessage());
            throw $e;
        }
    }

    // =================== PAIEMENTS ===================

    public function getPaiementsVente($filters = [])
    {
        error_log("CaisseModel::getPaiementsVente called with filters: " . json_encode($filters));

        $query = "
            SELECT
                pv.id,
                pv.facture_vente_id,
                pv.caisse_mouvement_id,
                pv.montant,
                fv.numero_facture,
                cm.libelle_operation,
                cm.date_mouvement,
                c.libelle as caisse_libelle
            FROM paiement_vente pv
            INNER JOIN facture_vente fv ON pv.facture_vente_id = fv.id
            INNER JOIN caisse_mouvement cm ON pv.caisse_mouvement_id = cm.id
            INNER JOIN caisse c ON cm.caisse_id = c.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['facture_vente_id'])) {
            $query .= " AND pv.facture_vente_id = ?";
            $params[] = $filters['facture_vente_id'];
        }

        if (isset($filters['caisse_id'])) {
            $query .= " AND cm.caisse_id = ?";
            $params[] = $filters['caisse_id'];
        }

        $query .= " ORDER BY cm.date_mouvement DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("CaisseModel::getPaiementsVente retrieved " . count($results) . " paiements");

        return $results;
    }

    public function getPaiementsAchat($filters = [])
    {
        error_log("CaisseModel::getPaiementsAchat called with filters: " . json_encode($filters));

        $query = "
            SELECT
                pa.id,
                pa.facture_achat_id,
                pa.caisse_mouvement_id,
                pa.montant,
                fa.numero_facture_fournisseur,
                cm.libelle_operation,
                cm.date_mouvement,
                c.libelle as caisse_libelle
            FROM paiement_achat pa
            INNER JOIN facture_achat fa ON pa.facture_achat_id = fa.id
            INNER JOIN caisse_mouvement cm ON pa.caisse_mouvement_id = cm.id
            INNER JOIN caisse c ON cm.caisse_id = c.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['facture_achat_id'])) {
            $query .= " AND pa.facture_achat_id = ?";
            $params[] = $filters['facture_achat_id'];
        }

        if (isset($filters['caisse_id'])) {
            $query .= " AND cm.caisse_id = ?";
            $params[] = $filters['caisse_id'];
        }

        $query .= " ORDER BY cm.date_mouvement DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("CaisseModel::getPaiementsAchat retrieved " . count($results) . " paiements");

        return $results;
    }

    public function enregistrerPaiementVente($factureVenteId, $caisseMouvementId, $montant)
    {
        if ($factureVenteId <= 0 || $caisseMouvementId <= 0) {
            throw new InvalidArgumentException("Les IDs doivent être des entiers positifs");
        }

        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant doit être positif");
        }

        error_log("CaisseModel::enregistrerPaiementVente called with factureVenteId=$factureVenteId, caisseMouvementId=$caisseMouvementId, montant=$montant");

        $query = "
            INSERT INTO paiement_vente (
                facture_vente_id, caisse_mouvement_id, montant, mode_paiement_id, statut_id
            ) VALUES (?, ?, ?, 1, 1)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$factureVenteId, $caisseMouvementId, $montant]);

        $paiementId = $this->db->lastInsertId();

        // Mettre à jour le reste à payer de la facture
        $this->updateResteAPayerVente($factureVenteId, $montant);

        error_log("CaisseModel::enregistrerPaiementVente created paiement with id=$paiementId");
        return (int)$paiementId;
    }

    public function enregistrerPaiementAchat($factureAchatId, $caisseMouvementId, $montant)
    {
        if ($factureAchatId <= 0 || $caisseMouvementId <= 0) {
            throw new InvalidArgumentException("Les IDs doivent être des entiers positifs");
        }

        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant doit être positif");
        }

        error_log("CaisseModel::enregistrerPaiementAchat called with factureAchatId=$factureAchatId, caisseMouvementId=$caisseMouvementId, montant=$montant");

        $query = "
            INSERT INTO paiement_achat (
                facture_achat_id, caisse_mouvement_id, montant, mode_paiement_id, statut_id
            ) VALUES (?, ?, ?, 1, 1)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$factureAchatId, $caisseMouvementId, $montant]);

        $paiementId = $this->db->lastInsertId();

        // Mettre à jour le reste à payer de la facture
        $this->updateResteAPayerAchat($factureAchatId, $montant);

        error_log("CaisseModel::enregistrerPaiementAchat created paiement with id=$paiementId");
        return (int)$paiementId;
    }

    // =================== MÉTHODES UTILITAIRES ===================

    private function updateCaisseSolde($caisseId, $nouveauSolde)
    {
        error_log("CaisseModel::updateCaisseSolde called with caisseId=$caisseId, nouveauSolde=$nouveauSolde");

        $query = "UPDATE caisse SET solde_actuel = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$nouveauSolde, $caisseId]);

        error_log("CaisseModel::updateCaisseSolde solde updated");
    }

    private function updateResteAPayerVente($factureVenteId, $montantPaye)
    {
        error_log("CaisseModel::updateResteAPayerVente called with factureVenteId=$factureVenteId, montantPaye=$montantPaye");

        $query = "UPDATE facture_vente SET reste_a_payer = GREATEST(0, reste_a_payer - ?) WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$montantPaye, $factureVenteId]);

        error_log("CaisseModel::updateResteAPayerVente reste à payer updated");
    }

    private function updateResteAPayerAchat($factureAchatId, $montantPaye)
    {
        error_log("CaisseModel::updateResteAPayerAchat called with factureAchatId=$factureAchatId, montantPaye=$montantPaye");

        $query = "UPDATE facture_achat SET reste_a_payer = GREATEST(0, reste_a_payer - ?) WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$montantPaye, $factureAchatId]);

        error_log("CaisseModel::updateResteAPayerAchat reste à payer updated");
    }

    private function validateCaisseData($data, $isCreation = true)
    {
        if ($isCreation || isset($data['code_caisse'])) {
            if (empty($data['code_caisse'])) {
                throw new InvalidArgumentException("Le code caisse est obligatoire");
            }
            if (strlen($data['code_caisse']) > 50) {
                throw new InvalidArgumentException("Le code caisse ne peut pas dépasser 50 caractères");
            }
        }

        if ($isCreation || isset($data['libelle'])) {
            if (empty($data['libelle'])) {
                throw new InvalidArgumentException("Le libellé est obligatoire");
            }
            if (strlen($data['libelle']) > 100) {
                throw new InvalidArgumentException("Le libellé ne peut pas dépasser 100 caractères");
            }
        }

        if (($isCreation || isset($data['entreprise_id'])) && (!isset($data['entreprise_id']) || $data['entreprise_id'] <= 0)) {
            throw new InvalidArgumentException("L'entreprise est obligatoire");
        }

        if (isset($data['solde_actuel']) && !is_numeric($data['solde_actuel'])) {
            throw new InvalidArgumentException("Le solde actuel doit être un nombre");
        }
    }

    private function validateMouvementData($data)
    {
        if (empty($data['libelle_operation'])) {
            throw new InvalidArgumentException("Le libellé d'opération est obligatoire");
        }

        if (!isset($data['montant']) || $data['montant'] <= 0) {
            throw new InvalidArgumentException("Le montant doit être positif");
        }
    }

    /**
     * Finalize a mouvement and optionally update its montant in the same atomic transaction.
     * If $newMontant is provided, it will update either montant_entree or montant_sortie depending on the existing columns
     * or on $isSortie flag (if provided).
     */
    public function finalizeMouvement($mouvementId, $statutId = 3, $newMontant = null, $isSortie = null)
    {
        if ($mouvementId <= 0) throw new InvalidArgumentException('mouvementId invalide');

        $startedTransaction = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $startedTransaction = true;
        }

        try {
            $stmt = $this->db->prepare('SELECT * FROM caisse_mouvement WHERE id = ? FOR UPDATE');
            $stmt->execute([$mouvementId]);
            $m = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$m) throw new InvalidArgumentException('Mouvement introuvable');

            $montantEntree = (float)$m['montant_entree'];
            $montantSortie = (float)$m['montant_sortie'];
            $soldeAvant = (float)$m['solde_avant'];
            $caisseId = (int)$m['caisse_id'];

            // If caller provided a new montant, decide whether to set montant_entree or montant_sortie
            if ($newMontant !== null) {
                // If isSortie explicitly provided, use it. Otherwise infer based on which column currently has a non-zero value.
                if ($isSortie === null) {
                    $isSortie = ($montantEntree == 0 && $montantSortie > 0) || ($montantSortie > 0 && $montantEntree == 0);
                }

                if ($isSortie) {
                    $montantSortie = (float)$newMontant;
                } else {
                    $montantEntree = (float)$newMontant;
                }
            }

            $soldeApres = $soldeAvant + $montantEntree - $montantSortie;

            $stmt = $this->db->prepare('UPDATE caisse_mouvement SET solde_apres = ?, montant_entree = ?, montant_sortie = ? WHERE id = ?');
            $stmt->execute([$soldeApres, $montantEntree, $montantSortie, $mouvementId]);

            $this->updateCaisseSolde($caisseId, $soldeApres);

            if ($startedTransaction) {
                $this->db->commit();
            }
            error_log("CaisseModel::finalizeMouvement finalized mouvement $mouvementId with statut $statutId");
            return true;
        } catch (Exception $e) {
            if ($startedTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
