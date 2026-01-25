<?php

namespace app\models;

use InvalidArgumentException;
use PDO;
use Exception;

class ModePaiementModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    public function getAll()
    {
        $stmt = $this->db->prepare('SELECT id, code, libelle FROM mode_paiement ORDER BY id');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        if ($id <= 0) throw new InvalidArgumentException('ID invalide');
        $stmt = $this->db->prepare('SELECT id, code, libelle FROM mode_paiement WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create($data)
    {
        if (empty($data['code']) || empty($data['libelle'])) throw new InvalidArgumentException('code et libelle requis');
        $stmt = $this->db->prepare('INSERT INTO mode_paiement (code, libelle) VALUES (?, ?)');
        $stmt->execute([$data['code'], $data['libelle']]);
        return (int)$this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        if ($id <= 0) throw new InvalidArgumentException('ID invalide');
        $fields = [];
        $params = [];
        if (isset($data['code'])) { $fields[] = 'code = ?'; $params[] = $data['code']; }
        if (isset($data['libelle'])) { $fields[] = 'libelle = ?'; $params[] = $data['libelle']; }
        if (count($fields) === 0) return false;
        $params[] = $id;
        $stmt = $this->db->prepare('UPDATE mode_paiement SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function delete($id)
    {
        if ($id <= 0) throw new InvalidArgumentException('ID invalide');
        $stmt = $this->db->prepare('DELETE FROM mode_paiement WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
