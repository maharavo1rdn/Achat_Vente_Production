<?php
// $exportDemandes : array of demandes
$companyName = 'Mon Entreprise';
$generatedAt = date('d/m/Y H:i');
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Liste des Demandes d'Achat</title>
    <style>
        /* Configuration de base */
        @page {
            margin: 1cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #1e293b;
            /* Slate 800 */
            line-height: 1.4;
        }

        /* En-tête et Pied de page */
        header {
            margin-bottom: 30px;
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

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }

        /* Couleurs */
        .text-gray-500 {
            color: #64748b;
        }

        .text-gray-400 {
            color: #94a3b8;
        }

        .text-indigo-700 {
            color: #4338ca;
        }

        /* Le Tableau de Données */
        .data-table {
            width: 100%;
            border: 1px solid #e2e8f0;
        }

        .data-table th {
            background-color: #f1f5f9;
            /* Slate 100 */
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.05em;
            padding: 8px 6px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }

        .data-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        /* Zébrure */
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Badges */
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }

        /* Totaux */
        .totals-box {
            width: 45%;
            margin-left: 55%;
            margin-top: 20px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }

        .totals-box td {
            padding: 8px;
            font-size: 11px;
        }

        .totals-row-total {
            border-top: 1px solid #cbd5e1;
            font-weight: bold;
            font-size: 12px;
            color: #0f172a;
        }

        /* Barre d'accentuation */
        .accent-bar {
            width: 40px;
            height: 4px;
            background-color: #4f46e5;
            margin-bottom: 10px;
            border-radius: 2px;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header>
        <table style="width: 100%;">
            <tr>
                <td style="vertical-align: top;">
                    <div class="accent-bar"></div>
                    <h1 style="margin: 0; font-size: 20px; color: #1e293b;">LISTE DES DEMANDES D'ACHAT</h1>
                    <div class="text-gray-500" style="font-size: 10px; margin-top: 5px;">
                        Date d'export : <?php echo $generatedAt; ?>
                    </div>
                    <?php if (!empty($exportMeta['filters'])): ?>
                        <div class="text-gray-400" style="font-size: 9px; margin-top: 2px; font-style: italic;">
                            Filtres : <?php echo htmlspecialchars(http_build_query((array)$exportMeta['filters'], '', ', ')); ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <div class="font-bold" style="font-size: 14px;"><?php echo htmlspecialchars($exportMeta['company']['nom'] ?? $companyName); ?></div>
                    <?php if (!empty($exportMeta['company'])): ?>
                        <div class="text-gray-500" style="margin-top: 2px;">
                            <?php echo htmlspecialchars(($exportMeta['company']['adresse'] ?? '') . ' ' . ($exportMeta['company']['telephone'] ?? '')); ?>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </header>

    <!-- Tableau -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">N° DA</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 18%;">Demandeur</th>
                <th style="width: 18%;">Entreprise</th>
                <th style="width: 14%;">Dépôt</th>
                <th style="width: 14%;" class="text-right">Montant Est.</th>
                <th style="width: 14%;" class="text-center">Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($exportDemandes)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8; font-style: italic;">
                        Aucune demande trouvée pour cette période.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($exportDemandes as $d):
                    // Logique de couleur (Uniformisée avec les autres fichiers)
                    $st = strtoupper($d['statut_libelle'] ?? '');
                    $badgeBg = '#f1f5f9';
                    $badgeColor = '#64748b';

                    if (strpos($st, 'VALID') !== false) {
                        $badgeBg = '#dcfce7';
                        $badgeColor = '#166534';
                    } elseif (strpos($st, 'ANNUL') !== false) {
                        $badgeBg = '#fee2e2';
                        $badgeColor = '#991b1b';
                    } elseif (strpos($st, 'ATTENTE') !== false || strpos($st, 'BROUILLON') !== false) {
                        $badgeBg = '#fef3c7';
                        $badgeColor = '#92400e';
                    }
                ?>
                    <tr>
                        <td class="font-bold text-indigo-700"><?php echo htmlspecialchars($d['numero_da'] ?? '#' . $d['id']); ?></td>
                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($d['date_demande'] ?? 'now'))); ?></td>
                        <td class="text-gray-500"><?php echo htmlspecialchars($d['demandeur_nom'] ?? '-'); ?></td>
                        <td class="text-gray-500"><?php echo htmlspecialchars($d['entreprise_nom'] ?? '-'); ?></td>
                        <td class="text-gray-500" style="font-size: 9px;"><?php echo htmlspecialchars($d['depot_nom'] ?? '-'); ?></td>
                        <td class="text-right font-mono font-bold">
                            <?php echo number_format((float)($d['montant_ttc'] ?? 0), 0, ',', ' '); ?> Ar
                        </td>
                        <td class="text-center">
                            <span class="badge" style="background-color: <?php echo $badgeBg; ?>; color: <?php echo $badgeColor; ?>;">
                                <?php echo htmlspecialchars($d['statut_libelle'] ?? ''); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Totaux -->
    <div class="totals-box">
        <table style="width: 100%;">
            <tr>
                <td class="text-gray-500">Volume Total Estimé</td>
                <td class="text-right text-gray-500"><?php echo number_format((float)$exportMeta['total_all'], 0, ',', ' '); ?> Ar</td>
            </tr>
            <tr class="totals-row-total">
                <td>TOTAL VALIDÉ</td>
                <td class="text-right text-indigo-700" style="font-size: 14px;">
                    <?php echo number_format((float)$exportMeta['total_validated'], 0, ',', ' '); ?> Ar
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <footer>
        Document généré automatiquement via le système de gestion. Page <span class="page-number"></span>
    </footer>
</body>

</html>