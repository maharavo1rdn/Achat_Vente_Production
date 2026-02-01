<?php

namespace app\models;

use InvalidArgumentException;
use PDO;

class MouvementStockModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    /**
     * Récupère la situation globale du stock
     */
    public function getSituationGlobale($filters = [])
    {
        error_log("MouvementStockModel::getSituationGlobale called with filters: " . json_encode($filters));

        $query = "
            SELECT 
                a.id as article_id,
                a.reference as article_reference,
                a.designation as article_designation,
                COALESCE(SUM(
                    CASE 
                        WHEN ms.type_mouvement = 'INVENTAIRE' THEN ms.quantite_entree
                        WHEN ms.type_mouvement = 'ACHAT' THEN ms.quantite_entree
                        WHEN ms.type_mouvement = 'VENTE' THEN -ms.quantite_sortie
                        ELSE 0
                    END
                ), 0) as quantite_totale,
                COUNT(DISTINCT ms.depot_id) as nombre_depots,
                STRING_AGG(DISTINCT d.nom, ', ') as depots
            FROM article a
            LEFT JOIN mouvement_stock ms ON a.id = ms.article_id
            LEFT JOIN depot d ON ms.depot_id = d.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['article_id'])) {
            $query .= " AND a.id = ?";
            $params[] = $filters['article_id'];
        }

        if (isset($filters['depot_id'])) {
            $query .= " AND ms.depot_id = ?";
            $params[] = $filters['depot_id'];
        }

        if (isset($filters['date_debut'])) {
            $query .= " AND ms.date_mouvement >= ?";
            $params[] = $filters['date_debut'];
        }

        if (isset($filters['date_fin'])) {
            $query .= " AND ms.date_mouvement <= ?";
            $params[] = $filters['date_fin'];
        }

        $query .= " GROUP BY a.id, a.reference, a.designation
                    HAVING COALESCE(SUM(
                        CASE 
                            WHEN ms.type_mouvement = 'INVENTAIRE' THEN ms.quantite_entree
                            WHEN ms.type_mouvement = 'ACHAT' THEN ms.quantite_entree
                            WHEN ms.type_mouvement = 'VENTE' THEN -ms.quantite_sortie
                            ELSE 0
                        END
                    ), 0) > 0
                    ORDER BY quantite_totale DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("MouvementStockModel::getSituationGlobale retrieved " . count($results) . " articles");

        return $results;
    }

    /**
     * Récupère la situation du stock par dépôt
     */
    public function getSituationParDepot($filters = [])
    {
        error_log("MouvementStockModel::getSituationParDepot called with filters: " . json_encode($filters));

        $query = "
            SELECT 
                d.id as depot_id,
                d.nom as depot_nom,
                a.id as article_id,
                a.reference as article_reference,
                a.designation as article_designation,
                COALESCE(SUM(
                    CASE 
                        WHEN ms.type_mouvement = 'INVENTAIRE' THEN ms.quantite_entree
                        WHEN ms.type_mouvement = 'ACHAT' THEN ms.quantite_entree
                        WHEN ms.type_mouvement = 'VENTE' THEN -ms.quantite_sortie
                        ELSE 0
                    END
                ), 0) as quantite_stock
            FROM depot d
            CROSS JOIN article a
            LEFT JOIN mouvement_stock ms ON a.id = ms.article_id AND ms.depot_id = d.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['article_id'])) {
            $query .= " AND a.id = ?";
            $params[] = $filters['article_id'];
        }

        if (isset($filters['depot_id'])) {
            $query .= " AND d.id = ?";
            $params[] = $filters['depot_id'];
        }

        if (isset($filters['date_debut'])) {
            $query .= " AND (ms.date_mouvement >= ? OR ms.date_mouvement IS NULL)";
            $params[] = $filters['date_debut'];
        }

        if (isset($filters['date_fin'])) {
            $query .= " AND (ms.date_mouvement <= ? OR ms.date_mouvement IS NULL)";
            $params[] = $filters['date_fin'];
        }

        $query .= " GROUP BY d.id, d.nom, a.id, a.reference, a.designation
                    HAVING COALESCE(SUM(
                        CASE 
                            WHEN ms.type_mouvement = 'INVENTAIRE' THEN ms.quantite_entree
                            WHEN ms.type_mouvement = 'ACHAT' THEN ms.quantite_entree
                            WHEN ms.type_mouvement = 'VENTE' THEN -ms.quantite_sortie
                            ELSE 0
                        END
                    ), 0) > 0
                    ORDER BY d.nom, a.designation";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("MouvementStockModel::getSituationParDepot retrieved " . count($results) . " entries");

        // Regrouper par dépôt pour une meilleure structure
        $groupedResults = [];
        foreach ($results as $row) {
            $depotId = $row['depot_id'];
            if (!isset($groupedResults[$depotId])) {
                $groupedResults[$depotId] = [
                    'depot_id' => $row['depot_id'],
                    'depot_nom' => $row['depot_nom'],
                    'articles' => []
                ];
            }
            $groupedResults[$depotId]['articles'][] = $row;
        }

        return array_values($groupedResults);
    }

    /**
     * Récupère les dernières transactions sorties par dépôt
     */
    public function getDernieresSortiesParDepot($limit = 10, $filters = [])
    {
        error_log("MouvementStockModel::getDernieresSortiesParDepot called with limit=$limit");

        $query = "
            SELECT 
                ms.id,
                ms.date_mouvement,
                ms.type_mouvement,
                ms.quantite_sortie,
                ms.reference_document,
                ms.article_id,
                a.reference as article_reference,
                a.designation as article_designation,
                d.id as depot_id,
                d.nom as depot_nom,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom
            FROM mouvement_stock ms
            INNER JOIN article a ON ms.article_id = a.id
            INNER JOIN depot d ON ms.depot_id = d.id
            INNER JOIN personnel p ON ms.personnel_id = p.id
            WHERE ms.type_mouvement = 'VENTE' 
            AND ms.quantite_sortie > 0
        ";

        $params = [];

        if (isset($filters['depot_id'])) {
            $query .= " AND ms.depot_id = ?";
            $params[] = $filters['depot_id'];
        }

        if (isset($filters['article_id'])) {
            $query .= " AND ms.article_id = ?";
            $params[] = $filters['article_id'];
        }

        if (isset($filters['date_debut'])) {
            $query .= " AND ms.date_mouvement >= ?";
            $params[] = $filters['date_debut'];
        }

        if (isset($filters['date_fin'])) {
            $query .= " AND ms.date_mouvement <= ?";
            $params[] = $filters['date_fin'];
        }

        $query .= " ORDER BY ms.date_mouvement DESC
                    LIMIT ?";
        
        $params[] = $limit;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("MouvementStockModel::getDernieresSortiesParDepot retrieved " . count($results) . " sorties");

        return $results;
    }

    /**
     * Récupère les dernières transactions (tous types)
     */
    public function getDernieresTransactions($limit = 20, $filters = [])
    {
        error_log("MouvementStockModel::getDernieresTransactions called with limit=$limit");

        $query = "
            SELECT 
                ms.id,
                ms.date_mouvement,
                ms.type_mouvement,
                ms.quantite_stock_avant,
                ms.quantite_entree,
                ms.quantite_sortie,
                ms.quantite_stock_apres,
                ms.reference_document,
                ms.article_id,
                a.reference as article_reference,
                a.designation as article_designation,
                d.id as depot_id,
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

        if (isset($filters['type_mouvement'])) {
            $query .= " AND ms.type_mouvement = ?";
            $params[] = $filters['type_mouvement'];
        }

        if (isset($filters['depot_id'])) {
            $query .= " AND ms.depot_id = ?";
            $params[] = $filters['depot_id'];
        }

        if (isset($filters['article_id'])) {
            $query .= " AND ms.article_id = ?";
            $params[] = $filters['article_id'];
        }

        if (isset($filters['date_debut'])) {
            $query .= " AND ms.date_mouvement >= ?";
            $params[] = $filters['date_debut'];
        }

        if (isset($filters['date_fin'])) {
            $query .= " AND ms.date_mouvement <= ?";
            $params[] = $filters['date_fin'];
        }

        $query .= " ORDER BY ms.date_mouvement DESC
                    LIMIT ?";
        
        $params[] = $limit;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("MouvementStockModel::getDernieresTransactions retrieved " . count($results) . " transactions");

        return $results;
    }

    /**
     * Détermine dans quel dépôt une sortie doit être faite selon la méthode FIFO
     */
    public function getDepotFIFOPourSortie($articleId, $quantiteDemandee)
    {
        error_log("MouvementStockModel::getDepotFIFOPourSortie called with articleId=$articleId, quantiteDemandee=$quantiteDemandee");

        if ($articleId <= 0) {
            throw new InvalidArgumentException("L'ID d'article doit être un entier positif");
        }

        if ($quantiteDemandee <= 0) {
            throw new InvalidArgumentException("La quantité demandée doit être positive");
        }

        // Récupérer tous les mouvements d'entrée (INVENTAIRE et ACHAT) triés par date (FIFO)
        $query = "
            SELECT 
                ms.depot_id,
                d.nom as depot_nom,
                SUM(ms.quantite_entree) as quantite_entree_totale,
                MIN(ms.date_mouvement) as date_premiere_entree
            FROM mouvement_stock ms
            INNER JOIN depot d ON ms.depot_id = d.id
            WHERE ms.article_id = ?
            AND ms.type_mouvement IN ('INVENTAIRE', 'ACHAT')
            AND ms.quantite_entree > 0
            GROUP BY ms.depot_id, d.nom
            ORDER BY date_premiere_entree ASC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$articleId]);

        $entreesParDepot = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($entreesParDepot)) {
            error_log("MouvementStockModel::getDepotFIFOPourSortie: Aucune entrée trouvée pour l'article $articleId");
            return null;
        }

        // Calculer le stock actuel par dépôt
        $stockParDepot = [];
        $stockTotal = 0;

        foreach ($entreesParDepot as $entree) {
            $depotId = $entree['depot_id'];
            
            // Calculer les sorties pour ce dépôt
            $querySorties = "
                SELECT COALESCE(SUM(quantite_sortie), 0) as total_sorties
                FROM mouvement_stock
                WHERE article_id = ?
                AND depot_id = ?
                AND type_mouvement = 'VENTE'
            ";
            
            $stmtSorties = $this->db->prepare($querySorties);
            $stmtSorties->execute([$articleId, $depotId]);
            $sorties = $stmtSorties->fetch(PDO::FETCH_ASSOC);
            
            $stockDisponible = $entree['quantite_entree_totale'] - $sorties['total_sorties'];
            
            if ($stockDisponible > 0) {
                $stockParDepot[$depotId] = [
                    'depot_id' => $depotId,
                    'depot_nom' => $entree['depot_nom'],
                    'stock_disponible' => $stockDisponible,
                    'date_premiere_entree' => $entree['date_premiere_entree']
                ];
                $stockTotal += $stockDisponible;
            }
        }

        // Vérifier si le stock total est suffisant
        if ($stockTotal < $quantiteDemandee) {
            error_log("MouvementStockModel::getDepotFIFOPourSortie: Stock insuffisant. Disponible: $stockTotal, Demandé: $quantiteDemandee");
            throw new InvalidArgumentException("Stock insuffisant. Disponible: $stockTotal, Demandé: $quantiteDemandee");
        }

        // Appliquer FIFO : prendre d'abord des dépôts les plus anciens
        usort($stockParDepot, function($a, $b) {
            return strtotime($a['date_premiere_entree']) - strtotime($b['date_premiere_entree']);
        });

        // Déterminer le dépôt pour la sortie
        $quantiteRestante = $quantiteDemandee;
        $depotsPourSortie = [];

        foreach ($stockParDepot as $depot) {
            if ($quantiteRestante <= 0) break;

            $quantiteAPrendre = min($depot['stock_disponible'], $quantiteRestante);
            
            $depotsPourSortie[] = [
                'depot_id' => $depot['depot_id'],
                'depot_nom' => $depot['depot_nom'],
                'quantite_sortie' => $quantiteAPrendre,
                'stock_disponible_apres' => $depot['stock_disponible'] - $quantiteAPrendre
            ];

            $quantiteRestante -= $quantiteAPrendre;
        }

        return $depotsPourSortie;
    }

    /**
     * Crée une sortie de stock avec FIFO
     */
    public function creerSortieAvecFIFO($articleId, $quantite, $personnelId, $referenceDocument, $prixUnitaire = null)
    {
        error_log("MouvementStockModel::creerSortieAvecFIFO called: articleId=$articleId, quantite=$quantite");

        // Déterminer les dépôts pour la sortie FIFO
        $depotsPourSortie = $this->getDepotFIFOPourSortie($articleId, $quantite);

        if (empty($depotsPourSortie)) {
            throw new InvalidArgumentException("Impossible de déterminer les dépôts pour la sortie");
        }

        $mouvementsIds = [];

        // Créer les mouvements de sortie pour chaque dépôt
        foreach ($depotsPourSortie as $depot) {
            // Récupérer le stock avant pour ce dépôt
            $stockAvant = $this->getStockActuelParDepot($articleId, $depot['depot_id']);
            
            $query = "
                INSERT INTO mouvement_stock (
                    type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie,
                    quantite_stock_apres, prix_unitaire_mouvement, article_id, personnel_id,
                    depot_id, reference_document, date_mouvement
                ) VALUES ('VENTE', ?, 0, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";

            $quantiteStockApres = $stockAvant - $depot['quantite_sortie'];

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $stockAvant,
                $depot['quantite_sortie'],
                $quantiteStockApres,
                $prixUnitaire,
                $articleId,
                $personnelId,
                $depot['depot_id'],
                $referenceDocument
            ]);

            $mouvementsIds[] = $this->db->lastInsertId();
            
            error_log("MouvementStockModel::creerSortieAvecFIFO: Sortie créée dans le dépôt {$depot['depot_nom']}, quantité: {$depot['quantite_sortie']}");
        }

        return $mouvementsIds;
    }

    /**
     * Récupère le stock actuel pour un article dans un dépôt spécifique
     */
    private function getStockActuelParDepot($articleId, $depotId)
    {
        // Calculer le stock actuel : entrées - sorties
        $query = "
            SELECT 
                COALESCE(SUM(
                    CASE 
                        WHEN type_mouvement IN ('INVENTAIRE', 'ACHAT') THEN quantite_entree
                        WHEN type_mouvement = 'VENTE' THEN -quantite_sortie
                        ELSE 0
                    END
                ), 0) as stock_actuel
            FROM mouvement_stock
            WHERE article_id = ?
            AND depot_id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$articleId, $depotId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (float)$result['stock_actuel'] : 0;
    }

    /**
     * Récupère les mouvements détaillés avec filtres
     */
    public function getMouvementsDetailles($filters = [])
    {
        error_log("MouvementStockModel::getMouvementsDetailles called with filters: " . json_encode($filters));

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
                ms.article_id,
                ms.depot_id,
                ms.personnel_id,
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

        if (isset($filters['depot_id'])) {
            $query .= " AND ms.depot_id = ?";
            $params[] = $filters['depot_id'];
        }

        if (isset($filters['type_mouvement'])) {
            $query .= " AND ms.type_mouvement = ?";
            $params[] = $filters['type_mouvement'];
        }

        if (isset($filters['date_debut'])) {
            $query .= " AND DATE(ms.date_mouvement) >= ?";
            $params[] = $filters['date_debut'];
        }

        if (isset($filters['date_fin'])) {
            $query .= " AND DATE(ms.date_mouvement) <= ?";
            $params[] = $filters['date_fin'];
        }

        if (isset($filters['reference_document'])) {
            $query .= " AND ms.reference_document LIKE ?";
            $params[] = '%' . $filters['reference_document'] . '%';
        }

        $query .= " ORDER BY ms.date_mouvement DESC";

        if (isset($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = $filters['limit'];
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("MouvementStockModel::getMouvementsDetailles retrieved " . count($results) . " mouvements");

        return $results;
    }

    /**
     * Récupère les statistiques des mouvements
     */
    public function getStatistiquesMouvements($filters = [])
    {
        error_log("MouvementStockModel::getStatistiquesMouvements called");

        $query = "
            SELECT 
                type_mouvement,
                COUNT(*) as nombre_mouvements,
                SUM(quantite_entree) as total_entrees,
                SUM(quantite_sortie) as total_sorties,
                COUNT(DISTINCT article_id) as nombre_articles,
                COUNT(DISTINCT depot_id) as nombre_depots
            FROM mouvement_stock
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['date_debut'])) {
            $query .= " AND DATE(date_mouvement) >= ?";
            $params[] = $filters['date_debut'];
        }

        if (isset($filters['date_fin'])) {
            $query .= " AND DATE(date_mouvement) <= ?";
            $params[] = $filters['date_fin'];
        }

        if (isset($filters['depot_id'])) {
            $query .= " AND depot_id = ?";
            $params[] = $filters['depot_id'];
        }

        $query .= " GROUP BY type_mouvement
                    ORDER BY type_mouvement";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ajouter les totaux
        $totalEntrees = 0;
        $totalSorties = 0;
        $totalMouvements = 0;
        
        foreach ($results as $row) {
            $totalEntrees += $row['total_entrees'];
            $totalSorties += $row['total_sorties'];
            $totalMouvements += $row['nombre_mouvements'];
        }
        
        $stats = [
            'par_type' => $results,
            'totaux' => [
                'total_mouvements' => $totalMouvements,
                'total_entrees' => $totalEntrees,
                'total_sorties' => $totalSorties,
                'solde_net' => $totalEntrees - $totalSorties
            ]
        ];

        error_log("MouvementStockModel::getStatistiquesMouvements retrieved statistics");

        return $stats;
    }

    /**
     * Crée un mouvement simple (entrée, sortie, inventaire)
     */
    public function createMouvementSimple($data)
    {
        $this->validateMouvementSimpleData($data);
        
        error_log("MouvementStockModel::createMouvementSimple called with data: " . json_encode($data));
        
        // Récupérer le stock avant
        $stockAvant = $this->getStockActuelParDepot($data['article_id'], $data['depot_id']);
        
        // Calculer le stock après
        $entree = $data['quantite_entree'] ?? 0;
        $sortie = $data['quantite_sortie'] ?? 0;
        $stockApres = $stockAvant + $entree - $sortie;
        
        $query = "
            INSERT INTO mouvement_stock (
                type_mouvement, quantite_stock_avant, quantite_entree, quantite_sortie,
                quantite_stock_apres, prix_unitaire_mouvement, article_id, personnel_id,
                depot_id, reference_document, date_mouvement
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['type_mouvement'],
            $stockAvant,
            $entree,
            $sortie,
            $stockApres,
            $data['prix_unitaire_mouvement'] ?? null,
            $data['article_id'],
            $data['personnel_id'],
            $data['depot_id'],
            $data['reference_document'] ?? null
        ]);
        
        $mouvementId = $this->db->lastInsertId();
        error_log("MouvementStockModel::createMouvementSimple created mouvement with id=$mouvementId");
        
        return (int)$mouvementId;
    }

    /**
     * Valide les données d'un mouvement simple
     */
    private function validateMouvementSimpleData($data)
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

    /**
     * Méthode publique pour récupérer le stock actuel par dépôt
     */
    public function getStockActuelParDepotPublic($articleId, $depotId)
    {
        return $this->getStockActuelParDepot($articleId, $depotId);
    }
}