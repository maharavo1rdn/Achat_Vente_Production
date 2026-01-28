<?php
// $exportDemande : single demande record with details in ['details']
$companyName = 'Mon Entreprise';
$generatedAt = date('d/m/Y H:i');
$details = $exportDemande['details'] ?? [];
$tot = 0;
foreach ($details as $it) $tot += (float)($it['quantite_demandee'] ?? 0) * (float)($it['prix_estime'] ?? 0);

// Logique de couleur pour le statut (Style Tailwind simulé)
$st = strtoupper($exportDemande['statut_libelle'] ?? '');
$badgeBg = '#f1f5f9';
$badgeColor = '#64748b'; // Default gray

if (strpos($st, 'VALID') !== false) {
    $badgeBg = '#dcfce7';
    $badgeColor = '#166534'; // Green
} elseif (strpos($st, 'ANNUL') !== false) {
    $badgeBg = '#fee2e2';
    $badgeColor = '#991b1b'; // Red
} elseif (strpos($st, 'ATTENTE') !== false || strpos($st, 'BROUILLON') !== false) {
    $badgeBg = '#fef3c7';
    $badgeColor = '#92400e'; // Amber
}
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Demande d'Achat #<?php echo htmlspecialchars($exportDemande['numero_da'] ?? $exportDemande['id']); ?></title>
    <style>
        /* Configuration globale */
        @page {
            margin: 1cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1e293b;
            /* Slate 800 */
            line-height: 1.4;
        }

        /* Header & Footer */
        header {
            margin-bottom: 25px;
        }

        footer {
            position: fixed;
            bottom: 0cm;
            left: 0cm;
            right: 0cm;
            height: 1cm;
            color: #94a3b8;
            text-align: center;
            font-size: 9px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }

        /* Utilitaires */
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }

        .text-gray {
            color: #64748b;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .text-xs {
            font-size: 9px;
        }

        /* Composants spécifiques */
        .accent-bar {
            width: 40px;
            height: 4px;
            background-color: #4f46e5;
            /* Indigo */
            margin-bottom: 8px;
            border-radius: 2px;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }

        /* Boîtes d'information (Méta données) */
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 20px;
        }

        .info-label {
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: #0f172a;
            font-weight: bold;
            font-size: 11px;
        }

        /* Tableau des lignes */
        .lines-table {
            width: 100%;
            border: 1px solid #e2e8f0;
            margin-top: 10px;
        }

        .lines-table th {
            background-color: #f1f5f9;
            color: #475569;
            text-transform: uppercase;
            font-size: 9px;
            font-weight: bold;
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #cbd5e1;
        }

        .lines-table td {
            padding: 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .lines-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        /* Totaux */
        .total-section {
            margin-top: 15px;
            width: 45%;
            margin-left: 55%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }

        .total-row td {
            padding: 8px;
        }

        .total-final {
            color: #4f46e5;
            font-size: 13px;
            font-weight: bold;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header>
        <table style="width:100%;">
            <tr>
                <td style="vertical-align: top;">
                    <div class="accent-bar"></div>
                    <h1 style="margin:0; font-size:18px; color:#1e293b;">DEMANDE D'ACHAT</h1>
                    <div style="font-size:10px; color:#64748b; margin-top:4px;">
                        Réf: <span class="font-mono text-indigo-700 font-bold"><?php echo htmlspecialchars($exportDemande['numero_da'] ?? ('#' . $exportDemande['id'])); ?></span>
                    </div>
                </td>
                <td style="text-align:right; vertical-align: top;">
                    <div class="font-bold" style="font-size:14px"><?php echo htmlspecialchars($exportMeta['company']['nom'] ?? $companyName); ?></div>
                    <?php if (!empty($exportMeta['company'])): ?>
                        <div class="text-gray" style="margin-top:2px; font-size: 10px;">
                            <?php echo htmlspecialchars(($exportMeta['company']['adresse'] ?? '') . ' • ' . ($exportMeta['company']['telephone'] ?? '')); ?>
                        </div>
                    <?php endif; ?>
                    <div class="text-gray" style="margin-top:4px; font-size: 9px;">Généré le <?php echo $generatedAt; ?></div>
                </td>
            </tr>
        </table>
    </header>

    <!-- Informations Générales (Grid simulée) -->
    <table style="width: 100%; margin-bottom: 5px;">
        <tr>
            <!-- Bloc Gauche : Qui ? -->
            <td style="width: 49%; vertical-align: top; padding-right: 1%;">
                <div class="info-box" style="height: 90px;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding-bottom: 8px;">
                                <div class="info-label">Demandeur</div>
                                <div class="info-value"><?php echo htmlspecialchars($exportDemande['demandeur_nom'] ?? '-'); ?></div>
                            </td>
                            <td style="padding-bottom: 8px; text-align: right;">
                                <div class="info-label">Statut</div>
                                <span class="badge" style="background-color: <?php echo $badgeBg; ?>; color: <?php echo $badgeColor; ?>;">
                                    <?php echo htmlspecialchars($exportDemande['statut_libelle'] ?? 'N/A'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="info-label">Motif / Justification</div>
                                <div class="info-value" style="font-weight: normal; font-style: italic;">
                                    <?php echo htmlspecialchars($exportDemande['motif_achat'] ?? 'Aucun motif spécifié'); ?>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>

            <!-- Bloc Droit : Où et Quand ? -->
            <td style="width: 49%; vertical-align: top; padding-left: 1%;">
                <div class="info-box" style="height: 90px;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding-bottom: 8px;">
                                <div class="info-label">Date Demande</div>
                                <div class="info-value"><?php echo htmlspecialchars(date('d/m/Y', strtotime($exportDemande['date_demande'] ?? 'now'))); ?></div>
                            </td>
                            <td style="padding-bottom: 8px;">
                                <div class="info-label">Date Souhaitée</div>
                                <div class="info-value"><?php echo !empty($exportDemande['date_souhaitee']) ? htmlspecialchars(date('d/m/Y', strtotime($exportDemande['date_souhaitee']))) : '-'; ?></div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-bottom: 8px;">
                                <div class="info-label">Entreprise</div>
                                <div class="info-value"><?php echo htmlspecialchars($exportDemande['entreprise_nom'] ?? '-'); ?></div>
                            </td>
                            <td style="padding-bottom: 8px;">
                                <div class="info-label">Dépôt Cible</div>
                                <div class="info-value"><?php echo htmlspecialchars($exportDemande['depot_nom'] ?? '-'); ?></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Tableau des articles -->
    <h3 style="font-size: 11px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-top: 10px; margin-bottom: 0;">Articles demandés</h3>

    <table class="lines-table">
        <thead>
            <tr>
                <th style="width:45%">Désignation</th>
                <th style="width:15%; text-align: center;">Quantité</th>
                <th style="width:20%" class="text-right">P.U. Est. (Ar)</th>
                <th style="width:20%" class="text-right">Total Est. (Ar)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($details)): ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding:20px; color:#94a3b8; font-style: italic;">
                        Aucun article n'a été ajouté à cette demande.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($details as $it):
                    $qte = (float)($it['quantite_demandee'] ?? 0);
                    $pu = (float)($it['prix_estime'] ?? 0);
                    $rowTotal = $qte * $pu;
                ?>
                    <?php $ref = $it['article_reference'] ?? ($it['reference'] ?? ''); $des = $it['article_designation'] ?? ($it['designation'] ?? ''); ?>
                    <tr>
                        <td>
                            <div style="font-weight: bold; color: #334155;"><?php echo htmlspecialchars($ref); ?></div>
                            <div style="color: #64748b; font-size: 10px;"><?php echo htmlspecialchars($des); ?></div>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: bold;">
                                <?php echo htmlspecialchars($qte); ?>
                            </span>
                        </td>
                        <td class="text-right font-mono" style="vertical-align: middle;">
                            <?php echo number_format($pu, 0, ',', ' '); ?>
                        </td>
                        <td class="text-right font-mono font-bold" style="vertical-align: middle; color: #4f46e5;">
                            <?php echo number_format($rowTotal, 0, ',', ' '); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Total Section -->
    <div class="total-section">
        <table style="width: 100%;">
            <tr class="total-row">
                <td class="text-gray text-xs">Nombre d'articles</td>
                <td class="text-right font-bold"><?php echo count($details); ?></td>
            </tr>
            <tr class="total-row total-final">
                <td>TOTAL ESTIMÉ</td>
                <td class="text-right font-mono"><?php echo number_format($tot, 0, ',', ' '); ?> Ar</td>
            </tr>
        </table>
    </div>

    <!-- Note de bas de page -->
    <div style="margin-top: 40px; border-top: 1px dashed #cbd5e1; padding-top: 10px; font-size: 10px; color: #64748b; text-align: justify;">
        <strong>Note :</strong> Ce document est une demande interne (proforma). Les prix indiqués sont des estimations et peuvent différer lors de la commande finale. Ce document doit être validé par le service achats avant tout engagement de dépense.
    </div>

    <!-- Footer -->
    <footer>
        Document généré automatiquement via le système de gestion. Page <span class="page-number"></span>
    </footer>
</body>

</html>