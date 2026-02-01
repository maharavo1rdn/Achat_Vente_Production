<?php

namespace app\models;

use InvalidArgumentException;
use PDO;

class StockModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    public function getStock($filters = [])
    {
        error_log("StockModel::getStock called with filters: " . json_encode($filters));

        $query = "
            SELECT
                s.id,
                s.article_id,
                s.entreprise_id,
                s.quantite_actuelle,
                s.date_maj,
                a.reference,
                a.designation,
                a.prix_achat_ref,
                a.prix_vente_ref,
                e.nom as entreprise_nom,
                u.code as unite_code,
                u.libelle as unite
            FROM stock s
            INNER JOIN article a ON s.article_id = a.id
            INNER JOIN entreprise e ON s.entreprise_id = e.id
            INNER JOIN unite u ON a.unite_id = u.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['entreprise_id'])) {
            $query .= " AND s.entreprise_id = ?";
            $params[] = $filters['entreprise_id'];
        }

        if (isset($filters['article_id'])) {
            $query .= " AND s.article_id = ?";
            $params[] = $filters['article_id'];
        }

        if (isset($filters['quantite_min'])) {
            $query .= " AND s.quantite_actuelle >= ?";
            $params[] = $filters['quantite_min'];
        }

        $query .= " ORDER BY a.designation, e.nom";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("StockModel::getStock retrieved " . count($results) . " stock entries");

        return $results;
    }

    public function getStockByArticle($articleId, $entrepriseId = null)
    {
        if ($articleId <= 0) {
            throw new InvalidArgumentException("L'ID d'article doit être un entier positif");
        }

        error_log("StockModel::getStockByArticle called with articleId=$articleId, entrepriseId=" . ($entrepriseId ?? 'null'));

        $query = "
            SELECT
                s.id,
                s.article_id,
                s.depot_id,
                s.quantite_actuelle,
                s.date_maj,
                a.reference,
                a.designation,
                e.nom as depot_nom
            FROM stock s
            INNER JOIN article a ON s.article_id = a.id
            INNER JOIN depot e ON s.depot_id = e.id
            WHERE s.article_id = ?
        ";

        $params = [$articleId];

       

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            error_log("StockModel::getStockByArticle stock found");
        } else {
            error_log("StockModel::getStockByArticle no stock found");
        }

        return $result ?: null;
    }

    public function getMouvements($filters = [])
    {
        error_log("StockModel::getMouvements called with filters: " . json_encode($filters));

        $query = "
            SELECT
                ms.id,
                ms.date_mouvement,
                ms.type_mouvement,
                ms.quantite_stock_avant,
                ms.quantite_entree,
                ms.quantite_sortie,
                ms.quantite_stock_apres,
                ms.prix_unitaire_mouvement,
                ms.article_id,
                ms.personnel_id,
                ms.depot_id,
                ms.reference_document,
                a.reference as article_reference,
                a.designation as article_designation,
                d.nom as depot_nom,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom
            FROM mouvement_stock ms
            INNER JOIN article a ON ms.article_id = a.id
            INNER JOIN depot d ON ms.depot_id = d.id
            INNER JOIN personnel p ON ms.personnel_id = p.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['article_id'])) {
            $query .= " AND ms.article_id = ?";
            $params[] = $filters['article_id'];
        }
        if(isset($filters['depot_id'])) {
            $query .= " AND ms.depot_id = ?";
            $params[] = $filters['depot_id'];
        }
        if (isset($filters['type_mouvement'])) {
            $query .= " AND ms.type_mouvement = ?";
            $params[] = $filters['type_mouvement'];
        }

        if (isset($filters['date_debut'])) {
            $query .= " AND ms.date_mouvement >= ?";
            $params[] = $filters['date_debut'];
        }

        if (isset($filters['date_fin'])) {
            $query .= " AND ms.date_mouvement <= ?";
            $params[] = $filters['date_fin'];
        }

        $query .= " ORDER BY ms.date_mouvement DESC";

        error_log("query:". $query . " params: " . json_encode($params));
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("StockModel::getMouvements retrieved " . count($results) . " mouvements");

        return $results;
    }

    public function createMouvement($data)
    {
        $this->validateMouvementData($data);

        error_log("StockModel::createMouvement called with data: " . json_encode($data));

        // Récupérer le stock actuel
        $stockActuel = $this->getStockByArticle($data['article_id'], $data['depot_id']);
        $quantiteAvant = $stockActuel ? $stockActuel['quantite_actuelle'] : 0;

        // Calculer la quantité après mouvement
        $quantiteApres = $quantiteAvant + ($data['quantite_entree'] ?? 0) - ($data['quantite_sortie'] ?? 0);

        $query = "
            INSERT INTO mouvement_stock (
                type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie,
                quantite_stock_apres, prix_unitaire_mouvement, article_id, 
                personnel_id, reference_document,depot_id,date_mouvement
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,NOW())
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['type_mouvement'],
            $quantiteAvant,
            $data['quantite_entree'] ?? 0,
            $data['quantite_sortie'] ?? 0,
            $quantiteApres,
            $data['prix_unitaire_mouvement'] ?? null,
            $data['article_id'],
            $data['personnel_id'],
            $data['reference_document'] ?? null,
            $data['depot_id'] ?? 1
        ]);

        $mouvementId = $this->db->lastInsertId();

        // Mettre à jour le stock
        $this->updateStockQuantite($data['article_id'], $data['depot_id'], $quantiteApres);

        error_log("StockModel::createMouvement created mouvement with id=$mouvementId");
        return (int)$mouvementId;
    }

    public function getHistoriqueArticle($articleId, $limit = 50)
    {
        if ($articleId <= 0) {
            throw new InvalidArgumentException("L'ID d'article doit être un entier positif");
        }

        error_log("StockModel::getHistoriqueArticle called with articleId=$articleId, limit=$limit");

        $query = "
            SELECT
                ms.id,
                ms.date_mouvement,
                ms.type_mouvement,
                ms.quantite_stock_avant,
                ms.quantite_entree,
                ms.quantite_sortie,
                ms.quantite_stock_apres,
                ms.prix_unitaire_mouvement,
                ms.reference_document,
                e.nom as entreprise_nom,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom
            FROM mouvement_stock ms
            INNER JOIN entreprise e ON ms.entreprise_id = e.id
            INNER JOIN personnel p ON ms.personnel_id = p.id
            WHERE ms.article_id = ?
            ORDER BY ms.date_mouvement DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$articleId, $limit]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("StockModel::getHistoriqueArticle retrieved " . count($results) . " mouvements");

        return $results;
    }

    public function getStockAlerte($seuilMinimum = 10)
    {
        error_log("StockModel::getStockAlerte called with seuilMinimum=$seuilMinimum");

        $query = "
            SELECT
                s.id,
                s.article_id,
                s.entreprise_id,
                s.quantite_actuelle,
                s.date_maj,
                a.reference,
                a.designation,
                e.nom as entreprise_nom,
                u.code as unite_code
            FROM stock s
            INNER JOIN article a ON s.article_id = a.id
            INNER JOIN entreprise e ON s.entreprise_id = e.id
            INNER JOIN unite u ON a.unite_id = u.id
            WHERE s.quantite_actuelle <= ?
            ORDER BY s.quantite_actuelle ASC, a.designation
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$seuilMinimum]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("StockModel::getStockAlerte retrieved " . count($results) . " alertes");

        return $results;
    }

    private function updateStockQuantite($articleId, $depotId, $nouvelleQuantite)
    {
        error_log("StockModel::updateStockQuantite called with articleId=$articleId, entrepriseId=$depotId, nouvelleQuantite=$nouvelleQuantite");

        // Vérifier si l'entrée stock existe
        $stockExistant = $this->getStockByArticle($articleId, $depotId);

        if ($stockExistant) {
            // Mettre à jour
            $query = "UPDATE stock SET quantite_actuelle = ?, date_maj = NOW() WHERE article_id = ? AND depot_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$nouvelleQuantite, $articleId, $depotId]);
        } else {
            // Créer nouvelle entrée
            $query = "INSERT INTO stock (article_id, depot_id, quantite_actuelle) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$articleId, $depotId, $nouvelleQuantite]);
        }

        error_log("StockModel::updateStockQuantite stock updated");
    }

    private function validateMouvementData($data)
    {
        if (empty($data['type_mouvement'])) {
            throw new InvalidArgumentException("Le type de mouvement est obligatoire");
        }

        $typesValides = ['ACHAT', 'VENTE', 'INVENTAIRE', 'TRANSFERT'];
        if (!in_array($data['type_mouvement'], $typesValides)) {
            throw new InvalidArgumentException("Type de mouvement invalide");
        }

        if (!isset($data['article_id']) || $data['article_id'] <= 0) {
            throw new InvalidArgumentException("L'article est obligatoire");
        }

        if (!isset($data['depot_id']) || $data['depot_id'] <= 0) {
            throw new InvalidArgumentException("Le dépôt est obligatoire");
        }

        if (!isset($data['personnel_id']) || $data['personnel_id'] <= 0) {
            throw new InvalidArgumentException("Le personnel est obligatoire");
        }

        $hasEntree = isset($data['quantite_entree']) && $data['quantite_entree'] > 0;
        $hasSortie = isset($data['quantite_sortie']) && $data['quantite_sortie'] > 0;

        if (!$hasEntree && !$hasSortie) {
            throw new InvalidArgumentException("Au moins une quantité d'entrée ou de sortie doit être spécifiée");
        }

        if ($hasEntree && $hasSortie) {
            throw new InvalidArgumentException("Un mouvement ne peut pas avoir à la fois une entrée et une sortie");
        }

        if (isset($data['prix_unitaire_mouvement']) && $data['prix_unitaire_mouvement'] < 0) {
            throw new InvalidArgumentException("Le prix unitaire ne peut pas être négatif");
        }
    }
}