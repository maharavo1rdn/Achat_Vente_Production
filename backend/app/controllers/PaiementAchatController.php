<?php

namespace app\controllers;

use Exception;
use PDO;
use Flight;
use Dompdf\Dompdf;
use Dompdf\Options;

class PaiementAchatController
{
    public function getAll()
    {
        try {
            $filters = Flight::request()->query;
            $result = Flight::paiementAchatModel()->getAll($filters);
            Flight::json($result);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($id)
    {
        try {
            $paiement = Flight::paiementAchatModel()->getById($id);
            if ($paiement) {
                Flight::json($paiement);
            } else {
                Flight::json(['error' => 'Paiement non trouvé'], 404);
            }
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        try {
            $data = Flight::request()->data;
            $id = Flight::paiementAchatModel()->create($data);
            Flight::json(['id' => $id], 201);
        } catch (\InvalidArgumentException $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id)
    {
        try {
            $data = Flight::request()->data;
            $ok = Flight::paiementAchatModel()->update((int)$id, (array)$data);
            Flight::json(['success' => $ok]);
        } catch (\InvalidArgumentException $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function filters()
    {
        try {
            $entrepriseId = Flight::request()->query->entreprise_id ?? null;

            $modes = Flight::modePaiementModel()->getAll();

            $stmt = Flight::db()->prepare('SELECT id, code, libelle FROM statut ORDER BY id');
            $stmt->execute();
            $statuts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if ($entrepriseId) {
                $stmt2 = Flight::db()->prepare('SELECT id, libelle, solde_actuel FROM caisse WHERE entreprise_id = ?');
                $stmt2->execute([(int)$entrepriseId]);
                $caisses = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                $stmt3 = Flight::db()->prepare('SELECT id, nom, prenom FROM personnel WHERE entreprise_id = ?');
                $stmt3->execute([(int)$entrepriseId]);
                $personnels = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt2 = Flight::db()->prepare('SELECT id, libelle, solde_actuel FROM caisse');
                $stmt2->execute();
                $caisses = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                $stmt3 = Flight::db()->prepare('SELECT id, nom, prenom FROM personnel');
                $stmt3->execute();
                $personnels = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            }

            Flight::json(['modes' => $modes, 'statuts' => $statuts, 'caisses' => $caisses, 'personnels' => $personnels]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
    public function applyPayment($id)
    {
        try {
            $data = Flight::request()->data;
            $montant = $data['montant'] ?? null;
            if ($montant === null) throw new Exception('montant requis');

            $ok = Flight::paiementAchatModel()->applyPayment($id, $montant);
            Flight::json(['success' => $ok]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function validate($id)
    {
        try {
            $data = Flight::request()->data;
            $userId = $data['user_id'] ?? null;
            $caisseId = $data['caisse_id'] ?? null;
            $personnelId = $data['personnel_id'] ?? null;
            $libelle = $data['libelle'] ?? null;

            $ok = Flight::paiementAchatModel()->validate((int)$id, $userId, $caisseId, $personnelId, $libelle);
            Flight::json(['success' => $ok]);
        } catch (\InvalidArgumentException $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

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

            $paiements = Flight::paiementAchatModel()->getAll($filters);

            $totalAll = 0;
            $totalValidated = 0;
            foreach ($paiements as $p) {
                $totalAll += (float)($p['montant'] ?? 0);
                if ((int)($p['statut_id'] ?? 0) === 3) $totalValidated += (float)($p['montant'] ?? 0);
            }

            ob_start();
            $exportPaiements = $paiements;
            $exportMeta = [
                'filters' => $filters,
                'company' => $companyInfo,
                'total_all' => $totalAll,
                'total_validated' => $totalValidated,
            ];
            include __DIR__ . '/../views/pdf/paiements_achat_list.php';
            $html = ob_get_clean();

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('paiements-achat.pdf', ['Attachment' => 1]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportById($id)
    {
        try {
            $filters = Flight::request()->query;
            $entrepriseId = $filters->entreprise_id ?? null;

            // Get entreprise info if provided
            $companyInfo = null;
            if ($entrepriseId) {
                $stmtE = Flight::db()->prepare('SELECT id, nom, adresse, telephone FROM entreprise WHERE id = ?');
                $stmtE->execute([(int)$entrepriseId]);
                $companyInfo = $stmtE->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            $paiement = Flight::paiementAchatModel()->getById((int)$id);
            if (!$paiement) {
                Flight::json(['error' => 'Paiement non trouvé'], 404);
                return;
            }

            ob_start();
            $exportPaiement = $paiement;
            $exportMeta = ['company' => $companyInfo];
            include __DIR__ . '/../views/pdf/paiement_achat_detail.php';
            $html = ob_get_clean();

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('paiement-achat-' . $id . '.pdf', ['Attachment' => 1]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}
