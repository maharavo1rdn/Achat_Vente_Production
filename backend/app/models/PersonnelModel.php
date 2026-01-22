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

        $base = "
            FROM personnel p
            INNER JOIN personnel_role pr ON p.personnel_role_id = pr.id
            LEFT JOIN entreprise e ON p.entreprise_id = e.id
            LEFT JOIN site s ON p.site_defaut_id = s.id
            WHERE 1=1
        ";

        $where = '';
        $params = [];

        if (isset($filters['est_actif']) && $filters['est_actif'] !== '') {
            $where .= " AND p.est_actif = ?";
            $params[] = ($filters['est_actif'] === 'true' || $filters['est_actif'] === true) ? true : false;
        }

        if (isset($filters['role_id']) && $filters['role_id'] !== '') {
            $where .= " AND p.personnel_role_id = ?";
            $params[] = (int)$filters['role_id'];
        }

        if (isset($filters['entreprise_id']) && $filters['entreprise_id'] !== '') {
            $where .= " AND p.entreprise_id = ?";
            $params[] = (int)$filters['entreprise_id'];
        }

        if (isset($filters['site_id']) && $filters['site_id'] !== '') {
            $where .= " AND p.site_defaut_id = ?";
            $params[] = (int)$filters['site_id'];
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $where .= " AND (p.nom ILIKE ? OR p.prenom ILIKE ? OR p.email ILIKE ? OR p.code_employe ILIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $countSql = "SELECT COUNT(*) " . $base . $where;
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Pagination parameters
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $perPage = isset($filters['per_page']) ? max(1, (int)$filters['per_page']) : 25;
        $offset = ($page - 1) * $perPage;

        $dataSql = "SELECT
                p.id,
                p.code_employe,
                p.nom,
                p.prenom,
                p.email,
                p.telephone,
                p.est_actif,
                p.personnel_role_id,
                p.entreprise_id,
                p.site_defaut_id,
                pr.libelle as role_libelle,
                pr.niveau_acces,
                e.nom as entreprise_nom,
                s.nom as site_nom
            " . $base . $where . " ORDER BY p.nom, p.prenom LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($dataSql);
        $execParams = array_merge($params, [$perPage, $offset]);
        $stmt->execute($execParams);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("PersonnelModel::getAll retrieved " . count($results) . " personnels (page $page)");

        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage
        ];
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
                p.site_defaut_id,
                pr.libelle as role_libelle,
                pr.niveau_acces,
                e.nom as entreprise_nom,
                s.nom as site_nom
            FROM personnel p
            INNER JOIN personnel_role pr ON p.personnel_role_id = pr.id
            LEFT JOIN entreprise e ON p.entreprise_id = e.id
            LEFT JOIN site s ON p.site_defaut_id = s.id
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
                telephone, est_actif, personnel_role_id, entreprise_id, site_defaut_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        // Generate code_employe if not provided
        if (empty($data['code_employe'])) {
            $data['code_employe'] = $this->generateEmployeeCode();
        } else {
            // if provided, ensure uniqueness
            if ($this->codeExists($data['code_employe'])) throw new InvalidArgumentException("Code employé déjà utilisé");
        }

        // Check uniqueness of email
        if (isset($data['email']) && $this->emailExists($data['email'])) throw new InvalidArgumentException("Email déjà utilisé");

        // Accept either raw mot_de_passe or mot_de_passe_hash. If mot_de_passe provided we store it AS IS (no hashing) per current request.
        $passwordToStore = $data['mot_de_passe'] ?? $data['mot_de_passe_hash'] ?? password_hash('default123', PASSWORD_DEFAULT);

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['code_employe'],
            $data['nom'],
            $data['prenom'] ?? null,
            $data['email'],
            $passwordToStore,
            $data['telephone'] ?? null,
            $data['est_actif'] ?? true,
            $data['personnel_role_id'],
            $data['entreprise_id'] ?? null,
            $data['site_defaut_id'] ?? null
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
                entreprise_id = ?,
                site_defaut_id = ?
            WHERE id = ?
        ";

        // Uniqueness checks for update
        if (isset($data['email'])) {
            $existing = $this->emailExists($data['email']);
            if ($existing && $existing !== $id) throw new InvalidArgumentException("Email déjà utilisé par un autre employé");
        }
        if (isset($data['code_employe'])) {
            $existing = $this->codeExists($data['code_employe']);
            if ($existing && $existing !== $id) throw new InvalidArgumentException("Code employé déjà utilisé par un autre employé");
        }

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
            $data['site_defaut_id'] ?? null,
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

    public function resetPassword($id, $newPassword = null)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("PersonnelModel::resetPassword called with id=$id");

        $passwordToSet = $newPassword ?? 'default123';
        $newPasswordHash = password_hash($passwordToSet, PASSWORD_DEFAULT);

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
                p.site_defaut_id,
                pr.libelle as role_libelle,
                pr.niveau_acces,
                e.nom as entreprise_nom,
                s.nom as site_nom
            FROM personnel p
            INNER JOIN personnel_role pr ON p.personnel_role_id = pr.id
            LEFT JOIN entreprise e ON p.entreprise_id = e.id
            LEFT JOIN site s ON p.site_defaut_id = s.id
            WHERE p.personnel_role_id = ?
            ORDER BY p.nom, p.prenom
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$roleId]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("PersonnelModel::getByRole retrieved " . count($results) . " personnels");

        return $results;
    }

    public function getRoles()
    {
        $stmt = $this->db->prepare("SELECT id, code, libelle, niveau_acces FROM personnel_role ORDER BY id");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        if ($result) {
            $hash = $result['mot_de_passe_hash'];
            if (password_verify($password, $hash) || $password === $hash) {
                unset($result['mot_de_passe_hash']);
                error_log("PersonnelModel::authenticate authentication successful for " . $result['email']);
                return $result;
            }
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
            // allow direct comparison if passwords are stored unhashed for now
            if (!$result || $oldPassword !== $result['mot_de_passe_hash']) {
                throw new InvalidArgumentException("Ancien mot de passe incorrect");
            }
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

    private function validatePersonnelData($data, $isCreation = true)
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

        // Vérifier que le rôle existe
        if (isset($data['personnel_role_id']) && !$this->roleExists($data['personnel_role_id'])) {
            throw new InvalidArgumentException("Rôle invalide ou introuvable");
        }

        // Entreprise/site validation
        if (isset($data['entreprise_id']) && $data['entreprise_id'] !== null && !$this->entrepriseExists($data['entreprise_id'])) {
            throw new InvalidArgumentException("Entreprise invalide ou introuvable");
        }

        if (isset($data['site_defaut_id']) && $data['site_defaut_id'] !== null) {
            if (!$this->siteExists($data['site_defaut_id'])) {
                throw new InvalidArgumentException("Site invalide ou introuvable");
            }
            // Si entreprise est renseignée, vérifier la correspondance
            if (isset($data['entreprise_id']) && $data['entreprise_id'] !== null) {
                $stmt = $this->db->prepare("SELECT entreprise_id FROM site WHERE id = ?");
                $stmt->execute([(int)$data['site_defaut_id']]);
                $site = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($site && (int)$site['entreprise_id'] !== (int)$data['entreprise_id']) {
                    throw new InvalidArgumentException("Le site ne correspond pas à l'entreprise sélectionnée");
                }
            }
        }
    }

    private function roleExists($roleId)
    {
        if (!is_numeric($roleId) || (int)$roleId <= 0) return false;
        $stmt = $this->db->prepare("SELECT 1 FROM personnel_role WHERE id = ?");
        $stmt->execute([(int)$roleId]);
        return (bool)$stmt->fetchColumn();
    }

    private function entrepriseExists($entrepriseId)
    {
        if ($entrepriseId === null) return true;
        if (!is_numeric($entrepriseId) || (int)$entrepriseId <= 0) return false;
        $stmt = $this->db->prepare("SELECT 1 FROM entreprise WHERE id = ?");
        $stmt->execute([(int)$entrepriseId]);
        return (bool)$stmt->fetchColumn();
    }

    private function siteExists($siteId)
    {
        if ($siteId === null) return true;
        if (!is_numeric($siteId) || (int)$siteId <= 0) return false;
        $stmt = $this->db->prepare("SELECT 1 FROM site WHERE id = ?");
        $stmt->execute([(int)$siteId]);
        return (bool)$stmt->fetchColumn();
    }

    private function emailExists($email)
    {
        if (empty($email)) return false;
        $stmt = $this->db->prepare("SELECT id FROM personnel WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : false;
    }

    private function codeExists($code)
    {
        if (empty($code)) return false;
        $stmt = $this->db->prepare("SELECT id FROM personnel WHERE code_employe = ? LIMIT 1");
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : false;
    }

    private function generateEmployeeCode()
    {
        // Try generating a unique EMP###### code up to N attempts
        $attempts = 0;
        while ($attempts < 10) {
            $code = 'EMP' . str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            if (!$this->codeExists($code)) return $code;
            $attempts++;
        }
        // Fallback using timestamp
        $code = 'EMP' . time();
        if ($this->codeExists($code)) throw new \Exception('Impossible de générer un code employé unique');
        return $code;
    }
}
