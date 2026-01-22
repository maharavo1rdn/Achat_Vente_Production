<?php

namespace app\models;

use InvalidArgumentException;
use PDO;

class SiteModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    public function getAll($filters = [])
    {
        error_log("SiteModel::getAll called with filters: " . json_encode($filters));

        $query = "
            SELECT
                s.id,
                s.nom,
                s.adresse,
                s.telephone,
                s.email,
                s.entreprise_id,
                e.nom AS entreprise_nom,
                s.est_actif,
                s.date_creation
            FROM site s
            LEFT JOIN entreprise e ON e.id = s.entreprise_id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['entreprise_id'])) {
            $query .= " AND s.entreprise_id = ?";
            $params[] = (int)$filters['entreprise_id'];
        }

        if (isset($filters['est_actif'])) {
            $val = filter_var($filters['est_actif'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($val !== null) {
                $query .= " AND s.est_actif = ?";
                $params[] = $val;
            }
        }

        $stringFields = ['s.nom', 's.adresse', 's.email', 's.telephone', 'e.nom'];
        foreach ($stringFields as $field) {
            $key = str_replace(['s.', 'e.'], '', $field);
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query .= " AND UPPER(" . $field . ") LIKE ?";
                $params[] = '%' . mb_strtoupper($filters[$key], 'UTF-8') . '%';
            }
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $s = '%' . mb_strtoupper($filters['search'], 'UTF-8') . '%';
            $query .= " AND (UPPER(s.nom) LIKE ? OR UPPER(s.adresse) LIKE ? OR UPPER(s.email) LIKE ? OR UPPER(s.telephone) LIKE ? OR UPPER(e.nom) LIKE ?)";
            for ($i = 0; $i < 5; $i++) $params[] = $s;
        }

        $query .= " ORDER BY s.nom";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("SiteModel::getAll retrieved " . count($results) . " sites");

        return $results;
    }

    public function getById($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("SiteModel::getById called with id=$id");

        $query = "
            SELECT
                s.id,
                s.nom,
                s.adresse,
                s.telephone,
                s.email,
                s.entreprise_id,
                e.nom AS entreprise_nom,
                s.est_actif,
                s.date_creation
            FROM site s
            LEFT JOIN entreprise e ON e.id = s.entreprise_id
            WHERE s.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            error_log("SiteModel::getById site with id=$id not found");
            return null;
        }

        error_log("SiteModel::getById site found: " . $result['nom']);
        return $result;
    }

    public function create($data)
    {
        $this->validateSiteData($data);

        error_log("SiteModel::create called with data: " . json_encode($data));

        $query = "
            INSERT INTO site (
                nom, adresse, telephone, email, entreprise_id, est_actif
            ) VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['nom'],
            $data['adresse'] ?? null,
            $data['telephone'] ?? null,
            $data['email'] ?? null,
            $data['entreprise_id'] ?? null,
            $data['est_actif'] ?? true
        ]);

        $newId = $this->db->lastInsertId();
        error_log("SiteModel::create created site with id=$newId");

        return (int)$newId;
    }

    public function update($id, $data)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        $this->validateSiteData($data, false);

        error_log("SiteModel::update called with id=$id, data: " . json_encode($data));

        $query = "
            UPDATE site SET
                nom = ?,
                adresse = ?,
                telephone = ?,
                email = ?,
                entreprise_id = ?,
                est_actif = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([
            $data['nom'],
            $data['adresse'] ?? null,
            $data['telephone'] ?? null,
            $data['email'] ?? null,
            $data['entreprise_id'] ?? null,
            $data['est_actif'] ?? true,
            $id
        ]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("SiteModel::update updated site with id=$id");
            return true;
        }

        error_log("SiteModel::update no site updated with id=$id");
        return false;
    }

    public function delete($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("SiteModel::delete called with id=$id");

        $query = "DELETE FROM site WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("SiteModel::delete deleted site with id=$id");
            return true;
        }

        error_log("SiteModel::delete no site deleted with id=$id");
        return false;
    }

    public function getByType($type)
    {
        $typesValides = ['CLIENT', 'FOURNISSEUR', 'INTERNE', 'PARTENAIRE'];
        if (!in_array($type, $typesValides)) {
            throw new InvalidArgumentException("Type d'entreprise invalide");
        }

        error_log("EntrepriseModel::getByType called with type=$type");

        $query = "
            SELECT
                id,
                nom,
                type_entreprise,
                matricule_fiscal,
                adresse,
                telephone,
                email,
                est_actif,
                date_creation
            FROM entreprise
            WHERE type_entreprise = ?
            ORDER BY nom
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$type]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("EntrepriseModel::getByType retrieved " . count($results) . " entreprises");

        return $results;
    }

    public function getClients()
    {
        error_log("EntrepriseModel::getClients called");
        return $this->getByType('CLIENT');
    }

    public function getFournisseurs()
    {
        error_log("EntrepriseModel::getFournisseurs called");
        return $this->getByType('FOURNISSEUR');
    }

    public function getByEntreprise($entrepriseId)
    {
        if (!is_numeric($entrepriseId) || (int)$entrepriseId <= 0) {
            throw new InvalidArgumentException("L'ID de l'entreprise doit être un entier positif");
        }

        $query = "
            SELECT s.id, s.nom, s.adresse, s.telephone, s.email, s.entreprise_id, e.nom AS entreprise_nom, s.est_actif, s.date_creation
            FROM site s
            LEFT JOIN entreprise e ON e.id = s.entreprise_id
            WHERE s.entreprise_id = ?
            ORDER BY s.nom
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([(int)$entrepriseId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function entrepriseExists($entrepriseId)
    {
        if ($entrepriseId === null) return true;
        if (!is_numeric($entrepriseId) || (int)$entrepriseId <= 0) return false;

        $stmt = $this->db->prepare("SELECT 1 FROM entreprise WHERE id = ?");
        $stmt->execute([(int)$entrepriseId]);
        return (bool)$stmt->fetchColumn();
    }

    public function setActive($id, $active)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        $stmt = $this->db->prepare("UPDATE site SET est_actif = ? WHERE id = ?");
        $result = $stmt->execute([$active ? true : false, $id]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("SiteModel::setActive updated site id=$id to " . ($active ? 'active' : 'inactive'));
            return true;
        }

        return false;
    }

    private function validateSiteData($data, $isCreation = true)
    {
        if ($isCreation || isset($data['nom'])) {
            if (empty($data['nom'])) {
                throw new InvalidArgumentException("Le nom du site est obligatoire");
            }
            if (strlen($data['nom']) > 200) {
                throw new InvalidArgumentException("Le nom du site ne peut pas dépasser 200 caractères");
            }
        }

        if (isset($data['adresse']) && strlen($data['adresse']) > 200) {
            throw new InvalidArgumentException("L'adresse ne peut pas dépasser 200 caractères");
        }

        if (isset($data['telephone']) && strlen($data['telephone']) > 50) {
            throw new InvalidArgumentException("Le téléphone ne peut pas dépasser 50 caractères");
        }

        if (isset($data['email'])) {
            if (strlen($data['email']) > 100) {
                throw new InvalidArgumentException("L'email ne peut pas dépasser 100 caractères");
            }
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Format d'email invalide");
            }
        }

        if (isset($data['entreprise_id'])) {
            if (!$this->entrepriseExists($data['entreprise_id'])) {
                throw new InvalidArgumentException("Entreprise invalide ou introuvable");
            }
        }
    }
}