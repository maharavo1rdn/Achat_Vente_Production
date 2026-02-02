<?php

namespace app\models;

use PDO;

class StatutModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    public function getAll()
    {
        $query = "SELECT id, code, libelle, niveau FROM statut ORDER BY id";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
