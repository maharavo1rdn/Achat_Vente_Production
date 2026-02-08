<?php

namespace app\models;

use Flight;
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
    /**
 * Crée une sortie de stock avec FIFO (First In, First Out)
 * Version avec création de mouvements de sortie séparés pour chaque lot FIFO
 * @throws InvalidArgumentException si stock insuffisant
 */
    public function creerSortieAvecFIFO($articleId, $quantite, $personnelId, $referenceDocument, $prixUnitaire = null)
    {
        error_log("=== DEBUT creerSortieAvecFIFO ===");
        error_log("Article: $articleId, Quantité: $quantite, Référence: $referenceDocument");
        
        $this->db->beginTransaction();
        
        try {
        
        
            // 1. RÉCUPÉRER LES ENTRÉES DISPONIBLES triées FIFO
            $sql = "
                SELECT 
                    ms.id as entree_id,
                    ms.depot_id,
                    ms.quantite_entree,
                    ms.date_mouvement,
                    ms.prix_unitaire_mouvement as prix_unitaire_achat,
                    ms.reference_document as reference_entree,
                    d.nom as depot_nom,
                    -- Calculer ce qui reste disponible via lot_stock
                    COALESCE(ls.quantite_restante, ms.quantite_entree) as quantite_disponible
                FROM mouvement_stock ms
                LEFT JOIN depot d ON ms.depot_id = d.id
                LEFT JOIN lot_stock ls ON ls.mouvement_entree_id = ms.id AND ls.statut = 'ACTIF'
                WHERE ms.article_id = ?
                AND ms.type_mouvement IN ('ACHAT', 'INVENTAIRE')
                AND ms.quantite_entree > 0
                AND COALESCE(ls.quantite_restante, ms.quantite_entree) > 0
                ORDER BY ms.date_mouvement ASC  -- FIFO: plus ancien d'abord
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$articleId]);
            $entreesDisponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Entrées disponibles (FIFO): " . count($entreesDisponibles));
            
            if (empty($entreesDisponibles)) {
                throw new InvalidArgumentException("Aucune entrée disponible pour l'article $articleId");
            }
            
            // 2. CALCULER LE STOCK TOTAL DISPONIBLE
            $stockTotalDisponible = 0;
            foreach ($entreesDisponibles as $entree) {
                $stockTotalDisponible += $entree['quantite_disponible'];
                error_log("Lot {$entree['reference_entree']}: Disponible = {$entree['quantite_disponible']}");
            }
            
            error_log("Stock total disponible: $stockTotalDisponible");
            
            if ($stockTotalDisponible < $quantite) {
                throw new InvalidArgumentException(
                    "Stock insuffisant. Disponible: $stockTotalDisponible, Demandé: $quantite"
                );
            }
            
            // 3. APPLIQUER FIFO - créer un mouvement par lot utilisé
            $quantiteRestante = $quantite;
            $mouvementsSortieIds = [];
            $lotsUtilises = [];
            
            foreach ($entreesDisponibles as $entree) {
                if ($quantiteRestante <= 0) break;
                
                $quantiteDisponibleDansLot = $entree['quantite_disponible'];
                $quantiteAPrendre = min($quantiteRestante, $quantiteDisponibleDansLot);
                
                if ($quantiteAPrendre > 0) {
                    // CRÉER UN MOUVEMENT DE SORTIE POUR CE LOT
                    $mouvementSortieId = $this->creerMouvementSortie(
                        $articleId,
                        $quantiteAPrendre,
                        $personnelId,
                        $referenceDocument,
                        $entree['depot_id'],
                        $prixUnitaire ?? $entree['prix_unitaire_achat'],
                        $entree['reference_entree']  // Référence de l'entrée source
                    );
                    
                    $mouvementsSortieIds[] = $mouvementSortieId;
                    
                    $lotsUtilises[] = [
                        'entree_id' => $entree['entree_id'],
                        'reference_entree' => $entree['reference_entree'],
                        'quantite_prelevee' => $quantiteAPrendre,
                        'quantite_restante_dans_lot' => $quantiteDisponibleDansLot - $quantiteAPrendre,
                        'depot' => $entree['depot_nom'],
                        'date_entree' => $entree['date_mouvement']
                    ];
                    
                    $quantiteRestante -= $quantiteAPrendre;
                    
                    error_log("Créé sortie ID $mouvementSortieId: $quantiteAPrendre unités du lot {$entree['reference_entree']}");
                    error_log("Reste à sortir: $quantiteRestante");
                }
            }
            
            // 4. CALCULER LE COÛT TOTAL FIFO
            $coutTotalFIFO = 0;
            foreach ($lotsUtilises as $lot) {
                $coutTotalFIFO += $lot['quantite_prelevee'] * ($lot['prix_unitaire_achat'] ?? 0);
            }
            
            // 5. VALIDER LA TRANSACTION
            $this->db->commit();
            
            $quantiteSortie = $quantite - $quantiteRestante;
            $resultat = [
                'success' => true,
                'quantite_demandee' => $quantite,
                'quantite_sortie_reelle' => $quantiteSortie,
                'quantite_restante' => $quantiteRestante,
                'mouvements_sortie_ids' => $mouvementsSortieIds,
                'lots_utilises' => $lotsUtilises,
                'cout_total_fifo' => round($coutTotalFIFO, 2),
                'cout_moyen_unitaire_fifo' => $quantiteSortie > 0 ? round($coutTotalFIFO / $quantiteSortie, 2) : 0,
                'nombre_mouvements_crees' => count($mouvementsSortieIds),
                'type_sortie' => 'FIFO',
                'message' => "Sortie FIFO créée avec " . count($mouvementsSortieIds) . " mouvement(s)"
            ];
            
            error_log("=== FIN creerSortieAvecFIFO ===");
            error_log("Coût total FIFO: $coutTotalFIFO");
            
            return $resultat;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("ERREUR creerSortieAvecFIFO: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crée un mouvement de sortie lié à une entrée spécifique
     */
   
    /**
     * Récupère les entrées disponibles pour FIFO (pour les tests)
     */
    public function getEntreesDisponiblesPourFIFO($articleId)
    {
        $sql = "
            SELECT 
                ms.id,
                ms.reference_document,
                ms.quantite_entree,
                ms.date_mouvement,
                ms.depot_id,
                d.nom as depot_nom,
                ms.prix_unitaire_mouvement,
                -- Calculer le disponible via lot_stock
                COALESCE(ls.quantite_restante, ms.quantite_entree) as quantite_disponible
            FROM mouvement_stock ms
            LEFT JOIN depot d ON ms.depot_id = d.id
            LEFT JOIN lot_stock ls ON ls.mouvement_entree_id = ms.id AND ls.statut = 'ACTIF'
            WHERE ms.article_id = ?
            AND ms.type_mouvement IN ('ACHAT', 'INVENTAIRE')
            AND ms.quantite_entree > 0
            AND COALESCE(ls.quantite_restante, ms.quantite_entree) > 0
            ORDER BY ms.date_mouvement ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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


    /**
 * Crée une sortie de stock avec LIFO (Last In, First Out)
 * Principe : Les dernières entrées sont sorties en premier
 * @throws InvalidArgumentException si stock insuffisant
 */
    public function creerSortieAvecLIFO($articleId, $quantite, $personnelId, $referenceDocument, $prixUnitaire = null)
    {
        error_log("=== DEBUT creerSortieAvecLIFO ===");
        error_log("Article: $articleId, Quantité: $quantite, Référence: $referenceDocument");
        
        $this->db->beginTransaction();
        
        try {
        
        
            // 1. RÉCUPÉRER LES ENTRÉES DISPONIBLES triées LIFO (inverse de FIFO)
            $sql = "
                SELECT 
                    ms.id as entree_id,
                    ms.depot_id,
                    ms.quantite_entree,
                    ms.date_mouvement,
                    ms.prix_unitaire_mouvement as prix_unitaire_achat,
                    ms.reference_document as reference_entree,
                    d.nom as depot_nom,
                    -- Calculer ce qui reste disponible via lot_stock
                    COALESCE(ls.quantite_restante, ms.quantite_entree) as quantite_disponible
                FROM mouvement_stock ms
                LEFT JOIN depot d ON ms.depot_id = d.id
                LEFT JOIN lot_stock ls ON ls.mouvement_entree_id = ms.id AND ls.statut = 'ACTIF'
                WHERE ms.article_id = ?
                AND ms.type_mouvement IN ('ACHAT', 'INVENTAIRE')
                AND ms.quantite_entree > 0
                AND COALESCE(ls.quantite_restante, ms.quantite_entree) > 0
                ORDER BY ms.date_mouvement DESC  -- LIFO: plus récent d'abord
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$articleId]);
            $entreesDisponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Entrées disponibles (LIFO): " . count($entreesDisponibles));
            
            if (empty($entreesDisponibles)) {
                throw new InvalidArgumentException("Aucune entrée disponible pour l'article $articleId");
            }
            
            // 2. CALCULER LE STOCK TOTAL DISPONIBLE
            $stockTotalDisponible = 0;
            foreach ($entreesDisponibles as $entree) {
                $stockTotalDisponible += $entree['quantite_disponible'];
                error_log("Lot {$entree['reference_entree']}: Disponible = {$entree['quantite_disponible']}");
            }
            
            error_log("Stock total disponible: $stockTotalDisponible");
            
            if ($stockTotalDisponible < $quantite) {
                throw new InvalidArgumentException(
                    "Stock insuffisant. Disponible: $stockTotalDisponible, Demandé: $quantite"
                );
            }
            
            // 3. APPLIQUER LIFO - créer un mouvement par lot utilisé
            $quantiteRestante = $quantite;
            $mouvementsSortieIds = [];
            $lotsUtilises = [];
            
            foreach ($entreesDisponibles as $entree) {
                if ($quantiteRestante <= 0) break;
                
                $quantiteDisponibleDansLot = $entree['quantite_disponible'];
                $quantiteAPrendre = min($quantiteRestante, $quantiteDisponibleDansLot);
                
                if ($quantiteAPrendre > 0) {
                    // CRÉER UN MOUVEMENT DE SORTIE POUR CE LOT
                    $mouvementSortieId = $this->creerMouvementSortie(
                        $articleId,
                        $quantiteAPrendre,
                        $personnelId,
                        $referenceDocument,
                        $entree['depot_id'],
                        $prixUnitaire ?? $entree['prix_unitaire_achat'],
                        $entree['reference_entree']  // Référence de l'entrée source
                    );
                    
                    $mouvementsSortieIds[] = $mouvementSortieId;
                    
                    $lotsUtilises[] = [
                        'entree_id' => $entree['entree_id'],
                        'reference_entree' => $entree['reference_entree'],
                        'quantite_prelevee' => $quantiteAPrendre,
                        'quantite_restante_dans_lot' => $quantiteDisponibleDansLot - $quantiteAPrendre,
                        'depot' => $entree['depot_nom'],
                        'date_entree' => $entree['date_mouvement'],
                        'prix_unitaire_achat' => $entree['prix_unitaire_achat'],
                        'type_sortie' => 'LIFO'
                    ];
                    
                    $quantiteRestante -= $quantiteAPrendre;
                    
                    error_log("LIFO: Créé sortie ID $mouvementSortieId: $quantiteAPrendre unités du lot {$entree['reference_entree']}");
                    error_log("Reste à sortir: $quantiteRestante");
                }
            }
            
            // 4. CALCULER LE COÛT TOTAL LIFO (avant commit pour inclure dans la transaction)
            $coutTotalLIFO = 0;
            foreach ($lotsUtilises as $lot) {
                $coutTotalLIFO += $lot['quantite_prelevee'] * ($lot['prix_unitaire_achat'] ?? 0);
            }
            
            // 5. VALIDER LA TRANSACTION
            $this->db->commit();
            
            $quantiteSortie = $quantite - $quantiteRestante;
            $resultat = [
                'success' => true,
                'quantite_demandee' => $quantite,
                'quantite_sortie_reelle' => $quantiteSortie,
                'quantite_restante' => $quantiteRestante,
                'mouvements_sortie_ids' => $mouvementsSortieIds,
                'lots_utilises' => $lotsUtilises,
                'cout_total_lifo' => round($coutTotalLIFO, 2),
                'cout_moyen_unitaire_lifo' => $quantiteSortie > 0 ? 
                    round($coutTotalLIFO / $quantiteSortie, 2) : 0,
                'nombre_mouvements_crees' => count($mouvementsSortieIds),
                'type_sortie' => 'LIFO',
                'message' => "Sortie LIFO créée avec " . count($mouvementsSortieIds) . " mouvement(s)"
            ];
            
            error_log("=== FIN creerSortieAvecLIFO ===");
            error_log("Coût total LIFO: $coutTotalLIFO");
            
            return $resultat;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("ERREUR creerSortieAvecLIFO: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crée une sortie de stock avec CMUP (Coût Moyen Unitaire Pondéré)
     * Principe : Toutes les entrées sont mélangées, on sort à un prix moyen
     * @throws InvalidArgumentException si stock insuffisant
     */
    public function creerSortieAvecCMUP($articleId, $quantite, $personnelId, $referenceDocument, $prixUnitaire = null)
    {
        error_log("=== DEBUT creerSortieAvecCMUP ===");
        error_log("Article: $articleId, Quantité: $quantite, Référence: $referenceDocument");
        
        $this->db->beginTransaction();
        
        try {
        
        
            // 1. RÉCUPÉRER TOUTES LES ENTRÉES DISPONIBLES
            $sql = "
                SELECT 
                    ms.id as entree_id,
                    ms.depot_id,
                    ms.quantite_entree,
                    ms.date_mouvement,
                    ms.prix_unitaire_mouvement as prix_unitaire_achat,
                    ms.reference_document as reference_entree,
                    d.nom as depot_nom,
                    -- Calculer ce qui reste disponible via lot_stock
                    COALESCE(ls.quantite_restante, ms.quantite_entree) as quantite_disponible
                FROM mouvement_stock ms
                LEFT JOIN depot d ON ms.depot_id = d.id
                LEFT JOIN lot_stock ls ON ls.mouvement_entree_id = ms.id AND ls.statut = 'ACTIF'
                WHERE ms.article_id = ?
                AND ms.type_mouvement IN ('ACHAT', 'INVENTAIRE')
                AND ms.quantite_entree > 0
                AND COALESCE(ls.quantite_restante, ms.quantite_entree) > 0
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$articleId]);
            $entreesDisponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Entrées disponibles (CMUP): " . count($entreesDisponibles));
            
            if (empty($entreesDisponibles)) {
                throw new InvalidArgumentException("Aucune entrée disponible pour l'article $articleId");
            }
            
            // 2. CALCULER LE STOCK TOTAL ET LE CMUP
            $stockTotalDisponible = 0;
            $valeurTotaleStock = 0;
            $entreesPourCMUP = [];
            
            foreach ($entreesDisponibles as $entree) {
                $stockTotalDisponible += $entree['quantite_disponible'];
                $valeurLot = $entree['quantite_disponible'] * $entree['prix_unitaire_achat'];
                $valeurTotaleStock += $valeurLot;
                
                $entreesPourCMUP[] = [
                    'entree_id' => $entree['entree_id'],
                    'depot_id' => $entree['depot_id'],
                    'quantite_disponible' => $entree['quantite_disponible'],
                    'prix_unitaire_achat' => $entree['prix_unitaire_achat'],
                    'valeur_lot' => $valeurLot,
                    'reference_entree' => $entree['reference_entree'],
                    'depot_nom' => $entree['depot_nom'],
                    'date_entree' => $entree['date_mouvement']
                ];
            }
            
            // 3. CALCULER LE CMUP (Coût Moyen Unitaire Pondéré)
            $cmup = $stockTotalDisponible > 0 ? $valeurTotaleStock / $stockTotalDisponible : 0;
            
            error_log("Stock total disponible: $stockTotalDisponible");
            error_log("Valeur totale stock: $valeurTotaleStock");
            error_log("CMUP calculé: $cmup");
            
            if ($stockTotalDisponible < $quantite) {
                throw new InvalidArgumentException(
                    "Stock insuffisant. Disponible: $stockTotalDisponible, Demandé: $quantite"
                );
            }
            
            // 4. AVEC CMUP, ON PEUT CRÉER UN SEUL MOUVEMENT DE SORTIE (prix unique)
            // Mais on doit quand même gérer les quantités par dépôt si nécessaire
            $quantiteRestante = $quantite;
            $mouvementsSortieIds = [];
            $lotsUtilises = [];
            
            // Pour CMUP, on prend proportionnellement de chaque lot
            foreach ($entreesPourCMUP as $entree) {
                if ($quantiteRestante <= 0) break;
                
                // Proportion à prendre de ce lot
                $proportion = $entree['quantite_disponible'] / $stockTotalDisponible;
                $quantiteAPrendre = min(
                    floor($quantite * $proportion),  // Prendre proportionnellement
                    $entree['quantite_disponible'],  // Mais pas plus que disponible
                    $quantiteRestante               // Et pas plus que ce qui reste
                );
                
                // Au moins prendre 1 unité si disponible et nécessaire
                if ($quantiteAPrendre <= 0 && $quantiteRestante > 0 && $entree['quantite_disponible'] > 0) {
                    $quantiteAPrendre = min(1, $entree['quantite_disponible'], $quantiteRestante);
                }
                
                if ($quantiteAPrendre > 0) {
                    // Créer un mouvement de sortie avec le prix CMUP
                    $mouvementSortieId = $this->creerMouvementSortie(
                        $articleId,
                        $quantiteAPrendre,
                        $personnelId,
                        $referenceDocument,
                        $entree['depot_id'],
                        $prixUnitaire ?? $cmup,  // Utiliser CMUP comme prix
                        $entree['reference_entree'],
                        'CMUP'  // Indiquer la méthode
                    );
                    
                    $mouvementsSortieIds[] = $mouvementSortieId;
                    
                    $lotsUtilises[] = [
                        'entree_id' => $entree['entree_id'],
                        'reference_entree' => $entree['reference_entree'],
                        'quantite_prelevee' => $quantiteAPrendre,
                        'quantite_restante_dans_lot' => $entree['quantite_disponible'] - $quantiteAPrendre,
                        'depot' => $entree['depot_nom'],
                        'date_entree' => $entree['date_entree'],
                        'prix_unitaire_achat' => $entree['prix_unitaire_achat'],
                        'prix_unitaire_applique' => $cmup,  // Prix CMUP appliqué
                        'type_sortie' => 'CMUP'
                    ];
                    
                    $quantiteRestante -= $quantiteAPrendre;
                    
                    error_log("CMUP: Créé sortie ID $mouvementSortieId: $quantiteAPrendre unités à prix $cmup");
                    error_log("Du lot {$entree['reference_entree']}, Reste à sortir: $quantiteRestante");
                }
            }
            
            // 5. SI IL RESTE ENCORE À SORTIR, PRENDRE DES LOTS AU HASARD
            if ($quantiteRestante > 0) {
                error_log("Prélèvement proportionnel insuffisant, prise complémentaire...");
                
                foreach ($entreesPourCMUP as $entree) {
                    if ($quantiteRestante <= 0) break;
                    
                    // Vérifier combien il reste dans ce lot après le prélèvement proportionnel
                    $lotUtilise = null;
                    foreach ($lotsUtilises as $lot) {
                        if ($lot['entree_id'] == $entree['entree_id']) {
                            $lotUtilise = $lot;
                            break;
                        }
                    }
                    
                    $dejaPreleve = $lotUtilise ? $lotUtilise['quantite_prelevee'] : 0;
                    $quantiteEncoreDisponible = $entree['quantite_disponible'] - $dejaPreleve;
                    
                    if ($quantiteEncoreDisponible > 0) {
                        $quantiteAPrendre = min($quantiteEncoreDisponible, $quantiteRestante);
                        
                        if ($quantiteAPrendre > 0) {
                            // Créer un mouvement de sortie supplémentaire
                            $mouvementSortieId = $this->creerMouvementSortie(
                                $articleId,
                                $quantiteAPrendre,
                                $personnelId,
                                $referenceDocument,
                                $entree['depot_id'],
                                $prixUnitaire ?? $cmup,
                                $entree['reference_entree'],
                                'CMUP'
                            );
                            
                            $mouvementsSortieIds[] = $mouvementSortieId;
                            
                            // Mettre à jour le lot utilisé
                            if ($lotUtilise) {
                                $lotUtilise['quantite_prelevee'] += $quantiteAPrendre;
                                $lotUtilise['quantite_restante_dans_lot'] -= $quantiteAPrendre;
                            } else {
                                $lotsUtilises[] = [
                                    'entree_id' => $entree['entree_id'],
                                    'reference_entree' => $entree['reference_entree'],
                                    'quantite_prelevee' => $quantiteAPrendre,
                                    'quantite_restante_dans_lot' => $entree['quantite_disponible'] - $quantiteAPrendre,
                                    'depot' => $entree['depot_nom'],
                                    'date_entree' => $entree['date_entree'],
                                    'prix_unitaire_achat' => $entree['prix_unitaire_achat'],
                                    'prix_unitaire_applique' => $cmup,
                                    'type_sortie' => 'CMUP'
                                ];
                            }
                            
                            $quantiteRestante -= $quantiteAPrendre;
                            
                            error_log("CMUP complémentaire: $quantiteAPrendre unités du lot {$entree['reference_entree']}");
                        }
                    }
                }
            }
            
            // 6. VALIDER LA TRANSACTION
            $this->db->commit();
            
            // 7. CALCULER LE COÛT TOTAL CMUP
            $quantiteSortie = $quantite - $quantiteRestante;
            $coutTotalCMUP = $quantiteSortie * $cmup;
            
            $resultat = [
                'success' => true,
                'quantite_demandee' => $quantite,
                'quantite_sortie_reelle' => $quantiteSortie,
                'quantite_restante' => $quantiteRestante,
                'mouvements_sortie_ids' => $mouvementsSortieIds,
                'lots_utilises' => $lotsUtilises,
                'calculs_cmup' => [
                    'stock_total_disponible' => round($stockTotalDisponible, 2),
                    'valeur_totale_stock' => round($valeurTotaleStock, 2),
                    'cmup_calcule' => round($cmup, 2),
                    'cout_total_cmup' => round($coutTotalCMUP, 2)
                ],
                'cout_total_cmup' => round($coutTotalCMUP, 2),
                'cout_moyen_unitaire_cmup' => round($cmup, 2),
                'nombre_mouvements_crees' => count($mouvementsSortieIds),
                'type_sortie' => 'CMUP',
                'message' => "Sortie CMUP créée avec " . count($mouvementsSortieIds) . " mouvement(s), CMUP: " . round($cmup, 2)
            ];
            
            error_log("=== FIN creerSortieAvecCMUP ===");
            error_log("Coût total CMUP: $coutTotalCMUP, CMUP unitaire: $cmup");
            
            return $resultat;
        
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("ERREUR creerSortieAvecCMUP: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crée un mouvement de sortie (version modifiée pour supporter différentes méthodes)
     */
    private function creerMouvementSortie($articleId, $quantite, $personnelId, $referenceDocument, 
                                        $depotId, $prixUnitaire, $referenceEntreeSource, $methodeSortie = null)
    {
        // 1. Récupérer le stock actuel avant la sortie
        $stockAvant = $this->getStockActuelParDepot($articleId, $depotId);
        $stockApres = $stockAvant - $quantite;
        
        if ($stockApres < 0) {
            throw new InvalidArgumentException("Stock insuffisant dans le dépôt $depotId");
        }
        
        // 2. Créer le mouvement de sortie
        $sql = "
            INSERT INTO mouvement_stock (
                type_mouvement,
                quantite_stock_avant,
                quantite_entree,
                quantite_sortie,
                quantite_stock_apres,
                prix_unitaire_mouvement,
                article_id,
                personnel_id,
                depot_id,
                reference_document,
                date_mouvement
            ) VALUES (
                'VENTE',
                ?,
                0,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NOW()
            )
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $stockAvant,
            $quantite,
            $stockApres,
            $prixUnitaire,
            $articleId,
            $personnelId,
            $depotId,
            $referenceDocument
        ]);
        
        $mouvementId = $this->db->lastInsertId();
        
        // 3. Mettre à jour le stock
        $stockModel = Flight::stockModel();
        $stockModel->updateStockQuantite($articleId, $depotId, $stockApres);
        
        error_log("Mouvement sortie créé: ID $mouvementId, Qty: $quantite, Méthode: $methodeSortie");
        
        return $mouvementId;
    }

    /**
     * Compare les différentes méthodes de sortie pour un article
     */
    // public function comparerMethodesSortie($articleId, $quantite, $personnelId, $referenceDocument, $prixUnitaire = null)
    // {
    //     error_log("=== COMPARAISON MÉTHODES SORTIE ===");
        
    //     $resultats = [];
        
    //     try {
    //         // 1. FIFO
    //         $resultats['FIFO'] = $this->creerSortieAvecFIFO(
    //             $articleId, $quantite, $personnelId, $referenceDocument . '_FIFO', $prixUnitaire
    //         );
    //     } catch (Exception $e) {
    //         $resultats['FIFO'] = ['error' => $e->getMessage()];
    //     }
        
    //     try {
    //         // 2. LIFO
    //         $resultats['LIFO'] = $this->creerSortieAvecLIFO(
    //             $articleId, $quantite, $personnelId, $referenceDocument . '_LIFO', $prixUnitaire
    //         );
    //     } catch (Exception $e) {
    //         $resultats['LIFO'] = ['error' => $e->getMessage()];
    //     }
        
    //     try {
    //         // 3. CMUP
    //         $resultats['CMUP'] = $this->creerSortieAvecCMUP(
    //             $articleId, $quantite, $personnelId, $referenceDocument . '_CMUP', $prixUnitaire
    //         );
    //     } catch (Exception $e) {
    //         $resultats['CMUP'] = ['error' => $e->getMessage()];
    //     }
        
    //     // 4. Analyse comparative
    //     $comparaison = [
    //         'article_id' => $articleId,
    //         'quantite_demandee' => $quantite,
    //         'methodes' => $resultats,
    //         'analyse' => $this->analyserComparaison($resultats)
    //     ];
        
    //     error_log("=== FIN COMPARAISON ===");
        
    //     return $comparaison;
    // }

    /**
     * Analyse la comparaison des différentes méthodes
     */
    private function analyserComparaison($resultats)
    {
        $analyse = [];
        
        if (isset($resultats['FIFO']['cout_total_lifo'])) { // Correction: devrait être cout_total_fifo
            $analyse['FIFO'] = [
                'cout_total' => $resultats['FIFO']['cout_total_lifo'] ?? 0,
                'cout_moyen' => $resultats['FIFO']['cout_moyen_unitaire_lifo'] ?? 0,
                'mouvements' => count($resultats['FIFO']['mouvements_sortie_ids'] ?? [])
            ];
        }
        
        if (isset($resultats['LIFO']['cout_total_lifo'])) {
            $analyse['LIFO'] = [
                'cout_total' => $resultats['LIFO']['cout_total_lifo'],
                'cout_moyen' => $resultats['LIFO']['cout_moyen_unitaire_lifo'],
                'mouvements' => count($resultats['LIFO']['mouvements_sortie_ids'] ?? [])
            ];
        }
        
        if (isset($resultats['CMUP']['cout_total_cmup'])) {
            $analyse['CMUP'] = [
                'cout_total' => $resultats['CMUP']['cout_total_cmup'],
                'cout_moyen' => $resultats['CMUP']['cout_moyen_unitaire_cmup'],
                'mouvements' => count($resultats['CMUP']['mouvements_sortie_ids'] ?? [])
            ];
        }
        
        // Déterminer la méthode la moins chère
        $coutsTotaux = array_filter(array_column($analyse, 'cout_total'));
        if (!empty($coutsTotaux)) {
            $methodeMoinsChere = array_search(min($coutsTotaux), $coutsTotaux);
            $analyse['methode_moins_chere'] = $methodeMoinsChere;
        }
        
        return $analyse;
    }

    /**
     * Simule les différentes méthodes sans créer réellement les mouvements
     */
    public function simulerMethodesSortie($articleId, $quantite)
    {
        error_log("=== SIMULATION MÉTHODES SORTIE ===");
        
        // Récupérer les entrées disponibles
        $entrees = $this->getEntreesDisponiblesPourFIFO($articleId);
        
        if (empty($entrees)) {
            throw new InvalidArgumentException("Aucune entrée disponible pour l'article $articleId");
        }
        
        // Calculer le stock total disponible
        $stockTotal = array_sum(array_column($entrees, 'quantite_disponible'));
        
        if ($stockTotal < $quantite) {
            throw new InvalidArgumentException("Stock insuffisant. Disponible: $stockTotal, Demandé: $quantite");
        }
        
        $simulations = [];
        
        // 1. Simulation FIFO
        $simulations['FIFO'] = $this->simulerFIFO($entrees, $quantite);
        
        // 2. Simulation LIFO
        $simulations['LIFO'] = $this->simulerLIFO($entrees, $quantite);
        
        // 3. Simulation CMUP
        $simulations['CMUP'] = $this->simulerCMUP($entrees, $quantite);
        
        return [
            'article_id' => $articleId,
            'quantite_demandee' => $quantite,
            'stock_disponible' => $stockTotal,
            'entrees_disponibles' => $entrees,
            'simulations' => $simulations,
            'recommendation' => $this->genererRecommendation($simulations)
        ];
    }

    /**
     * Simule une sortie FIFO
     */
    private function simulerFIFO($entrees, $quantite)
    {
        // Trier par date croissante (FIFO)
        usort($entrees, function($a, $b) {
            return strtotime($a['date_mouvement']) - strtotime($b['date_mouvement']);
        });
        
        return $this->simulerSortie($entrees, $quantite, 'FIFO');
    }

    /**
     * Simule une sortie LIFO
     */
    private function simulerLIFO($entrees, $quantite)
    {
        // Trier par date décroissante (LIFO)
        usort($entrees, function($a, $b) {
            return strtotime($b['date_mouvement']) - strtotime($a['date_mouvement']);
        });
        
        return $this->simulerSortie($entrees, $quantite, 'LIFO');
    }

    /**
     * Simule une sortie CMUP
     */
    private function simulerCMUP($entrees, $quantite)
    {
        // Calculer CMUP
        $valeurTotale = 0;
        $quantiteTotale = 0;
        
        foreach ($entrees as $entree) {
            $valeurTotale += $entree['quantite_disponible'] * $entree['prix_unitaire_mouvement'];
            $quantiteTotale += $entree['quantite_disponible'];
        }
        
        $cmup = $quantiteTotale > 0 ? $valeurTotale / $quantiteTotale : 0;
        
        $simulation = $this->simulerSortie($entrees, $quantite, 'CMUP');
        $simulation['cmup'] = $cmup;
        $simulation['cout_total'] = $quantite * $cmup;
        $simulation['cout_moyen'] = $cmup;
        
        return $simulation;
    }

    /**
     * Simule une sortie générique
     */
    private function simulerSortie($entreesTriees, $quantite, $methode)
    {
        $quantiteRestante = $quantite;
        $lotsUtilises = [];
        $coutTotal = 0;
        
        foreach ($entreesTriees as $entree) {
            if ($quantiteRestante <= 0) break;
            
            $quantiteAPrendre = min($quantiteRestante, $entree['quantite_disponible']);
            
            if ($quantiteAPrendre > 0) {
                $lotsUtilises[] = [
                    'reference_entree' => $entree['reference_document'],
                    'quantite_prelevee' => $quantiteAPrendre,
                    'prix_unitaire' => $entree['prix_unitaire_mouvement'],
                    'cout_ligne' => $quantiteAPrendre * $entree['prix_unitaire_mouvement'],
                    'date_entree' => $entree['date_mouvement'],
                    'depot' => $entree['depot_nom']
                ];
                
                $coutTotal += $quantiteAPrendre * $entree['prix_unitaire_mouvement'];
                $quantiteRestante -= $quantiteAPrendre;
            }
        }
        
        return [
            'methode' => $methode,
            'quantite_demandee' => $quantite,
            'quantite_simulee' => $quantite - $quantiteRestante,
            'quantite_restante' => $quantiteRestante,
            'lots_utilises' => $lotsUtilises,
            'cout_total' => $coutTotal,
            'cout_moyen' => ($quantite - $quantiteRestante) > 0 ? $coutTotal / ($quantite - $quantiteRestante) : 0,
            'nombre_lots_utilises' => count($lotsUtilises)
        ];
    }

    /**
     * Génère une recommandation basée sur les simulations
     */
    private function genererRecommendation($simulations)
    {
        if (empty($simulations)) return null;
        
        $couts = [];
        foreach ($simulations as $methode => $simulation) {
            if (isset($simulation['cout_total'])) {
                $couts[$methode] = $simulation['cout_total'];
            }
        }
        
        if (empty($couts)) return null;
        
        $methodeMoinsChere = array_search(min($couts), $couts);
        $methodePlusChere = array_search(max($couts), $couts);
        
        return [
            'methode_recommandee' => $methodeMoinsChere,
            'methode_plus_couteuse' => $methodePlusChere,
            'difference_cout' => $couts[$methodePlusChere] - $couts[$methodeMoinsChere],
            'economie_potentielle' => $couts[$methodePlusChere] - $couts[$methodeMoinsChere]
        ];
    }
}