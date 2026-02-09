<?php

namespace app\models;

use Flight;
use InvalidArgumentException;
use PDO;
use Exception;

class PaiementAchatModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    public function getAll($filters = [])
    {
        $query = "
            SELECT
                pa.id,
                pa.numero_paiement as numero_recu,
                pa.date_paiement,
                pa.facture_achat_id,
                pa.caisse_mouvement_id,
                pa.montant,
                pa.statut_id,
                s.libelle as statut_libelle,
                fa.numero_facture_fournisseur as numero_facture,
                fa.montant_ttc as facture_montant_ttc,
                fa.reste_a_payer as facture_reste_a_payer,
                ef.nom as fournisseur_nom,
                mp.libelle AS mode_paiement_libelle,
                c.libelle AS caisse_libelle,
                c.code_caisse AS caisse_code
            FROM paiement_achat pa
            LEFT JOIN statut s ON pa.statut_id = s.id
            LEFT JOIN facture_achat fa ON pa.facture_achat_id = fa.id
            LEFT JOIN entreprise ef ON fa.entreprise_fournisseur_id = ef.id
            LEFT JOIN mode_paiement mp ON pa.mode_paiement_id = mp.id
            LEFT JOIN caisse_mouvement cm ON pa.caisse_mouvement_id = cm.id
            LEFT JOIN caisse c ON cm.caisse_id = c.id
            WHERE 1=1
        ";

        $params = [];
        if (isset($filters['facture_achat_id'])) {
            $query .= " AND pa.facture_achat_id = ?";
            $params[] = (int)$filters['facture_achat_id'];
        }

        if (isset($filters['statut_id'])) {
            $query .= " AND pa.statut_id = ?";
            $params[] = (int)$filters['statut_id'];
        } elseif (isset($filters['paiement_statut_id'])) {
            $query .= " AND pa.statut_id = ?";
            $params[] = (int)$filters['paiement_statut_id'];
        }

        // Optional date filters (accepts YYYY-MM-DD or full datetime)
        if (isset($filters['date_debut']) && $filters['date_debut'] !== '') {
            $start = $filters['date_debut'];
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start .= ' 00:00:00';
            $query .= " AND pa.date_paiement >= ?";
            $params[] = $start;
        }

        if (isset($filters['date_fin']) && $filters['date_fin'] !== '') {
            $end = $filters['date_fin'];
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) $end .= ' 23:59:59';
            $query .= " AND pa.date_paiement <= ?";
            $params[] = $end;
        }

        $query .= " ORDER BY pa.date_paiement DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        if ($id <= 0) throw new InvalidArgumentException("L'ID doit être un entier positif");

        $query = "
            SELECT pa.*, pa.numero_paiement AS numero_recu, s.libelle as statut_libelle, fa.numero_facture_fournisseur, fa.montant_ttc AS facture_montant_ttc, fa.reste_a_payer AS facture_reste_a_payer, ef.nom AS fournisseur_nom, mp.libelle AS mode_paiement_libelle, c.libelle AS caisse_libelle, c.code_caisse AS caisse_code
            FROM paiement_achat pa
            LEFT JOIN statut s ON pa.statut_id = s.id
            LEFT JOIN facture_achat fa ON pa.facture_achat_id = fa.id
            LEFT JOIN entreprise ef ON fa.entreprise_fournisseur_id = ef.id
            LEFT JOIN mode_paiement mp ON pa.mode_paiement_id = mp.id
            LEFT JOIN caisse_mouvement cm ON pa.caisse_mouvement_id = cm.id
            LEFT JOIN caisse c ON cm.caisse_id = c.id
            WHERE pa.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $paiement = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$paiement) return null;

        if (!empty($paiement['caisse_mouvement_id'])) {
            $stmt2 = $this->db->prepare('SELECT cm.*, c.libelle AS caisse_libelle, p.nom AS personnel_nom, p.prenom AS personnel_prenom FROM caisse_mouvement cm LEFT JOIN caisse c ON cm.caisse_id = c.id LEFT JOIN personnel p ON cm.personnel_id = p.id WHERE cm.id = ?');
            $stmt2->execute([(int)$paiement['caisse_mouvement_id']]);
            $m = $stmt2->fetch(PDO::FETCH_ASSOC);
            // Keep name parts separate; front-end will concatenate if needed
            $paiement['caisse_mouvement'] = $m ?: null;
        } else {
            $paiement['caisse_mouvement'] = null;
        }

        return $paiement;
    }

    public function update($id, $data)
    {
        if ($id <= 0) throw new InvalidArgumentException('paiementId invalide');

        $stmt = $this->db->prepare('SELECT statut_id FROM paiement_achat WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new InvalidArgumentException('Paiement introuvable');

        if ((int)$row['statut_id'] === 3) {
            throw new InvalidArgumentException('Paiement déjà validé; modification interdite');
        }

        $allowed = ['montant', 'mode_paiement_id', 'reference_externe', 'numero_paiement', 'date_paiement'];
        $sets = [];
        $params = [];
        foreach ($allowed as $f) {
            if (isset($data[$f])) {
                $sets[] = "$f = ?";
                $params[] = $data[$f];
            }
        }

        if (empty($sets)) return false;

        $params[] = $id;
        $query = 'UPDATE paiement_achat SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        if (isset($data['montant'])) {
            try {
                $stmt2 = $this->db->prepare('SELECT caisse_mouvement_id FROM paiement_achat WHERE id = ?');
                $stmt2->execute([$id]);
                $r = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($r && !empty($r['caisse_mouvement_id'])) {
                    $cmId = (int)$r['caisse_mouvement_id'];
                    $stmtU = $this->db->prepare('UPDATE caisse_mouvement SET montant_sortie = ? WHERE id = ?');
                    $stmtU->execute([$data['montant'], $cmId]);
                }
            } catch (Exception $e) {
                error_log('Warning: failed to sync mouvement montant after paiement update: ' . $e->getMessage());
            }
        }

        return true;
    }

    public function create($data)
    {
        if (is_object($data))
            $data = json_decode(json_encode($data), true);

        if (is_array($data) && array_values($data) === $data && isset($data[0]) && !isset($data['facture_achat_id']))
            throw new InvalidArgumentException('Payload invalide: facture_achat_id requis');

        if (!isset($data['facture_achat_id']) || (int)$data['facture_achat_id'] <= 0)
            throw new InvalidArgumentException('facture_achat_id est requis');

        $this->db->beginTransaction();
        try {
            if (isset($data['payments']) && is_array($data['payments'])) {
                $ids = [];
                foreach ($data['payments'] as $p) {
                    $merged = array_merge($data, (array)$p);
                    $merged['facture_achat_id'] = (int)$merged['facture_achat_id'];
                    $ids[] = $this->insertPaymentRow($merged);
                }
                $this->db->commit();
                return $ids;
            }

            $id = $this->insertPaymentRow($data);
            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->db->rollBack();
            throw $e;
        }
    }

    private function insertPaymentRow($data)
    {
        if (!isset($data['facture_achat_id']) || (int)$data['facture_achat_id'] <= 0) {
            throw new InvalidArgumentException('facture_achat_id est requis');
        }

        if (!isset($data['montant']) || $data['montant'] === '') {
            $stmt = $this->db->prepare('SELECT reste_a_payer FROM facture_achat WHERE id = ?');
            $stmt->execute([(int)$data['facture_achat_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $data['montant'] = $row ? (float)$row['reste_a_payer'] : 0;
        }

        if (empty($data['numero_recu'])) {
            $data['numero_recu'] = 'REC-' . date('Ym') . substr(md5(uniqid()), 0, 6);
        }

        if (isset($data['caisse_id']) && isset($data['personnel_id']) && !empty($data['caisse_id']) && !empty($data['personnel_id'])) {
            $stmtInv = $this->db->prepare('SELECT numero_facture_fournisseur FROM facture_achat WHERE id = ?');
            $stmtInv->execute([(int)$data['facture_achat_id']]);
            $inv = $stmtInv->fetch(PDO::FETCH_ASSOC);
            $invoiceNum = $inv ? $inv['numero_facture_fournisseur'] : null;
            $defaultLibelle = $invoiceNum != null ? ("Paiement facture achat #{$invoiceNum}") : ("Paiement #" . $data['numero_recu']);
            $mouvementData = [
                'libelle_operation' =>  $invoiceNum != null ? $defaultLibelle : $data['reference_externe'],
                'montant' => $data['montant'],
                'personnel_id' => (int)$data['personnel_id']
            ];

            $mouvementId = Flight::caisseModel()->createSortie((int)$data['caisse_id'], $mouvementData, 2);
            $data['caisse_mouvement_id'] = $mouvementId;
            $data['statut_id'] = 2;
        }

        $query = "INSERT INTO paiement_achat (
            numero_paiement, statut_id, date_paiement, facture_achat_id, caisse_mouvement_id, montant, mode_paiement_id, reference_externe
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $statutId = $data['statut_id'] ?? 1;

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['numero_recu'],
            (int)$statutId,
            $data['date_paiement'] ?? date('Y-m-d'),
            (int)$data['facture_achat_id'],
            isset($data['caisse_mouvement_id']) ? (int)$data['caisse_mouvement_id'] : null,
            $data['montant'],
            isset($data['mode_paiement_id']) ? (int)$data['mode_paiement_id'] : null,
            $data['reference_externe'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function validate($paiementId, $userId = null, $caisseId = null, $personnelId = null, $libelle = null)
    {
        if ($paiementId <= 0) throw new InvalidArgumentException('paiementId invalide');

        if ($userId === null) throw new InvalidArgumentException('user_id requis pour valider le paiement');
        $personnel = Flight::personnelModel()->getById((int)$userId);

        if (!$personnel) throw new InvalidArgumentException('Utilisateur introuvable');

        $niveau = isset($personnel['niveau_acces']) ? (int)$personnel['niveau_acces'] : 0;
        if ($niveau < 5) throw new Exception('Permission refusée: niveau d\'accès insuffisant');

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM paiement_achat WHERE id = ?');
            $stmt->execute([$paiementId]);
            $paiement = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$paiement) throw new InvalidArgumentException('Paiement introuvable');

            $montant = (float)$paiement['montant'];

            $mouvementId = null;
            if (!empty($paiement['caisse_mouvement_id'])) {
                $mouvementId = (int)$paiement['caisse_mouvement_id'];
                Flight::caisseModel()->finalizeMouvement($mouvementId, 3, $montant, true);
            } elseif ($caisseId !== null || $personnelId !== null) {
                if (empty($caisseId) || empty($personnelId)) {
                    throw new InvalidArgumentException('caisse_id et personnel_id sont requis pour créer le mouvement de caisse');
                }

                if ($libelle === null) {
                    $stmtInv = $this->db->prepare('SELECT numero_facture_fournisseur FROM facture_achat WHERE id = ?');
                    $stmtInv->execute([(int)$paiement['facture_achat_id']]);
                    $inv = $stmtInv->fetch(PDO::FETCH_ASSOC);
                    $invoiceNum = $inv ? $inv['numero_facture_fournisseur'] : null;
                    $libelle = $invoiceNum ? ("Paiement facture achat #" . $invoiceNum) : ("Paiement achat #{$paiementId}");
                }

                $mouvementData = [
                    'libelle_operation' => $libelle,
                    'montant' => $montant,
                    'personnel_id' => (int)$personnelId
                ];

                $mouvementId = Flight::caisseModel()->createSortie((int)$caisseId, $mouvementData, 3);

                try {
                    $stmt = $this->db->prepare('UPDATE paiement_achat SET caisse_mouvement_id = ? WHERE id = ?');
                    $stmt->execute([$mouvementId, $paiementId]);
                } catch (Exception $e) {
                }
            }

            $this->applyPayment($paiementId, $montant);

            // Créer les mouvements de stock après validation du paiement
            $factureId = (int)$paiement['facture_achat_id'];
            try {
                $nbMouvements = Flight::achatModel()->creerMouvementsStockFacture($factureId);
                error_log("PaiementAchatModel::validate created $nbMouvements stock movements for facture $factureId");
            } catch (Exception $e) {
                error_log("PaiementAchatModel::validate - Warning: Failed to create stock movements: " . $e->getMessage());
                // Ne pas bloquer le paiement si les mouvements existent déjà
            }

            $this->db->commit();
            return ['success' => true, 'mouvement_id' => $mouvementId];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function applyPayment($paiementId, $montant = null)
    {
        if ($paiementId <= 0) throw new InvalidArgumentException('paiementId invalide');

        $startedTransaction = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $startedTransaction = true;
        }
        try {
            $stmt = $this->db->prepare('SELECT facture_achat_id, montant FROM paiement_achat WHERE id = ?');
            $stmt->execute([$paiementId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new InvalidArgumentException('Paiement introuvable');

            $applyMontant = ($montant === null) ? (float)$row['montant'] : (float)$montant;
            $factureId = (int)$row['facture_achat_id'];

            $stmt = $this->db->prepare('UPDATE facture_achat SET reste_a_payer = GREATEST(0, reste_a_payer - ?) WHERE id = ?');
            $stmt->execute([$applyMontant, $factureId]);

            $stmt = $this->db->prepare('UPDATE paiement_achat SET statut_id = ?, date_paiement = COALESCE(date_paiement, CURRENT_DATE) WHERE id = ?');
            $stmt->execute([3, $paiementId]);

            $stmt = $this->db->prepare('SELECT reste_a_payer FROM facture_achat WHERE id = ?');
            $stmt->execute([$factureId]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            $reste = $r ? (float)$r['reste_a_payer'] : 0;

            if ($reste <= 0) {
                $stmt = $this->db->prepare('UPDATE facture_achat SET statut_id = ? WHERE id = ?');
                $stmt->execute([3, $factureId]);
            }

            if ($startedTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($startedTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
