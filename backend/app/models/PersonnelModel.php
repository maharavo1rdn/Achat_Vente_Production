<?php

namespace app\models;

use InvalidArgumentException;
use PDO;

class PersonnelModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    public function getAll($filters = [])
    {
        error_log("PersonnelModel::getAll called with filters: " . json_encode($filters));

        $query = "
            SELECT
                p.id,
                p.code_employe,
                p.nom,
                p.prenom,
                p.email,
                p.telephone,
                p.est_actif,
                p.personnel_role_id,
                p.entreprise_id,
                pr.libelle as role_libelle,
                pr.niveau_acces,
                e.nom as entreprise_nom
            FROM personnel p
            INNER JOIN personnel_role pr ON p.personnel_role_id = pr.id
            LEFT JOIN entreprise e ON p.entreprise_id = e.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['est_actif'])) {
            $query .= " AND p.est_actif = ?";
            $params[] = $filters['est_actif'] === 'true';
        }

        if (isset($filters['role_id'])) {
            $query .= " AND p.personnel_role_id = ?";
            $params[] = $filters['role_id'];
        }

        if (isset($filters['entreprise_id'])) {
            $query .= " AND p.entreprise_id = ?";
            $params[] = $filters['entreprise_id'];
        }

        if (isset($filters['search'])) {
            $query .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR p.email LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $query .= " ORDER BY p.nom, p.prenom";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("PersonnelModel::getAll retrieved " . count($results) . " personnels");

        return $results;
    }

    public function getById($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("PersonnelModel::getById called with id=$id");

        $query = "
            SELECT
                p.id,
                p.code_employe,
                p.nom,
                p.prenom,
                p.email,
                p.telephone,
                p.est_actif,
                p.personnel_role_id,
                p.entreprise_id,
                pr.libelle as role_libelle,
                pr.niveau_acces,
                e.nom as entreprise_nom
            FROM personnel p
            INNER JOIN personnel_role pr ON p.personnel_role_id = pr.id
            LEFT JOIN entreprise e ON p.entreprise_id = e.id
            WHERE p.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            error_log("PersonnelModel::getById personnel with id=$id not found");
            return null;
        }

        error_log("PersonnelModel::getById personnel found: " . $result['nom'] . ' ' . $result['prenom']);
        return $result;
    }

    public function create($data)
    {
        $this->validatePersonnelData($data);

        error_log("PersonnelModel::create called with data: " . json_encode($data));

        $query = "
            INSERT INTO personnel (
                code_employe, nom, prenom, email, mot_de_passe_hash,
                telephone, est_actif, personnel_role_id, entreprise_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['code_employe'] ?? null,
            $data['nom'],
            $data['prenom'] ?? null,
            $data['email'],
            $data['mot_de_passe_hash'] ?? password_hash('default123', PASSWORD_DEFAULT),
            $data['telephone'] ?? null,
            $data['est_actif'] ?? true,
            $data['personnel_role_id'],
            $data['entreprise_id'] ?? null
        ]);

        $newId = $this->db->lastInsertId();
        error_log("PersonnelModel::create created personnel with id=$newId");

        return (int)$newId;
    }

    public function update($id, $data)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        $this->validatePersonnelData($data, false);

        error_log("PersonnelModel::update called with id=$id, data: " . json_encode($data));

        $query = "
            UPDATE personnel SET
                code_employe = ?,
                nom = ?,
                prenom = ?,
                email = ?,
                telephone = ?,
                est_actif = ?,
                personnel_role_id = ?,
                entreprise_id = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([
            $data['code_employe'] ?? null,
            $data['nom'],
            $data['prenom'] ?? null,
            $data['email'],
            $data['telephone'] ?? null,
            $data['est_actif'] ?? true,
            $data['personnel_role_id'],
            $data['entreprise_id'] ?? null,
            $id
        ]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("PersonnelModel::update updated personnel with id=$id");
            return true;
        }

        error_log("PersonnelModel::update no personnel updated with id=$id");
        return false;
    }

    public function delete($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("PersonnelModel::delete called with id=$id");

        $query = "DELETE FROM personnel WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("PersonnelModel::delete deleted personnel with id=$id");
            return true;
        }

        error_log("PersonnelModel::delete no personnel deleted with id=$id");
        return false;
    }

    public function resetPassword($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("PersonnelModel::resetPassword called with id=$id");

        $newPasswordHash = password_hash('default123', PASSWORD_DEFAULT);

        $query = "UPDATE personnel SET mot_de_passe_hash = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$newPasswordHash, $id]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("PersonnelModel::resetPassword reset password for personnel with id=$id");
            return true;
        }

        error_log("PersonnelModel::resetPassword no personnel updated with id=$id");
        return false;
    }

    public function getByRole($roleId)
    {
        if ($roleId <= 0) {
            throw new InvalidArgumentException("L'ID de rôle doit être un entier positif");
        }

        error_log("PersonnelModel::getByRole called with roleId=$roleId");

        $query = "
            SELECT
                p.id,
                p.code_employe,
                p.nom,
                p.prenom,
                p.email,
                p.telephone,
                p.est_actif,
                p.personnel_role_id,
                p.entreprise_id,
                pr.libelle as role_libelle,
                pr.niveau_acces,
                e.nom as entreprise_nom
            FROM personnel p
            INNER JOIN personnel_role pr ON p.personnel_role_id = pr.id
            LEFT JOIN entreprise e ON p.entreprise_id = e.id
            WHERE p.personnel_role_id = ?
            ORDER BY p.nom, p.prenom
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$roleId]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("PersonnelModel::getByRole retrieved " . count($results) . " personnels");

        return $results;
    }

    public function getByFiliale($filialeId)
    {
        if ($filialeId <= 0) {
            throw new InvalidArgumentException("L'ID de filiale doit être un entier positif");
        }

        error_log("PersonnelModel::getByFiliale called with filialeId=$filialeId");

        $query = "
            SELECT
                p.id,
                p.code_employe,
                p.nom,
                p.prenom,
                p.email,
                p.telephone,
                p.est_actif,
                p.personnel_role_id,
                p.entreprise_id,
                pr.libelle as role_libelle,
                pr.niveau_acces,
                e.nom as entreprise_nom
            FROM personnel p
            INNER JOIN personnel_role pr ON p.personnel_role_id = pr.id
            INNER JOIN entreprise e ON p.entreprise_id = e.id
            WHERE p.entreprise_id = ?
            ORDER BY p.nom, p.prenom
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$filialeId]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("PersonnelModel::getByFiliale retrieved " . count($results) . " personnels");

        return $results;
    }

    public function authenticate($email, $password)
    {
        if (empty($email) || empty($password)) {
            throw new InvalidArgumentException("Email et mot de passe sont obligatoires");
        }

        error_log("PersonnelModel::authenticate called with email=$email");

        $query = "
            SELECT
                p.id,
                p.code_employe,
                p.nom,
                p.prenom,
                p.email,
                p.telephone,
                p.est_actif,
                p.personnel_role_id,
                p.entreprise_id,
                p.mot_de_passe_hash,
                pr.libelle as role_libelle,
                pr.niveau_acces,
                e.nom as entreprise_nom
            FROM personnel p
            INNER JOIN personnel_role pr ON p.personnel_role_id = pr.id
            LEFT JOIN entreprise e ON p.entreprise_id = e.id
            WHERE p.email = ? AND p.est_actif = true
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$email]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && password_verify($password, $result['mot_de_passe_hash'])) {
            unset($result['mot_de_passe_hash']); // Ne pas retourner le hash
            error_log("PersonnelModel::authenticate authentication successful for " . $result['email']);
            return $result;
        }

        error_log("PersonnelModel::authenticate authentication failed for email=$email");
        return null;
    }

    public function changePassword($id, $oldPassword, $newPassword)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        if (empty($oldPassword) || empty($newPassword)) {
            throw new InvalidArgumentException("Ancien et nouveau mot de passe sont obligatoires");
        }

        if (strlen($newPassword) < 6) {
            throw new InvalidArgumentException("Le nouveau mot de passe doit contenir au moins 6 caractères");
        }

        error_log("PersonnelModel::changePassword called with id=$id");

        // Vérifier l'ancien mot de passe
        $query = "SELECT mot_de_passe_hash FROM personnel WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result || !password_verify($oldPassword, $result['mot_de_passe_hash'])) {
            throw new InvalidArgumentException("Ancien mot de passe incorrect");
        }

        // Mettre à jour avec le nouveau mot de passe
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE personnel SET mot_de_passe_hash = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$newPasswordHash, $id]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("PersonnelModel::changePassword password changed for personnel with id=$id");
            return true;
        }

        error_log("PersonnelModel::changePassword no personnel updated with id=$id");
        return false;
    }

    private function validatePersonnelData(array $data, bool $isCreation = true): void
    {
        if ($isCreation || isset($data['nom'])) {
            if (empty($data['nom'])) {
                throw new InvalidArgumentException("Le nom est obligatoire");
            }
            if (strlen($data['nom']) > 100) {
                throw new InvalidArgumentException("Le nom ne peut pas dépasser 100 caractères");
            }
        }

        if (isset($data['prenom']) && strlen($data['prenom']) > 100) {
            throw new InvalidArgumentException("Le prénom ne peut pas dépasser 100 caractères");
        }

        if ($isCreation || isset($data['email'])) {
            if (empty($data['email'])) {
                throw new InvalidArgumentException("L'email est obligatoire");
            }
            if (strlen($data['email']) > 150) {
                throw new InvalidArgumentException("L'email ne peut pas dépasser 150 caractères");
            }
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Format d'email invalide");
            }
        }

        if (isset($data['code_employe']) && strlen($data['code_employe']) > 20) {
            throw new InvalidArgumentException("Le code employé ne peut pas dépasser 20 caractères");
        }

        if (isset($data['telephone']) && strlen($data['telephone']) > 50) {
            throw new InvalidArgumentException("Le téléphone ne peut pas dépasser 50 caractères");
        }

        if (($isCreation || isset($data['personnel_role_id'])) && (!isset($data['personnel_role_id']) || $data['personnel_role_id'] <= 0)) {
            throw new InvalidArgumentException("Le rôle est obligatoire");
        }
    }
}