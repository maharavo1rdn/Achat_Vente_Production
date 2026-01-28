<?php

namespace app\controllers;

use Exception;
use Flight;
use PDO;
use Dompdf\Dompdf;
use Dompdf\Options;

class ProformaFournisseurController
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

            $proformas = Flight::proformaFournisseurModel()->getAll($filters);
            Flight::json($proformas);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($id)
    {
        try {
            $proforma = Flight::proformaFournisseurModel()->getById($id);
            if (!$proforma) {
                Flight::json(['error' => 'Proforma non trouvée'], 404);
                return;
            }

            Flight::json($proforma);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        try {
            // Normalize request data to an array to avoid issues when Flight provides an object
            $data = Flight::request()->data;

            if (isset($data['details']) && is_string($data['details'])) {
                $data['details'] = json_decode($data['details'], true);
            }

            $newId = Flight::proformaFournisseurModel()->create($data);
            Flight::json(['id' => (int)$newId], 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id)
    {
        try {
            $data = Flight::request()->data;
            if (isset($data['details']) && is_string($data['details'])) {
                $data['details'] = json_decode($data['details'], true);
            }

            $result = Flight::proformaFournisseurModel()->update($id, $data);
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
            $result = Flight::proformaFournisseurModel()->delete($id);
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

            $personnel = Flight::personnelModel()->getById($userId);
            if (!$personnel) {
                Flight::json(['error' => 'Utilisateur introuvable'], 404);
                return;
            }

            if (!isset($personnel['niveau_acces']) || (int)$personnel['niveau_acces'] < 5) {
                throw new Exception('Droits insuffisants pour valider une proforma fournisseur');
            }

            $result = Flight::proformaFournisseurModel()->updateStatut($id, 3);
            if ($result) {
                Flight::json(['success' => true, 'message' => 'Proforma validée']);
            } else {
                Flight::json(['error' => 'Proforma non trouvée'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDetails($id)
    {
        try {
            $details = Flight::proformaFournisseurModel()->getDetails($id);
            Flight::json($details);
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

            $proformas = Flight::proformaFournisseurModel()->getAll($filters);

            $totalAll = 0;
            $totalValidated = 0;
            foreach ($proformas as $d) {
                $totalAll += (float)($d['montant_ttc'] ?? 0);
                if ((int)($d['statut_id'] ?? 0) === 3) $totalValidated += (float)($d['montant_ttc'] ?? 0);
            }

            ob_start();
            $exportProformas = $proformas;
            $exportMeta = [
                'filters' => $filters,
                'company' => $companyInfo,
                'total_all' => $totalAll,
                'total_validated' => $totalValidated,
            ];
            include __DIR__ . '/../views/pdf/proforma_fournisseur_list.php';
            $html = ob_get_clean();

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('proformas-fournisseur.pdf', ['Attachment' => 1]);
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

            $proforma = Flight::proformaFournisseurModel()->getById((int)$id);
            if (!$proforma) {
                Flight::json(['error' => 'Proforma non trouvée'], 404);
                return;
            }
            $details = Flight::proformaFournisseurModel()->getDetails($id);

            ob_start();
            $exportProforma = $proforma;
            $exportProforma['details'] = $details;
            $exportMeta = ['company' => $companyInfo];
            include __DIR__ . '/../views/pdf/proforma_fournisseur_detail.php';
            $html = ob_get_clean();

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('proforma-fournisseur-' . $id . '.pdf', ['Attachment' => 1]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
