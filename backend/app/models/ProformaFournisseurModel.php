<?php

namespace app\models;

use InvalidArgumentException;
use PDO;
use Exception;

class ProformaFournisseurModel
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
                pf.id,
                pf.numero_proforma,
                pf.date_emission,
                pf.date_validite,
                pf.entreprise_fournisseur_id,
                ef.nom as fournisseur,
                pf.entreprise_filiale_id,
                efi.nom as filiale,
                pf.personnel_id,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom,
                pf.statut_id,
                s.libelle as statut,
                s.libelle as statut_libelle,
                pf.montant_ttc,
                pf.proforma_demande_achat_id
            FROM proforma_fournisseur pf
            LEFT JOIN entreprise ef ON pf.entreprise_fournisseur_id = ef.id
            LEFT JOIN entreprise efi ON pf.entreprise_filiale_id = efi.id
            LEFT JOIN personnel p ON pf.personnel_id = p.id
            LEFT JOIN statut s ON pf.statut_id = s.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['fournisseur_id'])) {
            $query .= " AND pf.entreprise_fournisseur_id = ?";
            $params[] = (int)$filters['fournisseur_id'];
        }

        if (isset($filters['filiale_id'])) {
            $query .= " AND pf.entreprise_filiale_id = ?";
            $params[] = (int)$filters['filiale_id'];
        }

        if (isset($filters['statut_id'])) {
            $query .= " AND pf.statut_id = ?";
            $params[] = (int)$filters['statut_id'];
        }

        if (isset($filters['date_debut']) && $filters['date_debut'] !== '') {
            $query .= " AND pf.date_emission >= ?";
            $params[] = $filters['date_debut'];
        }

        if (isset($filters['date_fin']) && $filters['date_fin'] !== '') {
            $query .= " AND pf.date_emission <= ?";
            $params[] = $filters['date_fin'];
        }

        $query .= " ORDER BY pf.date_emission DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        if ($id <= 0) throw new InvalidArgumentException("L'ID doit être un entier positif");

        $query = "
            SELECT
                pf.*, 
                ef.nom as fournisseur_nom,
                efi.nom as filiale_nom,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom,
                s.libelle as statut_libelle
            FROM proforma_fournisseur pf
            LEFT JOIN entreprise ef ON pf.entreprise_fournisseur_id = ef.id
            LEFT JOIN entreprise efi ON pf.entreprise_filiale_id = efi.id
            LEFT JOIN personnel p ON pf.personnel_id = p.id
            LEFT JOIN statut s ON pf.statut_id = s.id
            WHERE pf.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['details'] = $this->getDetails($id);
        }

        return $result ?: null;
    }

    public function create($data)
    {
        // Générer automatiquement un numéro si non fourni (backend contrôle)
        if (empty($data['numero_proforma'])) {
            $data['numero_proforma'] = 'PF' . date('Ym') . substr(md5(uniqid()), 0, 6);
        }

        $this->validateProformaData($data);

        $this->db->beginTransaction();

        try {
            $query = "
                INSERT INTO proforma_fournisseur (
                    numero_proforma, date_emission, date_validite, entreprise_fournisseur_id,
                    entreprise_filiale_id, personnel_id, statut_id, montant_ttc, proforma_demande_achat_id,depot_cible_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,?)
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['numero_proforma'],
                $data['date_emission'] ?? date('Y-m-d'),
                $data['date_validite'] ?? null,
                (int)$data['entreprise_fournisseur_id'],
                (int)$data['entreprise_filiale_id'],
                (int)$data['personnel_id'],
                (int)$data['statut_id'],
                $data['montant_ttc'] ?? 0,
                isset($data['proforma_demande_achat_id']) ? (int)$data['proforma_demande_achat_id'] : null,
                isset($data['depot_cible_id']) ? (int)$data['depot_cible_id'] : 1
            ]);
            $id = (int)$this->db->lastInsertId();

            if (isset($data['details']) && is_array($data['details'])) {
                $this->insertDetails($id, $data['details']);
            }

            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update($id, $data)
    {
        if ($id <= 0) throw new InvalidArgumentException("L'ID doit être un entier positif");
        $this->validateProformaData($data, false);

        try {
            $this->db->beginTransaction();

            $query = "
                UPDATE proforma_fournisseur SET
                    numero_proforma = ?,
                    date_emission = ?,
                    date_validite = ?,
                    entreprise_fournisseur_id = ?,
                    entreprise_filiale_id = ?,
                    personnel_id = ?,
                    statut_id = ?,
                    montant_ttc = ?
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['numero_proforma'],
                $data['date_emission'] ?? date('Y-m-d'),
                $data['date_validite'] ?? null,
                (int)$data['entreprise_fournisseur_id'],
                (int)$data['entreprise_filiale_id'],
                (int)$data['personnel_id'],
                (int)$data['statut_id'],
                $data['montant_ttc'] ?? 0,
                $id
            ]);

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

        $stmt = $this->db->prepare("DELETE FROM proforma_fournisseur WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function getDetails($proformaId)
    {
        $query = "
            SELECT
                pfd.id,
                pfd.proforma_fournisseur_id,
                pfd.article_id,
                a.reference AS article_reference,
                a.designation AS article_designation,
                pfd.quantite,
                pfd.prix_unitaire
            FROM proforma_fournisseur_details pfd
            LEFT JOIN article a ON a.id = pfd.article_id
            WHERE pfd.proforma_fournisseur_id = ?
            ORDER BY pfd.id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$proformaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function insertDetails($proformaId, $details)
    {
        $query = "
            INSERT INTO proforma_fournisseur_details (proforma_fournisseur_id, article_id, quantite, prix_unitaire)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);
        foreach ($details as $d) {
            if (!isset($d['article_id']) || !isset($d['quantite'])) {
                throw new InvalidArgumentException('Chaque détail doit contenir article_id et quantite');
            }

            $stmt->execute([
                $proformaId,
                (int)$d['article_id'],
                $d['quantite'],
                $d['prix_unitaire'] ?? 0
            ]);
        }
    }

    private function updateDetails($proformaId, $details)
    {
        $stmt = $this->db->prepare("DELETE FROM proforma_fournisseur_details WHERE proforma_fournisseur_id = ?");
        $stmt->execute([$proformaId]);

        if (count($details) > 0) {
            $this->insertDetails($proformaId, $details);
        }
    } 

    public function updateStatut($id, $statutId)
    {
        if ($id <= 0) throw new InvalidArgumentException("L'ID doit être un entier positif");

        $stmt = $this->db->prepare("UPDATE proforma_fournisseur SET statut_id = ? WHERE id = ?");
        $stmt->execute([(int)$statutId, $id]);
        return $stmt->rowCount() > 0;
    }

    private function validateProformaData($data, $isCreation = true)
    {
        // Ne pas exiger le numéro lors de la création (il peut être généré côté serveur)
        if (isset($data['numero_proforma'])) {
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
}
