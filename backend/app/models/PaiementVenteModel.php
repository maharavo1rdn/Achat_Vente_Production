<?php

namespace app\models;

use Flight;
use InvalidArgumentException;
use PDO;
use Exception;

class PaiementVenteModel
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
                pv.id,
                pv.numero_recu,
                pv.date_paiement,
                pv.facture_vente_id,
                pv.caisse_mouvement_id,
                pv.montant,
                pv.statut_id,
                s.libelle as statut_libelle,
                fv.numero_facture
            FROM paiement_vente pv
            LEFT JOIN statut s ON pv.statut_id = s.id
            LEFT JOIN facture_vente fv ON pv.facture_vente_id = fv.id
            WHERE 1=1
        ";

        $params = [];
        if (isset($filters['facture_vente_id'])) {
            $query .= " AND pv.facture_vente_id = ?";
            $params[] = (int)$filters['facture_vente_id'];
        }

        if (isset($filters['statut_id'])) {
            $query .= " AND pv.statut_id = ?";
            $params[] = (int)$filters['statut_id'];
        } elseif (isset($filters['paiement_statut_id'])) {
            // backward compatibility
            $query .= " AND pv.statut_id = ?";
            $params[] = (int)$filters['paiement_statut_id'];
        }

        $query .= " ORDER BY pv.date_paiement DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        if ($id <= 0) throw new InvalidArgumentException("L'ID doit être un entier positif");

        $query = "
            SELECT pv.*, s.libelle as statut_libelle, fv.numero_facture
            FROM paiement_vente pv
            LEFT JOIN statut s ON pv.statut_id = s.id
            LEFT JOIN facture_vente fv ON pv.facture_vente_id = fv.id
            WHERE pv.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $paiement = $stmt->fetch(PDO::FETCH_ASSOC);

        return $paiement ?: null;
    }


    public function create($data)
    {
        if (!isset($data['facture_vente_id']) || (int)$data['facture_vente_id'] <= 0) {
            throw new InvalidArgumentException('facture_vente_id est requis');
        }

        $this->db->beginTransaction();
        try {
            if (isset($data['payments']) && is_array($data['payments'])) {
                $ids = [];
                foreach ($data['payments'] as $p) {
                    $merged = array_merge($data, (array)$p);
                    $merged['facture_vente_id'] = (int)$merged['facture_vente_id'];
                    $ids[] = $this->insertPaymentRow($merged);
                }
                $this->db->commit();
                return $ids;
            }

            $id = $this->insertPaymentRow($data);
            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function insertPaymentRow($data)
    {
        if (!isset($data['facture_vente_id']) || (int)$data['facture_vente_id'] <= 0) {
            throw new InvalidArgumentException('facture_vente_id est requis');
        }

        if (!isset($data['montant']) || $data['montant'] === '') {
            $stmt = $this->db->prepare('SELECT reste_a_payer FROM facture_vente WHERE id = ?');
            $stmt->execute([(int)$data['facture_vente_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $data['montant'] = $row ? (float)$row['reste_a_payer'] : 0;
        }

        if (empty($data['numero_recu'])) {
            $data['numero_recu'] = 'REC-' . date('Ym') . substr(md5(uniqid()), 0, 6);
        }

        $query = "INSERT INTO paiement_vente (
            numero_recu, statut_id, date_paiement, facture_vente_id, caisse_mouvement_id, montant, mode_paiement_id, reference_externe
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $statutId = $data['statut_id'] ?? 1;

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['numero_recu'],
            (int)$statutId,
            $data['date_paiement'] ?? date('Y-m-d'),
            (int)$data['facture_vente_id'],
            isset($data['caisse_mouvement_id']) ? (int)$data['caisse_mouvement_id'] : null,
            $data['montant'],
            isset($data['mode_paiement_id']) ? (int)$data['mode_paiement_id'] : null,
            $data['reference_externe'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function validate($paiementId, $userId = null, $caisseId = null, $personnelId = null, $libelle = null)
    {
        if ($paiementId <= 0)
            throw new InvalidArgumentException('paiementId invalide');

        if ($userId === null)
            throw new InvalidArgumentException('user_id requis pour valider le paiement');
        $personnel = Flight::personnelModel()->getById((int)$userId);

        if (!$personnel)
            throw new InvalidArgumentException('Utilisateur introuvable');

        $niveau = isset($personnel['niveau_acces']) ? (int)$personnel['niveau_acces'] : 0;
        if ($niveau < 5)
            throw new Exception('Permission refusée: niveau d\'accès insuffisant');

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM paiement_vente WHERE id = ?');
            $stmt->execute([$paiementId]);
            $paiement = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$paiement) throw new InvalidArgumentException('Paiement introuvable');

            $montant = (float)$paiement['montant'];

            $mouvementId = null;
            if ($caisseId !== null || $personnelId !== null) {
                if (empty($caisseId) || empty($personnelId)) {
                    throw new InvalidArgumentException('caisse_id et personnel_id sont requis pour créer le mouvement de caisse');
                }

                $mouvementData = [
                    'libelle_operation' => $libelle ?? "Paiement #{$paiementId}",
                    'montant' => $montant,
                    'personnel_id' => (int)$personnelId
                ];

                $mouvementId = Flight::caisseModel()->createEntree((int)$caisseId, $mouvementData);

                try {
                    $stmt = $this->db->prepare('UPDATE paiement_vente SET caisse_mouvement_id = ? WHERE id = ?');
                    $stmt->execute([$mouvementId, $paiementId]);
                } catch (Exception $e) {
                }
            }

            $this->applyPayment($paiementId, $montant);

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

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT facture_vente_id, montant FROM paiement_vente WHERE id = ?');
            $stmt->execute([$paiementId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new InvalidArgumentException('Paiement introuvable');

            $applyMontant = ($montant === null) ? (float)$row['montant'] : (float)$montant;
            $factureId = (int)$row['facture_vente_id'];

            $stmt = $this->db->prepare('UPDATE facture_vente SET reste_a_payer = GREATEST(0, reste_a_payer - ?) WHERE id = ?');
            $stmt->execute([$applyMontant, $factureId]);

            $stmt = $this->db->prepare('UPDATE paiement_vente SET statut_id = ?, date_paiement = COALESCE(date_paiement, CURRENT_DATE) WHERE id = ?');
            $stmt->execute([3, $paiementId]);

            $stmt = $this->db->prepare('SELECT reste_a_payer FROM facture_vente WHERE id = ?');
            $stmt->execute([$factureId]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            $reste = $r ? (float)$r['reste_a_payer'] : 0;

            if ($reste <= 0) {
                $stmt = $this->db->prepare('UPDATE facture_vente SET statut_facture_id = ? WHERE id = ?');
                $stmt->execute([3, $factureId]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
