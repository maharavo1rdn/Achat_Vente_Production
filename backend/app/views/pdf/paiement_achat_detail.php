<?php
// $exportPaiement : single paiement record
$companyName = 'Mon Entreprise';
$generatedAt = date('d/m/Y H:i');

// Helpers pour le style conditionnel (Logique de présentation uniquement)
$status = strtoupper($exportPaiement['statut_libelle'] ?? '');
$statusColor = '#64748b'; // Default slate
$statusBg = '#f1f5f9';

if (strpos($status, 'VALID') !== false) {
    $statusColor = '#059669'; // Emerald 600
    $statusBg = '#d1fae5'; // Emerald 100
} elseif (strpos($status, 'ANNUL') !== false) {
    $statusColor = '#dc2626'; // Red 600
    $statusBg = '#fee2e2'; // Red 100
} elseif (strpos($status, 'ATTENTE') !== false || strpos($status, 'BROUILLON') !== false) {
    $statusColor = '#d97706'; // Amber 600
    $statusBg = '#fef3c7'; // Amber 100
}
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Paiement Fournisseur #<?php echo htmlspecialchars($exportPaiement['id'] ?? ''); ?></title>
    <style>
        /* Reset & Base fonts - DomPDF supporte mal les polices externes, on reste sur du standard */
        @page {
            margin: 0cm 0cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #1e293b;
            /* Slate 800 */
            margin-top: 3cm;
            margin-left: 2cm;
            margin-right: 2cm;
            margin-bottom: 2cm;
            background-color: #ffffff;
        }

        /* Header Fixe pour PDF */
        header {
            position: fixed;
            top: 0cm;
            left: 0cm;
            right: 0cm;
            height: 2cm;
            background-color: #f8fafc;
            /* Slate 50 */
            border-bottom: 1px solid #e2e8f0;
            padding: 0.5cm 2cm;
            line-height: 1.5;
        }

        /* Footer Fixe */
        footer {
            position: fixed;
            bottom: 0cm;
            left: 0cm;
            right: 0cm;
            height: 1cm;
            background-color: #f8fafc;
            color: #94a3b8;
            text-align: center;
            line-height: 1cm;
            font-size: 10px;
            border-top: 1px solid #e2e8f0;
        }

        /* Utilities (Simulating Tailwind) */
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .text-sm {
            font-size: 10px;
        }

        .text-lg {
            font-size: 14px;
        }

        .text-xl {
            font-size: 18px;
        }

        .text-2xl {
            font-size: 24px;
        }

        .text-gray-500 {
            color: #64748b;
        }

        .text-indigo-700 {
            color: #4338ca;
        }

        /* Layout Tables (remplace Flexbox/Grid) */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            vertical-align: top;
        }

        /* Components */
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 10px;
            display: inline-block;
        }

        .amount-box {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .data-table {
            width: 100%;
            margin-top: 20px;
            border: 1px solid #e2e8f0;
        }

        .data-table th {
            background-color: #f8fafc;
            color: #475569;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.05em;
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        /* Accent Bar */
        .accent-bar {
            width: 40px;
            height: 4px;
            background-color: #4f46e5;
            /* Indigo 600 */
            margin-bottom: 10px;
            border-radius: 2px;
        }
    </style>
</head>

<body>

    <!-- Header Block -->
    <header>
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <div style="font-weight: bold; font-size: 16px; color: #1e293b;">
                        <?php echo htmlspecialchars($exportMeta['company']['nom'] ?? $companyName); ?>
                    </div>
                    <?php if (!empty($exportMeta['company'])): ?>
                        <div style="color: #64748b; font-size: 10px; margin-top: 4px;">
                            <?php echo htmlspecialchars(($exportMeta['company']['adresse'] ?? '') . ' ' . ($exportMeta['company']['telephone'] ?? '')); ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td style="width: 50%; text-align: right;">
                    <div style="color: #94a3b8; font-size: 9px; text-transform: uppercase;">Date d'export</div>
                    <div style="font-weight: bold; color: #334155;"><?php echo $generatedAt; ?></div>
                </td>
            </tr>
        </table>
    </header>

    <footer>
        Document généré automatiquement via le système de gestion.
    </footer>

    <!-- Main Content -->
    <div>
        <!-- Title Section -->
        <table style="margin-bottom: 30px;">
            <tr>
                <td>
                    <div class="accent-bar"></div>
                    <h1 class="text-2xl font-bold text-indigo-700" style="margin: 0;">REÇU DE PAIEMENT</h1>
                    <p class="text-gray-500" style="margin: 5px 0 0 0;">
                        Réf: <?php echo htmlspecialchars($exportPaiement['numero_recu'] ?? ('#' . $exportPaiement['id'])); ?>
                    </p>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <span class="badge" style="background-color: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>;">
                        <?php echo htmlspecialchars($exportPaiement['statut_libelle'] ?? 'N/A'); ?>
                    </span>
                </td>
            </tr>
        </table>

        <!-- Hero Amount -->
        <div class="amount-box">
            <table style="width: 100%;">
                <tr>
                    <td style="vertical-align: middle;">
                        <span class="text-gray-500 uppercase text-sm" style="letter-spacing: 1px;">Montant Réglé</span>
                    </td>
                    <td style="text-align: right; vertical-align: middle;">
                        <span class="text-2xl font-bold" style="font-family: 'Courier New', Courier, monospace;">
                            <?php echo number_format((float)($exportPaiement['montant'] ?? 0), 0, ',', ' '); ?> Ar
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Details Grid (Using Tables for Layout) -->
        <table style="width: 100%; margin-bottom: 20px;">
            <tr>
                <!-- Column 1: Payment Details -->
                <td style="width: 48%; padding-right: 2%;">
                    <h3 class="text-sm uppercase text-gray-500 font-bold" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;">Détails Transaction</h3>
                    <table class="data-table" style="margin-top: 10px;">
                        <tr>
                            <th>Date Paiement</th>
                            <td class="text-right"><?php echo htmlspecialchars(date('d/m/Y', strtotime($exportPaiement['date_paiement'] ?? 'now'))); ?></td>
                        </tr>
                        <tr>
                            <th>Mode</th>
                            <td class="text-right"><?php echo htmlspecialchars($exportPaiement['mode_paiement_libelle'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Caisse (Source)</th>
                            <td class="text-right"><?php echo htmlspecialchars($exportPaiement['caisse_libelle'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Réf. Externe</th>
                            <td class="text-right"><?php echo htmlspecialchars($exportPaiement['reference_externe'] ?? '-'); ?></td>
                        </tr>
                    </table>
                </td>

                <!-- Spacer -->
                <td style="width: 4%;"></td>

                <!-- Column 2: Invoice Context -->
                <td style="width: 48%;">
                    <h3 class="text-sm uppercase text-gray-500 font-bold" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;">Contexte Facture</h3>
                    <table class="data-table" style="margin-top: 10px;">
                        <tr>
                            <th>Numéro Facture</th>
                            <td class="text-right font-bold"><?php echo htmlspecialchars($exportPaiement['numero_facture'] ?? ('#' . ($exportPaiement['facture_achat_id'] ?? ''))); ?></td>
                        </tr>
                        <tr>
                            <th>Fournisseur</th>
                            <td class="text-right"><?php echo htmlspecialchars($exportPaiement['fournisseur_nom'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Total Facture TTC</th>
                            <td class="text-right"><?php echo number_format((float)($exportPaiement['facture_montant_ttc'] ?? 0), 0, ',', ' '); ?> Ar</td>
                        </tr>
                        <tr>
                            <th>Reste à payer</th>
                            <td class="text-right" style="color: #dc2626;">
                                <?php echo number_format((float)($exportPaiement['facture_reste_a_payer'] ?? 0), 0, ',', ' '); ?> Ar
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Bottom Note -->
        <div style="margin-top: 30px; padding: 15px; background-color: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; color: #92400e; font-size: 11px;">
            <strong>Note :</strong> Ce document atteste du règlement partiel ou total de la facture référencée ci-dessus. Veuillez conserver ce reçu pour votre comptabilité.
        </div>

    </div>
</body>

</html>