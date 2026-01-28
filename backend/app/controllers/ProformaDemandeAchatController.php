<?php

namespace app\controllers;

use Exception;
use Flight;
use PDO;
use Dompdf\Dompdf;
use Dompdf\Options; 

class ProformaDemandeAchatController
{
    public function getAll()
    {
        try {
            $raw = Flight::request()->query;
            $filters = [];
            foreach ($raw as $k => $v) {
                if ($v === null || $v === '') continue;
                $filters[$k] = is_string($v) ? trim($v) : $v;
            }

            $demandes = Flight::proformaDemandeAchatModel()->getAll($filters);
            Flight::json($demandes);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($id)
    {
        try {
            $demande = Flight::proformaDemandeAchatModel()->getById($id);
            if (!$demande) {
                Flight::json(['error' => 'Demande d\'achat non trouvée'], 404);
                return;
            }

            $details = Flight::proformaDemandeAchatModel()->getDetails($id);
            $demande['details'] = $details;

            Flight::json($demande);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    // PDF export - liste
    public function exportList()
    {
        try {
            $filters = Flight::request()->query;
            $entrepriseId = $filters->entreprise_id ?? null;

            $companyInfo = null;
            if ($entrepriseId) {
                $stmtE = Flight::db()->prepare('SELECT id, nom, adresse, telephone FROM entreprise WHERE id = ?');
                $stmtE->execute([(int)$entrepriseId]);
                $companyInfo = $stmtE->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            $demandes = Flight::proformaDemandeAchatModel()->getAll($filters);

            $totalAll = 0;
            $totalValidated = 0;
            foreach ($demandes as $d) {
                $totalAll += (float)($d['montant_ttc'] ?? 0);
                if ((int)($d['statut_id'] ?? 0) === 3) $totalValidated += (float)($d['montant_ttc'] ?? 0);
            }

            ob_start();
            $exportDemandes = $demandes;
            $exportMeta = [
                'filters' => $filters,
                'company' => $companyInfo,
                'total_all' => $totalAll,
                'total_validated' => $totalValidated,
            ];
            include __DIR__ . '/../views/pdf/proforma_demande_achat_list.php';
            $html = ob_get_clean();

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('demandes-achat.pdf', ['Attachment' => 1]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    // PDF export - détail
    public function exportById($id)
    {
        try {
            $filters = Flight::request()->query;
            $entrepriseId = $filters->entreprise_id ?? null;

            $companyInfo = null;
            if ($entrepriseId) {
                $stmtE = Flight::db()->prepare('SELECT id, nom, adresse, telephone FROM entreprise WHERE id = ?');
                $stmtE->execute([(int)$entrepriseId]);
                $companyInfo = $stmtE->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            $demande = Flight::proformaDemandeAchatModel()->getById((int)$id);
            if (!$demande) {
                Flight::json(['error' => 'Demande non trouvée'], 404);
                return;
            }
            $details = Flight::proformaDemandeAchatModel()->getDetails($id);

            ob_start();
            $exportDemande = $demande;
            $exportDemande['details'] = $details;
            $exportMeta = ['company' => $companyInfo];
            include __DIR__ . '/../views/pdf/proforma_demande_achat_detail.php';
            $html = ob_get_clean();

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('demande-achat-' . $id . '.pdf', ['Attachment' => 1]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        try {
            $data = Flight::request()->data;

            if (isset($data['details']) && is_string($data['details'])) {
                $data['details'] = json_decode($data['details'], true);
            }

            // Allow force_create flag to bypass stock check
            $force = isset($data['force_create']) ? filter_var($data['force_create'], FILTER_VALIDATE_BOOLEAN) : false;

            if (!$force) {
                $depotId = $data['depot_cible_id'] ?? null;
                if ($depotId) {
                    $availability = Flight::proformaDemandeAchatModel()->checkStockAvailability($depotId, $data['details'] ?? []);
                    if (!$availability['all_clear']) {
                        // Return a warning payload to the client with availability details
                        Flight::json(['warning' => true, 'availability' => $availability], 200);
                        return;
                    }
                }
            }

            $newId = Flight::proformaDemandeAchatModel()->create($data);
            Flight::json(['id' => (int)$newId], 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id)
    {
        try {
            $data = Flight::request()->data;

            // Convert details if needed
            if (isset($data['details']) && is_string($data['details'])) {
                $data['details'] = json_decode($data['details'], true);
            }

            $result = Flight::proformaDemandeAchatModel()->update($id, $data);
            if ($result) {
                Flight::json(['success' => true]);
            } else {
                Flight::json(['error' => 'Aucune modification effectuée'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $result = Flight::proformaDemandeAchatModel()->delete($id);
            Flight::json(['success' => (bool)$result]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function valider($id)
    {
        try {
            $data = Flight::request()->data;
            $userId = isset($data['user_id']) ? (int)$data['user_id'] : null;

            if (!$userId) {
                Flight::json(['error' => 'user_id is required'], 400);
                return;
            }

            // Vérifier le niveau d'accès de l'utilisateur
            $personnel = Flight::personnelModel()->getById($userId);
            if (!$personnel) {
                Flight::json(['error' => 'Utilisateur introuvable'], 404);
                return;
            }

            if (!isset($personnel['niveau_acces']) || (int)$personnel['niveau_acces'] < 5) {
                throw new Exception('Droits insuffisants pour valider une demande');
            }

            $result = Flight::proformaDemandeAchatModel()->updateStatut($id, 3);
            if ($result) {
                Flight::json(['success' => true, 'message' => 'Demande validée']);
            } else {
                Flight::json(['error' => 'Demande non trouvée'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function annuler($id)
    {
        try {
            $result = Flight::proformaDemandeAchatModel()->updateStatut($id, 5);
            if ($result) {
                Flight::json(['success' => true, 'message' => 'Demande annulée']);
            } else {
                Flight::json(['error' => 'Demande non trouvée'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    // Génère un proforma fournisseur à partir d'une demande validée (tout en UN)
    public function genererProforma($id)
    {
        try {
            $data = Flight::request()->data;
            $userId = isset($data['user_id']) ? (int)$data['user_id'] : null;
            $fournisseurId = isset($data['entreprise_fournisseur_id']) ? (int)$data['entreprise_fournisseur_id'] : null;

            if (!$userId || !$fournisseurId) {
                Flight::json(['error' => 'user_id et entreprise_fournisseur_id sont requis'], 400);
                return;
            }

            $demande = Flight::proformaDemandeAchatModel()->getById($id);
            if (!$demande) {
                Flight::json(['error' => 'Demande non trouvée'], 404);
                return;
            }

            if ((int)$demande['statut_id'] !== 3) {
                Flight::json(['error' => 'Seules les demandes validées peuvent générer un proforma fournisseur'], 400);
                return;
            }

            $details = Flight::proformaDemandeAchatModel()->getDetails($id);
            if (!$details || count($details) === 0) {
                Flight::json(['error' => 'Aucun détail trouvé pour cette demande'], 400);
                return;
            }

            // Vérifier le niveau d'accès de l'utilisateur pour générer la proforma
            $personnel = Flight::personnelModel()->getById($userId);
            if (!$personnel) {
                Flight::json(['error' => 'Utilisateur introuvable'], 404);
                return;
            }
            if (!isset($personnel['niveau_acces']) || (int)$personnel['niveau_acces'] < 5) {
                Flight::json(['error' => 'Droits insuffisants pour générer une proforma fournisseur'], 403);
                return;
            }

            // Construire les détails et calculer le total
            $pfDetails = [];
            $total = 0;
            foreach ($details as $d) {
                $q = (float)$d['quantite_demandee'];
                $p = (float)$d['prix_estime'];
                $pfDetails[] = [
                    'article_id' => (int)$d['article_id'],
                    'quantite' => $q,
                    'prix_unitaire' => $p
                ];
                $total += $q * $p;
            }

            // Numéro unique
            $numero = 'PF' . date('Ym') . substr(md5(uniqid()), 0, 6);

            $payload = [
                'numero_proforma' => $numero,
                'date_emission' => date('Y-m-d'),
                'entreprise_fournisseur_id' => $fournisseurId,
                'entreprise_filiale_id' => (int)$demande['entreprise_id'],
                'personnel_id' => $userId,
                'statut_id' => 1, // Brouillon
                'montant_ttc' => $total,
                'details' => $pfDetails,
                'proforma_demande_achat_id' => $id
            ];

            $newId = Flight::proformaFournisseurModel()->create($payload);
            Flight::json(['success' => true, 'proforma_id' => (int)$newId], 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDetails($id)
    {
        try {
            $details = Flight::proformaDemandeAchatModel()->getDetails($id);
            Flight::json($details);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
