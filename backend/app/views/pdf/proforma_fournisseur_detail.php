<?php
// $exportProforma : single proforma with details
$companyName = 'Mon Entreprise';
$generatedAt = date('d/m/Y H:i');
$details = $exportProforma['details'] ?? [];
$tot = 0;
foreach ($details as $it) $tot += (float)($it['quantite'] ?? 0) * (float)($it['prix_unitaire'] ?? 0);

// Logique de couleur pour le statut (si disponible, sinon gris par défaut)
$st = strtoupper($exportProforma['statut_libelle'] ?? '');
$badgeBg = '#f1f5f9';
$badgeColor = '#64748b';

if (strpos($st, 'VALID') !== false) {
    $badgeBg = '#dcfce7';
    $badgeColor = '#166534';
} elseif (strpos($st, 'ANNUL') !== false) {
    $badgeBg = '#fee2e2';
    $badgeColor = '#991b1b';
} elseif (strpos($st, 'EN COURS') !== false || strpos($st, 'BROUILLON') !== false) {
    $badgeBg = '#fef3c7';
    $badgeColor = '#92400e';
}
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Proforma #<?php echo htmlspecialchars($exportProforma['numero_proforma'] ?? $exportProforma['id']); ?></title>
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

        .text-indigo {
            color: #4338ca;
        }

        .uppercase {
            text-transform: uppercase;
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
            padding: 12px;
            margin-bottom: 20px;
        }

        .info-label {
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 3px;
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
                    <h1 style="margin:0; font-size:18px; color:#1e293b;">PROFORMA FOURNISSEUR</h1>
                    <div style="font-size:10px; color:#64748b; margin-top:4px;">
                        Réf: <span class="font-mono text-indigo font-bold"><?php echo htmlspecialchars($exportProforma['numero_proforma'] ?? ('#' . $exportProforma['id'])); ?></span>
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

    <!-- Informations Générales -->
    <table style="width: 100%; margin-bottom: 5px;">
        <tr>
            <!-- Bloc Gauche : Fournisseur -->
            <td style="width: 49%; vertical-align: top; padding-right: 1%;">
                <div class="info-box" style="height: 80px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 5px;">
                        <span style="font-size: 10px; font-weight: bold; color: #475569; text-transform: uppercase;">Fournisseur</span>
                    </div>
                    <div class="info-value" style="font-size: 12px;">
                        <?php echo htmlspecialchars($exportProforma['fournisseur'] ?? ($exportProforma['fournisseur_nom'] ?? '-')); ?>
                    </div>
                    <?php if (!empty($exportProforma['statut_libelle'])): ?>
                        <div style="margin-top: 8px;">
                            <span class="badge" style="background-color: <?php echo $badgeBg; ?>; color: <?php echo $badgeColor; ?>;">
                                <?php echo htmlspecialchars($exportProforma['statut_libelle']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </td>

            <!-- Bloc Droit : Contexte -->
            <td style="width: 49%; vertical-align: top; padding-left: 1%;">
                <div class="info-box" style="height: 80px;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding-bottom: 8px;">
                                <div class="info-label">Date Émission</div>
                                <div class="info-value"><?php echo htmlspecialchars(date('d/m/Y', strtotime($exportProforma['date_emission'] ?? 'now'))); ?></div>
                            </td>
                            <td style="padding-bottom: 8px;">
                                <div class="info-label">Date Validité</div>
                                <div class="info-value"><?php echo !empty($exportProforma['date_validite']) ? htmlspecialchars(date('d/m/Y', strtotime($exportProforma['date_validite']))) : '-'; ?></div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="info-label">Filiale / Entité</div>
                                <div class="info-value"><?php echo htmlspecialchars($exportProforma['filiale'] ?? ($exportProforma['filiale_nom'] ?? '-')); ?></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Tableau des articles -->
    <h3 style="font-size: 11px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-top: 10px; margin-bottom: 0;">Détails de la proposition</h3>

    <table class="lines-table">
        <thead>
            <tr>
                <th style="width:45%">Désignation</th>
                <th style="width:15%; text-align: center;">Quantité</th>
                <th style="width:20%" class="text-right">P.U. (Ar)</th>
                <th style="width:20%" class="text-right">Total (Ar)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($details)): ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding:20px; color:#94a3b8; font-style: italic;">
                        Aucun détail saisi pour cette proforma.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($details as $it):
                    $qte = (float)($it['quantite'] ?? 0);
                    $pu = (float)($it['prix_unitaire'] ?? 0);
                    $rowTotal = $qte * $pu;
                ?>
                    <?php $ref = $it['article_reference'] ?? ($it['reference'] ?? ''); $des = $it['article_designation'] ?? ($it['designation'] ?? ''); ?>
                    <tr>
                        <td>
                            <div style="font-weight: bold; color: #334155;">
                                <?php echo htmlspecialchars($des); ?>
                            </div>
                            <?php if (!empty($ref)): ?>
                                <div style="color: #64748b; font-size: 9px; font-family: monospace;">
                                    Réf: <?php echo htmlspecialchars($ref); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: bold;">
                                <?php echo htmlspecialchars($it['quantite'] ?? '-'); ?>
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
            <tr class="total-row total-final">
                <td>TOTAL TTC</td>
                <td class="text-right font-mono"><?php echo number_format($tot, 0, ',', ' '); ?> Ar</td>
            </tr>
        </table>
    </div>

    <!-- Note de bas de page -->
    <div style="margin-top: 40px; border-top: 1px dashed #cbd5e1; padding-top: 10px; font-size: 10px; color: #64748b; text-align: justify;">
        <strong>Note :</strong> Ce document est une facture proforma et ne peut servir de facture définitive. Elle est valable jusqu'à la date de validité indiquée ci-dessus.
    </div>

    <!-- Footer -->
    <footer>
        Document généré automatiquement via le système de gestion. Page <span class="page-number"></span>
    </footer>
</body>

</html>