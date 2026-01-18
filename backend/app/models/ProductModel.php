<?php

namespace app\models;

class ProductModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }
    public function getPersonnels()
    {
        $sql = "SELECT p.*, pe.nom, pe.prenom, pe.cin, pe.contact, pe.date_naissance
                FROM personnel p
                JOIN personne pe ON p.id_personne = pe.id
                ORDER BY p.id";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
