<?php

namespace app\models;

use InvalidArgumentException;
use PDO;

class EntrepriseModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    public function getAll($filters = [])
    {
        error_log("EntrepriseModel::getAll called with filters: " . json_encode($filters));

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
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['type_entreprise'])) {
            $query .= " AND type_entreprise = ?";
            $params[] = $filters['type_entreprise'];
        }

        if (isset($filters['est_actif'])) {
            $query .= " AND est_actif = ?";
            $params[] = $filters['est_actif'] === 'true';
        }

        if (isset($filters['search'])) {
            $query .= " AND (nom LIKE ? OR email LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $query .= " ORDER BY nom";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("EntrepriseModel::getAll retrieved " . count($results) . " entreprises");

        return $results;
    }

    public function getById($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("EntrepriseModel::getById called with id=$id");

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
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            error_log("EntrepriseModel::getById entreprise with id=$id not found");
            return null;
        }

        error_log("EntrepriseModel::getById entreprise found: " . $result['nom']);
        return $result;
    }

    public function create($data)
    {
        $this->validateEntrepriseData($data);

        error_log("EntrepriseModel::create called with data: " . json_encode($data));

        $query = "
            INSERT INTO entreprise (
                nom, type_entreprise, matricule_fiscal, adresse,
                telephone, email, est_actif
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['nom'],
            $data['type_entreprise'],
            $data['matricule_fiscal'] ?? null,
            $data['adresse'] ?? null,
            $data['telephone'] ?? null,
            $data['email'] ?? null,
            $data['est_actif'] ?? true
        ]);

        $newId = $this->db->lastInsertId();
        error_log("EntrepriseModel::create created entreprise with id=$newId");

        return (int)$newId;
    }

    public function update($id, $data)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        $this->validateEntrepriseData($data, false);

        error_log("EntrepriseModel::update called with id=$id, data: " . json_encode($data));

        $query = "
            UPDATE entreprise SET
                nom = ?,
                type_entreprise = ?,
                matricule_fiscal = ?,
                adresse = ?,
                telephone = ?,
                email = ?,
                est_actif = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([
            $data['nom'],
            $data['type_entreprise'],
            $data['matricule_fiscal'] ?? null,
            $data['adresse'] ?? null,
            $data['telephone'] ?? null,
            $data['email'] ?? null,
            $data['est_actif'] ?? true,
            $id
        ]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("EntrepriseModel::update updated entreprise with id=$id");
            return true;
        }

        error_log("EntrepriseModel::update no entreprise updated with id=$id");
        return false;
    }

    public function delete($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("EntrepriseModel::delete called with id=$id");

        $query = "DELETE FROM entreprise WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("EntrepriseModel::delete deleted entreprise with id=$id");
            return true;
        }

        error_log("EntrepriseModel::delete no entreprise deleted with id=$id");
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

    public function getClients(): array
    {
        error_log("EntrepriseModel::getClients called");
        return $this->getByType('CLIENT');
    }

    public function getFournisseurs(): array
    {
        error_log("EntrepriseModel::getFournisseurs called");
        return $this->getByType('FOURNISSEUR');
    }

    public function getFiliales(): array
    {
        error_log("EntrepriseModel::getFiliales called");
        return $this->getByType('INTERNE');
    }

    private function validateEntrepriseData(array $data, bool $isCreation = true): void
    {
        if ($isCreation || isset($data['nom'])) {
            if (empty($data['nom'])) {
                throw new InvalidArgumentException("Le nom est obligatoire");
            }
            if (strlen($data['nom']) > 200) {
                throw new InvalidArgumentException("Le nom ne peut pas dépasser 200 caractères");
            }
        }

        if ($isCreation || isset($data['type_entreprise'])) {
            $typesValides = ['CLIENT', 'FOURNISSEUR', 'INTERNE', 'PARTENAIRE'];
            if (!in_array($data['type_entreprise'], $typesValides)) {
                throw new InvalidArgumentException("Type d'entreprise invalide");
            }
        }

        if (isset($data['matricule_fiscal']) && strlen($data['matricule_fiscal']) > 100) {
            throw new InvalidArgumentException("Le matricule fiscal ne peut pas dépasser 100 caractères");
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
    }
}