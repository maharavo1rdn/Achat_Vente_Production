<?php

namespace app\models;

use Flight;
use InvalidArgumentException;
use PDO;

class ArticleModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    public function getAll($filters = [])
    {
        error_log("ArticleModel::getAll called with filters: " . json_encode($filters));

        $query = "
            SELECT
                a.id,
                a.reference,
                a.designation,
                a.description,
                a.prix_achat_ref,
                a.prix_vente_ref,
                a.taux_tva,
                a.est_actif,
                u.code as unite_code,
                u.libelle as unite,
                ac.libelle as categorie
            FROM article a
            INNER JOIN unite u ON a.unite_id = u.id
            INNER JOIN article_categorie ac ON a.article_categorie_id = ac.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['search'])) {
            $query .= " AND (a.reference LIKE ? OR a.designation LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        if (isset($filters['categorie_id'])) {
            $query .= " AND a.article_categorie_id = ?";
            $params[] = $filters['categorie_id'];
        }

        if (isset($filters['est_actif'])) {
            $query .= " AND a.est_actif = ?";
            $params[] = $filters['est_actif'] === 'true';
        }

        $query .= " ORDER BY a.designation";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("ArticleModel::getAll retrieved " . count($results) . " articles");

        return $results;
    }

    public function getById($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("ArticleModel::getById called with id=$id");

        $query = "
            SELECT
                a.id,
                a.reference,
                a.designation,
                a.description,
                a.prix_achat_ref,
                a.prix_vente_ref,
                a.taux_tva,
                a.est_actif,
                a.unite_id,
                a.article_categorie_id,
                u.code as unite_code,
                u.libelle as unite,
                ac.libelle as categorie
            FROM article a
            INNER JOIN unite u ON a.unite_id = u.id
            INNER JOIN article_categorie ac ON a.article_categorie_id = ac.id
            WHERE a.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            error_log("ArticleModel::getById article with id=$id not found");
            return null;
        }

        error_log("ArticleModel::getById article found: " . $result['designation']);
        return $result;
    }
    public function getCategories() {
        return Flight::appModel()->getAll("article_categorie");
    }
    public function create($data)
    {
        $this->validateArticleData($data);

        error_log("ArticleModel::create called with data: " . json_encode($data));

        $query = "
            INSERT INTO article (
                reference, designation, description, prix_achat_ref,
                prix_vente_ref, taux_tva, est_actif, unite_id, article_categorie_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['reference'],
            $data['designation'],
            $data['description'] ?? null,
            $data['prix_achat_ref'] ?? 0,
            $data['prix_vente_ref'] ?? 0,
            $data['taux_tva'] ?? 20.00,
            $data['est_actif'] ?? true,
            $data['unite_id'],
            $data['article_categorie_id']
        ]);

        $newId = $this->db->lastInsertId();
        error_log("ArticleModel::create created article with id=$newId");

        return (int)$newId;
    }

    public function update($id, $data)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        $this->validateArticleData($data, false);

        error_log("ArticleModel::update called with id=$id, data: " . json_encode($data));

        $query = "
            UPDATE article SET
                reference = ?,
                designation = ?,
                description = ?,
                prix_achat_ref = ?,
                prix_vente_ref = ?,
                taux_tva = ?,
                est_actif = ?,
                unite_id = ?,
                article_categorie_id = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([
            $data['reference'],
            $data['designation'],
            $data['description'] ?? null,
            $data['prix_achat_ref'] ?? 0,
            $data['prix_vente_ref'] ?? 0,
            $data['taux_tva'] ?? 20.00,
            $data['est_actif'] ?? true,
            $data['unite_id'],
            $data['article_categorie_id'],
            $id
        ]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("ArticleModel::update updated article with id=$id");
            return true;
        }

        error_log("ArticleModel::update no article updated with id=$id");
        return false;
    }

    public function delete($id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }

        error_log("ArticleModel::delete called with id=$id");

        $query = "DELETE FROM article WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("ArticleModel::delete deleted article with id=$id");
            return true;
        }

        error_log("ArticleModel::delete no article deleted with id=$id");
        return false;
    }

    public function getByCategorie($categorieId)
    {
        if ($categorieId <= 0) {
            throw new InvalidArgumentException("L'ID de catégorie doit être un entier positif");
        }

        error_log("ArticleModel::getByCategorie called with categorieId=$categorieId");

        $query = "
            SELECT
                a.id,
                a.reference,
                a.designation,
                a.description,
                a.prix_achat_ref,
                a.prix_vente_ref,
                a.taux_tva,
                a.est_actif,
                u.code as unite_code,
                u.libelle as unite,
                ac.libelle as categorie
            FROM article a
            INNER JOIN unite u ON a.unite_id = u.id
            INNER JOIN article_categorie ac ON a.article_categorie_id = ac.id
            WHERE a.article_categorie_id = ?
            ORDER BY a.designation
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$categorieId]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("ArticleModel::getByCategorie retrieved " . count($results) . " articles");

        return $results;
    }

    public function getStockByArticle($articleId)
    {
        if ($articleId <= 0) {
            throw new InvalidArgumentException("L'ID d'article doit être un entier positif");
        }

        error_log("ArticleModel::getStockByArticle called with articleId=$articleId");

        $query = "
            SELECT
                s.id,
                s.article_id,
                s.entreprise_id,
                s.quantite_actuelle,
                s.date_maj,
                e.nom as entreprise_nom,
                a.reference,
                a.designation
            FROM stock s
            INNER JOIN entreprise e ON s.entreprise_id = e.id
            INNER JOIN article a ON s.article_id = a.id
            WHERE s.article_id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$articleId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            error_log("ArticleModel::getStockByArticle stock found for article $articleId");
        } else {
            error_log("ArticleModel::getStockByArticle no stock found for article $articleId");
        }

        return $result ?: null;
    }

    public function getMouvementsByArticle($articleId)
    {
        if ($articleId <= 0) {
            throw new InvalidArgumentException("L'ID d'article doit être un entier positif");
        }

        error_log("ArticleModel::getMouvementsByArticle called with articleId=$articleId");

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
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$articleId]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("ArticleModel::getMouvementsByArticle retrieved " . count($results) . " mouvements");

        return $results;
    }

    private function validateArticleData(array $data, bool $isCreation = true): void
    {
        if ($isCreation || isset($data['reference'])) {
            if (empty($data['reference'])) {
                throw new InvalidArgumentException("La référence est obligatoire");
            }
            if (strlen($data['reference']) > 50) {
                throw new InvalidArgumentException("La référence ne peut pas dépasser 50 caractères");
            }
        }

        if ($isCreation || isset($data['designation'])) {
            if (empty($data['designation'])) {
                throw new InvalidArgumentException("La désignation est obligatoire");
            }
            if (strlen($data['designation']) > 200) {
                throw new InvalidArgumentException("La désignation ne peut pas dépasser 200 caractères");
            }
        }

        if (isset($data['prix_achat_ref']) && $data['prix_achat_ref'] < 0) {
            throw new InvalidArgumentException("Le prix d'achat ne peut pas être négatif");
        }

        if (isset($data['prix_vente_ref']) && $data['prix_vente_ref'] < 0) {
            throw new InvalidArgumentException("Le prix de vente ne peut pas être négatif");
        }

        if (isset($data['taux_tva']) && ($data['taux_tva'] < 0 || $data['taux_tva'] > 100)) {
            throw new InvalidArgumentException("Le taux TVA doit être entre 0 et 100");
        }

        if (($isCreation || isset($data['unite_id'])) && (!isset($data['unite_id']) || $data['unite_id'] <= 0)) {
            throw new InvalidArgumentException("L'unité est obligatoire");
        }

        if (($isCreation || isset($data['article_categorie_id'])) && (!isset($data['article_categorie_id']) || $data['article_categorie_id'] <= 0)) {
            throw new InvalidArgumentException("La catégorie d'article est obligatoire");
        }
    }
}
