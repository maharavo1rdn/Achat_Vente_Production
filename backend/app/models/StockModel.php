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
                s.depot_id,
                s.quantite_actuelle,
                s.cmup_actuel,
                s.valeur_stock_total,
                s.date_maj,
                a.reference,
                a.designation as article,
                a.prix_achat_ref,
                a.prix_vente_ref,
                d.nom as depot,
                si.nom as site,
                e.nom as filiale,
                u.code as unite_code,
                u.libelle as unite,
                mvs.code as methode_valorisation_code,
                mvs.libelle as methode_valorisation
            FROM stock s
            INNER JOIN article a ON s.article_id = a.id
            INNER JOIN depot d ON s.depot_id = d.id
            INNER JOIN site si ON d.site_id = si.id
            INNER JOIN entreprise e ON si.entreprise_id = e.id
            INNER JOIN unite u ON a.unite_id = u.id
            INNER JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['entreprise_id'])) {
            $query .= " AND e.id = ?";
            $params[] = $filters['entreprise_id'];
        }

        if (isset($filters['depot_id'])) {
            $query .= " AND s.depot_id = ?";
            $params[] = $filters['depot_id'];
        }

        if (isset($filters['article_id'])) {
            $query .= " AND s.article_id = ?";
            $params[] = $filters['article_id'];
        }

        if (isset($filters['quantite_min'])) {
            $query .= " AND s.quantite_actuelle >= ?";
            $params[] = $filters['quantite_min'];
        }

        $query .= " ORDER BY e.nom, d.nom, a.designation";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("StockModel::getStock retrieved " . count($results) . " stock entries");

        return $results;
    }

    public function getStockByArticle($articleId, $depotId = null)
    {
        if ($articleId <= 0) {
            throw new InvalidArgumentException("L'ID d'article doit être un entier positif");
        }

        error_log("StockModel::getStockByArticle called with articleId=$articleId, depotId=" . ($depotId ?? 'null'));

        $query = "
            SELECT
                s.id,
                s.article_id,
                s.depot_id,
                s.quantite_actuelle,
                s.cmup_actuel,
                s.valeur_stock_total,
                s.date_maj,
                a.reference,
                a.designation,
                d.nom as depot_nom,
                mvs.code as methode_valorisation_code
            FROM stock s
            INNER JOIN article a ON s.article_id = a.id
            INNER JOIN depot d ON s.depot_id = d.id
            INNER JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
            WHERE s.article_id = ? AND s.depot_id = ?
        ";

        $params = [$articleId, $depotId];

       

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

        $query .= " ORDER BY ms.date_mouvement DESC LIMIT 50";

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
            $data['prix_unitaire'] ?? null,
            $data['article_id'],
            $data['personnel_id'],
            $data['reference_document'] ?? null,
            $data['depot_id'] ?? 1
        ]);

        $mouvementId = $this->db->lastInsertId();

        // Mettre à jour le stock
        $this->updateStockQuantite($data['article_id'], $data['depot_id'], $quantiteApres,$data['prix_unitaire']);

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
                d.nom as depot_nom,
                si.nom as site_nom,
                e.nom as entreprise_nom,
                p.nom as personnel_nom,
                p.prenom as personnel_prenom
            FROM mouvement_stock ms
            INNER JOIN depot d ON ms.depot_id = d.id
            INNER JOIN site si ON d.site_id = si.id
            INNER JOIN entreprise e ON si.entreprise_id = e.id
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
                s.depot_id,
                s.quantite_actuelle,
                s.date_maj,
                a.reference,
                a.designation as article,
                d.nom as depot,
                si.nom as site,
                e.nom as filiale,
                u.code as unite_code,
                u.libelle as unite
            FROM stock s
            INNER JOIN article a ON s.article_id = a.id
            INNER JOIN depot d ON s.depot_id = d.id
            INNER JOIN site si ON d.site_id = si.id
            INNER JOIN entreprise e ON si.entreprise_id = e.id
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

    /**
     * Récupérer le stock valorisé avec valeur comptable et valeur vente potentielle
     * Utilisé par le frontend pour l'affichage de valorisation
     */
    public function getStockValorise($filters = [])
    {
        error_log("StockModel::getStockValorise called with filters: " . json_encode($filters));

        $query = "
            SELECT
                s.id,
                s.article_id,
                s.depot_id,
                s.quantite_actuelle,
                COALESCE(s.cmup_actuel, 0) as cmup_actuel,
                COALESCE(s.valeur_stock_total, s.quantite_actuelle * COALESCE(s.cmup_actuel, a.prix_achat_ref, 0)) as valeur_comptable,
                COALESCE(s.quantite_actuelle * a.prix_vente_ref, 0) as valeur_vente_potentielle,
                s.date_maj,
                a.reference,
                a.designation,
                a.prix_achat_ref,
                a.prix_vente_ref,
                d.nom as depot,
                si.nom as site,
                e.nom as filiale,
                u.code as unite_code,
                mvs.code as methode_valorisation_code
            FROM stock s
            INNER JOIN article a ON s.article_id = a.id
            INNER JOIN depot d ON s.depot_id = d.id
            INNER JOIN site si ON d.site_id = si.id
            INNER JOIN entreprise e ON si.entreprise_id = e.id
            INNER JOIN unite u ON a.unite_id = u.id
            LEFT JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['filiale'])) {
            $query .= " AND e.nom = ?";
            $params[] = $filters['filiale'];
        }

        if (!empty($filters['site'])) {
            $query .= " AND si.nom = ?";
            $params[] = $filters['site'];
        }

        if (!empty($filters['depot'])) {
            $query .= " AND s.depot_id = ?";
            $params[] = $filters['depot'];
        }

        $query .= " ORDER BY e.nom, si.nom, d.nom, a.designation";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // S'assurer que toutes les valeurs numériques sont correctement typées
        foreach ($results as &$row) {
            $row['quantite_actuelle'] = (int)$row['quantite_actuelle'];
            $row['cmup_actuel'] = round((float)$row['cmup_actuel'], 2);
            $row['valeur_comptable'] = round((float)$row['valeur_comptable'], 2);
            $row['valeur_vente_potentielle'] = round((float)$row['valeur_vente_potentielle'], 2);
        }

        error_log("StockModel::getStockValorise retrieved " . count($results) . " stock entries");

        return $results;
    }

    /**
     * Récupérer le stock consolidé au niveau groupe (v_stock_consolide_groupe)
     */
    public function getStockConsolideGroupe()
    {
        error_log("StockModel::getStockConsolideGroupe called");

        $query = "SELECT * FROM v_stock_consolide_groupe ORDER BY valeur_totale_groupe DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("StockModel::getStockConsolideGroupe retrieved " . count($results) . " rows");

        return $results;
    }

    /**
     * Récupérer la structure organisationnelle (groupes, entreprises, sites, dépôts)
     */
    public function getStructureOrganisation()
    {
        error_log("StockModel::getStructureOrganisation called");

        $query = "SELECT DISTINCT groupe, entreprise, site_geo as site, depot_id, depot_logistique as depot FROM v_structure_organisation ORDER BY groupe, entreprise, site, depot";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("StockModel::getStructureOrganisation retrieved " . count($results) . " rows");

        return $results;
    }

    public function getDepotInfo($depotId)
    {
        error_log("StockModel::getDepotInfo called with depotId=$depotId");
        
        $query = "
            SELECT 
                d.id as depot_id,
                d.nom as depot,
                s.nom as site,
                e.nom as filiale,
                g.nom as groupe,
                mvs.code as methode_valorisation
            FROM depot d
            INNER JOIN site s ON d.site_id = s.id
            INNER JOIN entreprise e ON s.entreprise_id = e.id
            LEFT JOIN groupe g ON e.groupe_id = g.id
            LEFT JOIN (
                SELECT DISTINCT depot_id, methode_valorisation_stock_id 
                FROM stock 
                WHERE depot_id = ?
                LIMIT 1
            ) st ON st.depot_id = d.id
            LEFT JOIN methode_valorisation_stock mvs ON st.methode_valorisation_stock_id = mvs.id
            WHERE d.id = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$depotId, $depotId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            throw new InvalidArgumentException("Dépôt non trouvé");
        }
        
        return $result;
    }

    public function getStockByDepot($depotId)
    {
        error_log("StockModel::getStockByDepot called with depotId=$depotId");
        
        $query = "
            SELECT 
                s.id,
                s.article_id,
                s.depot_id,
                s.quantite_actuelle,
                s.cmup_actuel,
                s.valeur_stock_total,
                s.date_maj,
                a.reference,
                a.designation,
                u.code as unite
            FROM stock s
            INNER JOIN article a ON s.article_id = a.id
            INNER JOIN unite u ON a.unite_id = u.id
            WHERE s.depot_id = ? AND s.quantite_actuelle > 0
            ORDER BY a.designation
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$depotId]);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("StockModel::getStockByDepot retrieved " . count($results) . " articles");
        
        return $results;
    }

    public function getLotsByDepot($depotId)
    {
        error_log("StockModel::getLotsByDepot called with depotId=$depotId");
        
        $query = "
            SELECT 
                l.id,
                l.numero_lot,
                l.article_id,
                l.depot_id,
                l.date_entree,
                l.quantite_initiale,
                l.quantite_restante,
                l.prix_unitaire_achat,
                l.statut,
                a.reference as article_reference,
                a.designation as article_designation
            FROM lot_stock l
            INNER JOIN article a ON l.article_id = a.id
            WHERE l.depot_id = ? AND l.quantite_restante > 0
            ORDER BY a.designation, l.date_entree DESC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$depotId]);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("StockModel::getLotsByDepot retrieved " . count($results) . " lots");
        
        return $results;
    }

    public function getMouvementsByArticle($articleId, $depotId = null)
    {
        error_log("StockModel::getMouvementsByArticle called with articleId=$articleId, depotId=" . ($depotId ?? 'null'));
        
        $query = "
            SELECT 
                ms.id,
                ms.date_mouvement,
                ms.type_mouvement,
                ms.quantite_entree,
                ms.quantite_sortie,
                ms.prix_unitaire_mouvement,
                ms.reference_document,
                p.nom || ' ' || p.prenom as operateur
            FROM mouvement_stock ms
            INNER JOIN personnel p ON ms.personnel_id = p.id
            WHERE ms.article_id = ?
        ";
        
        $params = [$articleId];
        
        if ($depotId) {
            $query .= " AND ms.depot_id = ?";
            $params[] = $depotId;
        }
        
        $query .= " ORDER BY ms.date_mouvement DESC LIMIT 50";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("StockModel::getMouvementsByArticle retrieved " . count($results) . " movements");
        
        return $results;
    }

    private function validateMouvementData($data)
    {
        if (empty($data['type_mouvement'])) {
            throw new InvalidArgumentException("Le type de mouvement est obligatoire");
        }

        $typesValides = ['ACHAT', 'VENTE', 'INVENTAIRE', 'TRANSFERT', 'ENTREE_ACHAT', 'SORTIE_VENTE'];
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
     * Récupère le stock valorisé avec formatage professionnel et totaux
     * Supporte le groupement par site, dépôt, article
     * @param array $filters Filtres (entreprise_id, depot_id, seuil_critique, groupBy)
     * @return array Stock avec totaux et alertes
     */
    public function getStockValoriseComplet($filters = [])
    {
        error_log("StockModel::getStockValoriseComplet called with filters: " . json_encode($filters));

        $query = "
            SELECT
                s.id,
                s.article_id,
                s.depot_id,
                s.quantite_actuelle,
                s.cmup_actuel,
                s.valeur_stock_total,
                s.date_maj,
                a.reference,
                a.designation as article,
                a.prix_achat_ref,
                a.prix_vente_ref,
                d.nom as depot,
                si.id as site_id,
                si.nom as site,
                e.id as entreprise_id,
                e.nom as entreprise,
                g.id as groupe_id,
                g.nom as groupe,
                u.code as unite_code,
                u.libelle as unite,
                mvs.code as methode_valorisation_code,
                mvs.libelle as methode_valorisation,
                CASE 
                    WHEN s.quantite_actuelle <= 0 THEN 'RUPTURE'
                    WHEN s.quantite_actuelle <= COALESCE(?, 10) THEN 'BAS'
                    ELSE 'NORMAL'
                END as statut_stock
            FROM stock s
            INNER JOIN article a ON s.article_id = a.id
            INNER JOIN depot d ON s.depot_id = d.id
            INNER JOIN site si ON d.site_id = si.id
            INNER JOIN entreprise e ON si.entreprise_id = e.id
            LEFT JOIN groupe g ON e.groupe_id = g.id
            INNER JOIN unite u ON a.unite_id = u.id
            INNER JOIN methode_valorisation_stock mvs ON s.methode_valorisation_stock_id = mvs.id
            WHERE 1=1
        ";

        $seuilCritique = $filters['seuil_critique'] ?? 10;
        $params = [$seuilCritique];

        if (isset($filters['entreprise_id'])) {
            $query .= " AND e.id = ?";
            $params[] = $filters['entreprise_id'];
        }

        if (isset($filters['site_id'])) {
            $query .= " AND si.id = ?";
            $params[] = $filters['site_id'];
        }

        if (isset($filters['depot_id'])) {
            $query .= " AND s.depot_id = ?";
            $params[] = $filters['depot_id'];
        }

        if (isset($filters['methode_valorisation'])) {
            $query .= " AND mvs.code = ?";
            $params[] = $filters['methode_valorisation'];
        }

        if (isset($filters['statut_stock'])) {
            switch ($filters['statut_stock']) {
                case 'RUPTURE':
                    $query .= " AND s.quantite_actuelle <= 0";
                    break;
                case 'BAS':
                    $query .= " AND s.quantite_actuelle > 0 AND s.quantite_actuelle <= ?";
                    $params[] = $seuilCritique;
                    break;
                case 'NORMAL':
                    $query .= " AND s.quantite_actuelle > ?";
                    $params[] = $seuilCritique;
                    break;
            }
        }

        $query .= " ORDER BY e.nom, si.nom, d.nom, a.designation";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les montants et calculer les totaux
        $totaux = [
            'quantite_totale' => 0,
            'valeur_totale' => 0,
            'valeur_vente_potentielle' => 0,
            'articles_rupture' => 0,
            'articles_stock_bas' => 0,
            'articles_normaux' => 0
        ];
        
        $totauxParSite = [];
        $totauxParDepot = [];
        
        foreach ($stocks as &$stock) {
            // Arrondir les valeurs monétaires à 2 décimales
            $stock['cmup_actuel'] = round((float)$stock['cmup_actuel'], 2);
            $stock['valeur_stock_total'] = round((float)$stock['valeur_stock_total'], 2);
            $stock['quantite_actuelle'] = (int)$stock['quantite_actuelle'];
            
            // Calculer la valeur de vente potentielle
            $stock['valeur_vente_potentielle'] = round(
                $stock['quantite_actuelle'] * (float)$stock['prix_vente_ref'], 2
            );
            
            // Marge potentielle
            $stock['marge_potentielle'] = round(
                $stock['valeur_vente_potentielle'] - $stock['valeur_stock_total'], 2
            );
            
            // Mettre à jour les totaux globaux
            $totaux['quantite_totale'] += $stock['quantite_actuelle'];
            $totaux['valeur_totale'] += $stock['valeur_stock_total'];
            $totaux['valeur_vente_potentielle'] += $stock['valeur_vente_potentielle'];
            
            switch ($stock['statut_stock']) {
                case 'RUPTURE':
                    $totaux['articles_rupture']++;
                    break;
                case 'BAS':
                    $totaux['articles_stock_bas']++;
                    break;
                default:
                    $totaux['articles_normaux']++;
            }
            
            // Totaux par site
            $siteKey = $stock['site_id'];
            if (!isset($totauxParSite[$siteKey])) {
                $totauxParSite[$siteKey] = [
                    'site_id' => $siteKey,
                    'site' => $stock['site'],
                    'entreprise' => $stock['entreprise'],
                    'valeur_stock' => 0,
                    'valeur_vente' => 0,
                    'nb_articles' => 0
                ];
            }
            $totauxParSite[$siteKey]['valeur_stock'] += $stock['valeur_stock_total'];
            $totauxParSite[$siteKey]['valeur_vente'] += $stock['valeur_vente_potentielle'];
            $totauxParSite[$siteKey]['nb_articles']++;
            
            // Totaux par dépôt
            $depotKey = $stock['depot_id'];
            if (!isset($totauxParDepot[$depotKey])) {
                $totauxParDepot[$depotKey] = [
                    'depot_id' => $depotKey,
                    'depot' => $stock['depot'],
                    'site' => $stock['site'],
                    'valeur_stock' => 0,
                    'valeur_vente' => 0,
                    'nb_articles' => 0
                ];
            }
            $totauxParDepot[$depotKey]['valeur_stock'] += $stock['valeur_stock_total'];
            $totauxParDepot[$depotKey]['valeur_vente'] += $stock['valeur_vente_potentielle'];
            $totauxParDepot[$depotKey]['nb_articles']++;
        }
        
        // Arrondir les totaux
        $totaux['valeur_totale'] = round($totaux['valeur_totale'], 2);
        $totaux['valeur_vente_potentielle'] = round($totaux['valeur_vente_potentielle'], 2);
        $totaux['marge_potentielle_totale'] = round(
            $totaux['valeur_vente_potentielle'] - $totaux['valeur_totale'], 2
        );
        
        foreach ($totauxParSite as &$site) {
            $site['valeur_stock'] = round($site['valeur_stock'], 2);
            $site['valeur_vente'] = round($site['valeur_vente'], 2);
        }
        
        foreach ($totauxParDepot as &$depot) {
            $depot['valeur_stock'] = round($depot['valeur_stock'], 2);
            $depot['valeur_vente'] = round($depot['valeur_vente'], 2);
        }

        error_log("StockModel::getStockValoriseComplet retrieved " . count($stocks) . " stock entries");

        return [
            'stocks' => $stocks,
            'totaux_globaux' => $totaux,
            'totaux_par_site' => array_values($totauxParSite),
            'totaux_par_depot' => array_values($totauxParDepot),
            'filtres_appliques' => $filters,
            'date_extraction' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Rend la méthode updateStockQuantite publique pour utilisation dans MouvementStockModel
     */
    public function updateStockQuantite($articleId, $depotId, $nouvelleQuantite, $prixUnitaire = null)
    {
        error_log("StockModel::updateStockQuantite called with articleId=$articleId, depotId=$depotId, nouvelleQuantite=$nouvelleQuantite");

        // Vérifier si l'entrée stock existe
        $stockExistant = $this->getStockByArticle($articleId, $depotId);

        if ($stockExistant) {
            // Mettre à jour
            $query = "UPDATE stock SET quantite_actuelle = ?, date_maj = NOW() WHERE article_id = ? AND depot_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$nouvelleQuantite, $articleId, $depotId]);
        } else {
            // Créer nouvelle entrée
            $query = "INSERT INTO stock (article_id, depot_id, quantite_actuelle, cmup_actuel, valeur_stock_total, methode_valorisation_stock_id) VALUES (?, ?, ?, ?, ?, 2)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$articleId, $depotId, $nouvelleQuantite, $prixUnitaire, ($prixUnitaire ?? 0) * $nouvelleQuantite]);
        }

        error_log("StockModel::updateStockQuantite stock updated");
    }
}