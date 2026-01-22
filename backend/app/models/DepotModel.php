<?php

namespace app\models;

use InvalidArgumentException;
use PDO;

class DepotModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    public function getAll($filters = [])
    {
        error_log("DepotModel::getAll called with filters: " . json_encode($filters));

        $query = "
            SELECT
                d.id,
                d.nom,
                d.adresse,
                d.site_id,
                s.nom AS site_nom,
                s.entreprise_id,
                e.nom AS entreprise_nom,
                d.est_actif,
                d.date_creation
            FROM depot d
            LEFT JOIN site s ON s.id = d.site_id
            LEFT JOIN entreprise e ON e.id = s.entreprise_id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['site_id'])) {
            $query .= " AND d.site_id = ?";
            $params[] = (int)$filters['site_id'];
        }

        if (isset($filters['entreprise_id'])) {
            $query .= " AND s.entreprise_id = ?";
            $params[] = (int)$filters['entreprise_id'];
        }

        if (isset($filters['est_actif'])) {
            $val = filter_var($filters['est_actif'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($val !== null) {
                $query .= " AND d.est_actif = ?";
                $params[] = $val;
            }
        }

        $stringFields = ['d.nom', 'd.adresse', 's.nom', 'e.nom'];
        foreach ($stringFields as $field) {
            $key = str_replace(['d.', 's.', 'e.'], '', $field);
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query .= " AND UPPER(" . $field . ") LIKE ?";
                $params[] = '%' . mb_strtoupper($filters[$key], 'UTF-8') . '%';
            }
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $s = '%' . mb_strtoupper($filters['search'], 'UTF-8') . '%';
            $query .= " AND (UPPER(d.nom) LIKE ? OR UPPER(d.adresse) LIKE ? OR UPPER(s.nom) LIKE ? OR UPPER(e.nom) LIKE ? )";
            for ($i = 0; $i < 4; $i++) $params[] = $s;
        }

        $query .= " ORDER BY d.nom";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        if ($id <= 0) throw new InvalidArgumentException("L'ID doit être un entier positif");

        $query = "
            SELECT d.id, d.nom, d.adresse, d.site_id, s.nom AS site_nom, s.entreprise_id, e.nom AS entreprise_nom, d.est_actif, d.date_creation
            FROM depot d
            LEFT JOIN site s ON s.id = d.site_id
            LEFT JOIN entreprise e ON e.id = s.entreprise_id
            WHERE d.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create($data)
    {
        $this->validateDepotData($data);

        $query = "INSERT INTO depot (nom, adresse, site_id, est_actif) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['nom'],
            $data['adresse'] ?? null,
            isset($data['site_id']) ? (int)$data['site_id'] : null,
            $data['est_actif'] ?? true
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        if ($id <= 0) throw new InvalidArgumentException("L'ID doit être un entier positif");
        $this->validateDepotData($data, false);

        $query = "UPDATE depot SET nom = ?, adresse = ?, site_id = ?, est_actif = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['nom'],
            $data['adresse'] ?? null,
            isset($data['site_id']) ? (int)$data['site_id'] : null,
            $data['est_actif'] ?? true,
            $id
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete($id)
    {
        if ($id <= 0) throw new InvalidArgumentException("L'ID doit être un entier positif");
        $stmt = $this->db->prepare("DELETE FROM depot WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function getBySite($siteId)
    {
        if (!is_numeric($siteId) || (int)$siteId <= 0) throw new InvalidArgumentException("L'ID du site doit être un entier positif");
        $stmt = $this->db->prepare("SELECT d.id, d.nom, d.adresse, d.site_id, s.nom AS site_nom, s.entreprise_id, e.nom AS entreprise_nom, d.est_actif, d.date_creation FROM depot d LEFT JOIN site s ON s.id = d.site_id LEFT JOIN entreprise e ON e.id = s.entreprise_id WHERE d.site_id = ? ORDER BY d.nom");
        $stmt->execute([(int)$siteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByEntreprise($entrepriseId)
    {
        if (!is_numeric($entrepriseId) || (int)$entrepriseId <= 0) throw new InvalidArgumentException("L'ID de l'entreprise doit être un entier positif");
        $stmt = $this->db->prepare("SELECT d.id, d.nom, d.adresse, d.site_id, s.nom AS site_nom, s.entreprise_id, e.nom AS entreprise_nom, d.est_actif, d.date_creation FROM depot d LEFT JOIN site s ON s.id = d.site_id LEFT JOIN entreprise e ON e.id = s.entreprise_id WHERE s.entreprise_id = ? ORDER BY d.nom");
        $stmt->execute([(int)$entrepriseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function siteExists($siteId)
    {
        if ($siteId === null) return true;
        if (!is_numeric($siteId) || (int)$siteId <= 0) return false;
        $stmt = $this->db->prepare("SELECT 1 FROM site WHERE id = ?");
        $stmt->execute([(int)$siteId]);
        return (bool)$stmt->fetchColumn();
    }

    public function setActive($id, $active)
    {
        if ($id <= 0) throw new InvalidArgumentException("L'ID doit être un entier positif");
        $stmt = $this->db->prepare("UPDATE depot SET est_actif = ? WHERE id = ?");
        $stmt->execute([$active ? true : false, $id]);
        return $stmt->rowCount() > 0;
    }

    private function validateDepotData($data, $isCreation = true)
    {
        if ($isCreation || isset($data['nom'])) {
            if (empty($data['nom'])) throw new InvalidArgumentException("Le nom du dépôt est obligatoire");
            if (strlen($data['nom']) > 200) throw new InvalidArgumentException("Le nom du dépôt ne peut pas dépasser 200 caractères");
        }

        if (isset($data['adresse']) && strlen($data['adresse']) > 200) throw new InvalidArgumentException("L'adresse ne peut pas dépasser 200 caractères");

        if (isset($data['site_id']) && !$this->siteExists($data['site_id'])) throw new InvalidArgumentException("Site invalide ou introuvable");
    }
}
