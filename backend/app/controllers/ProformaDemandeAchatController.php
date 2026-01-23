<?php

namespace app\controllers;

use Exception;
use Flight;

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

    public function create()
    {
        try {
            $data = Flight::request()->data;

            if (isset($data['details']) && is_string($data['details'])) {
                $data['details'] = json_decode($data['details'], true);
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

    // Génère un proforma fournisseur à partir d'une demande validée
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

            // Construire les détails pour le proforma fournisseur
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

            // Numero unique simple
            $numero = 'PF' . date('Ym') . substr(md5(uniqid()), 0, 6);

            $payload = [
                'numero_proforma' => $numero,
                'date_emission' => date('Y-m-d'),
                'entreprise_fournisseur_id' => $fournisseurId,
                'entreprise_filiale_id' => (int)$demande['entreprise_id'],
                'personnel_id' => $userId,
                'statut_id' => 2, // EN_ATTENTE
                'montant_ht' => $total,
                'montant_ttc' => $total,
                'details' => $pfDetails,
                'proforma_demande_achat_id' => $id
            ];

            // $proformaId = Flight::achatModel()->createProforma($payload);

            Flight::json(['success' => true, 'payload' => $payload]);
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
