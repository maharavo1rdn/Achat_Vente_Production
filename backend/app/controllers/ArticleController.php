<?php

namespace app\controllers;

use Exception;
use Flight;

class ArticleController {

    public function getAll() {
        try {
            $filters = Flight::request()->query;
            $articles = Flight::articleModel()->getAll($filters);
            Flight::json($articles);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($id) {
        try {
            $article = Flight::articleModel()->getById($id);
            if ($article) {
                Flight::json($article);
            } else {
                Flight::json(['error' => 'Article non trouvé'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function create() {
        try {
            $data = Flight::request()->data;
            $result = Flight::articleModel()->create($data);
            Flight::json($result, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id) {
        try {
            $data = Flight::request()->data;
            $result = Flight::articleModel()->update($id, $data);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id) {
        try {
            $result = Flight::articleModel()->delete($id);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getByCategorie($categorieId) {
        try {
            $articles = Flight::articleModel()->getByCategorie($categorieId);
            Flight::json($articles);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getStockByArticle($id) {
        try {
            $stocks = Flight::articleModel()->getStockByArticle($id);
            Flight::json($stocks);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getMouvementsByArticle($id) {
        try {
            $mouvements = Flight::articleModel()->getMouvementsByArticle($id);
            Flight::json($mouvements);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
