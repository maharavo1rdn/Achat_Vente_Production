<?php

namespace app\models;

use PDO;

class DashboardModel
{
    private $db;

    public function __construct($base_db)
    {
        $this->db = $base_db;
    }

    public function getStatistics(): array
    {
        error_log("DashboardModel::getStatistics called");

        $stats = [];

        $stats['total_articles'] = $this->getTotalArticles();
        $stats['total_clients'] = $this->getTotalClients();
        $stats['total_fournisseurs'] = $this->getTotalFournisseurs();
        $stats['total_personnel'] = $this->getTotalPersonnel();

        $currentMonth = date('Y-m');
        $stats['ventes_mois_courant'] = $this->getVentesTotalByMonth($currentMonth);
        $stats['achats_mois_courant'] = $this->getAchatsTotalByMonth($currentMonth);
        $stats['benefice_mois_courant'] = $stats['ventes_mois_courant'] - $stats['achats_mois_courant'];

        $stats['evolution_ventes'] = $this->getEvolutionVentes(12);
        $stats['evolution_achats'] = $this->getEvolutionAchats(12);

        $stats['top_articles_vendus'] = $this->getTopArticlesVendus(10);

        $stats['articles_rupture'] = $this->getArticlesEnRupture();

        $stats['ca_mensuel'] = $this->getCAMensuel(12);

        error_log("DashboardModel::getStatistics statistics calculated");
        return $stats;
    }

    public function getRecentVentes($limit = 10)
    {
        error_log("DashboardModel::getRecentVentes called with limit=$limit");

        $query = "
            SELECT
                fv.id,
                fv.numero_facture,
                fv.date_facture,
                fv.montant_ttc,
                fv.reste_a_payer,
                ec.nom as client_nom,
                efi.nom as filiale_nom,
                s.libelle as statut_libelle
            FROM facture_vente fv
            INNER JOIN entreprise ec ON fv.entreprise_client_id = ec.id
            INNER JOIN entreprise efi ON fv.entreprise_filiale_id = efi.id
            INNER JOIN statut s ON fv.statut_id = s.id
            ORDER BY fv.date_facture DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$limit]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("DashboardModel::getRecentVentes retrieved " . count($results) . " recent ventes");

        return $results;
    }

    public function getRecentAchats($limit = 10)
    {
        error_log("DashboardModel::getRecentAchats called with limit=$limit");

        $query = "
            SELECT
                fa.id,
                fa.numero_facture_fournisseur,
                fa.date_facture,
                fa.montant_ttc,
                fa.reste_a_payer,
                ef.nom as fournisseur_nom,
                efi.nom as filiale_nom,
                s.libelle as statut_libelle
            FROM facture_achat fa
            INNER JOIN entreprise ef ON fa.entreprise_fournisseur_id = ef.id
            INNER JOIN entreprise efi ON fa.entreprise_filiale_id = efi.id
            INNER JOIN statut s ON fa.statut_id = s.id
            ORDER BY fa.date_facture DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$limit]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("DashboardModel::getRecentAchats retrieved " . count($results) . " recent achats");

        return $results;
    }

    private function getTotalArticles(): int
    {
        $query = "SELECT COUNT(*) as total FROM article WHERE est_actif = true";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    private function getTotalClients(): int
    {
        $query = "SELECT COUNT(*) as total FROM entreprise WHERE type_entreprise = 'CLIENT' AND est_actif = true";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    private function getTotalFournisseurs(): int
    {
        $query = "SELECT COUNT(*) as total FROM entreprise WHERE type_entreprise = 'FOURNISSEUR' AND est_actif = true";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    private function getTotalPersonnel(): int
    {
        $query = "SELECT COUNT(*) as total FROM personnel WHERE est_actif = true";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    private function getVentesTotalByMonth(string $month): float
    {
        $query = "
            SELECT COALESCE(SUM(montant_ttc), 0) as total
            FROM facture_vente
            WHERE TO_CHAR(date_facture, 'YYYY-MM') = ?
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$month]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)$result['total'];
    }

    private function getAchatsTotalByMonth(string $month): float
    {
        $query = "
            SELECT COALESCE(SUM(montant_ttc), 0) as total
            FROM facture_achat
            WHERE TO_CHAR(date_facture, 'YYYY-MM') = ?
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$month]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)$result['total'];
    }

    private function getEvolutionVentes(int $months): array
    {
        $query = "
            SELECT
                TO_CHAR(date_facture, 'YYYY-MM') as mois,
                COALESCE(SUM(montant_ttc), 0) as total
            FROM facture_vente
            WHERE date_facture >= CURRENT_DATE - INTERVAL '" . $months . " months'
            GROUP BY TO_CHAR(date_facture, 'YYYY-MM')
            ORDER BY mois
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Remplir les mois manquants avec 0
        $evolution = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-$i months"));
            $found = false;
            foreach ($results as $result) {
                if ($result['mois'] === $date) {
                    $evolution[] = [
                        'mois' => $date,
                        'total' => (float)$result['total']
                    ];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $evolution[] = [
                    'mois' => $date,
                    'total' => 0.0
                ];
            }
        }

        return $evolution;
    }

    private function getEvolutionAchats(int $months): array
    {
        $query = "
            SELECT
                TO_CHAR(date_facture, 'YYYY-MM') as mois,
                COALESCE(SUM(montant_ttc), 0) as total
            FROM facture_achat
            WHERE date_facture >= CURRENT_DATE - INTERVAL '" . $months . " months'
            GROUP BY TO_CHAR(date_facture, 'YYYY-MM')
            ORDER BY mois
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Remplir les mois manquants avec 0
        $evolution = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-$i months"));
            $found = false;
            foreach ($results as $result) {
                if ($result['mois'] === $date) {
                    $evolution[] = [
                        'mois' => $date,
                        'total' => (float)$result['total']
                    ];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $evolution[] = [
                    'mois' => $date,
                    'total' => 0.0
                ];
            }
        }

        return $evolution;
    }

    private function getTopArticlesVendus(int $limit): array
    {
        $query = "
            SELECT
                a.id,
                a.reference,
                a.designation,
                COALESCE(SUM(fvd.quantite), 0) as quantite_vendue,
                COALESCE(SUM(fvd.quantite * fvd.prix_unitaire), 0) as montant_total
            FROM article a
            LEFT JOIN facture_vente_details fvd ON a.id = fvd.article_id
            LEFT JOIN facture_vente fv ON fvd.facture_vente_id = fv.id
            WHERE a.est_actif = true
            GROUP BY a.id, a.reference, a.designation
            ORDER BY quantite_vendue DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$limit]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convertir les valeurs numériques
        foreach ($results as &$result) {
            $result['quantite_vendue'] = (float)$result['quantite_vendue'];
            $result['montant_total'] = (float)$result['montant_total'];
        }

        return $results;
    }

    private function getArticlesEnRupture(): array
    {
        // Aggregate stock per article across depots and collect entreprise names
        $query = "
            SELECT
                a.id,
                a.reference,
                a.designation,
                COALESCE(SUM(s.quantite_actuelle), 0) AS stock_actuel,
                u.libelle AS unite,
                STRING_AGG(DISTINCT e.nom, ', ') AS entreprises
            FROM article a
            INNER JOIN unite u ON a.unite_id = u.id
            LEFT JOIN stock s ON a.id = s.article_id
            LEFT JOIN depot d ON s.depot_id = d.id
            LEFT JOIN site st ON d.site_id = st.id
            LEFT JOIN entreprise e ON st.entreprise_id = e.id
            WHERE a.est_actif = true
            GROUP BY a.id, a.reference, a.designation, u.libelle
            HAVING COALESCE(SUM(s.quantite_actuelle), 0) <= 10
            ORDER BY stock_actuel ASC, a.designation
            LIMIT 20
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $result['stock_actuel'] = (float)$result['stock_actuel'];
        }

        return $results;
    }

    private function getCAMensuel(int $months): array
    {
        $query = "
            SELECT
                TO_CHAR(date_facture, 'YYYY-MM') as mois,
                COALESCE(SUM(montant_ttc), 0) as ca_ventes,
                0 as ca_achats
            FROM facture_vente
            WHERE date_facture >= CURRENT_DATE - INTERVAL '" . $months . " months'
            GROUP BY TO_CHAR(date_facture, 'YYYY-MM')

            UNION ALL

            SELECT
                TO_CHAR(date_facture, 'YYYY-MM') as mois,
                0 as ca_ventes,
                COALESCE(SUM(montant_ttc), 0) as ca_achats
            FROM facture_achat
            WHERE date_facture >= CURRENT_DATE - INTERVAL '" . $months . " months'
            GROUP BY TO_CHAR(date_facture, 'YYYY-MM')

            ORDER BY mois
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agréger par mois
        $caMensuel = [];
        foreach ($results as $result) {
            $mois = $result['mois'];
            if (!isset($caMensuel[$mois])) {
                $caMensuel[$mois] = [
                    'mois' => $mois,
                    'ventes' => 0,
                    'achats' => 0,
                    'benefice' => 0
                ];
            }
            $caMensuel[$mois]['ventes'] += (float)$result['ca_ventes'];
            $caMensuel[$mois]['achats'] += (float)$result['ca_achats'];
            $caMensuel[$mois]['benefice'] = $caMensuel[$mois]['ventes'] - $caMensuel[$mois]['achats'];
        }

        $finalResults = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-$i months"));
            if (isset($caMensuel[$date])) {
                $finalResults[] = $caMensuel[$date];
            } else {
                $finalResults[] = [
                    'mois' => $date,
                    'ventes' => 0.0,
                    'achats' => 0.0,
                    'benefice' => 0.0
                ];
            }
        }

        return $finalResults;
    }
}