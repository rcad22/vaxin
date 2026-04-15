<?php
session_start();
include '../../config.php';

// CYBERSECURITY: Admin at Support Staff lang
if (!isset($_SESSION['userid']) || ($_SESSION['role'] !== 'Administrator' && $_SESSION['role'] !== 'Support Staff')) {
    die("Unauthorized Access.");
}

$prepared_by_name = strtoupper($_SESSION['fullname']);
$prepared_by_role = $_SESSION['role'];
$report_date = date('F d, Y');

// MAIN QUERY: Same logic as the preview page
$sql = "SELECT 
            b.barangay_id, 
            b.barangay_name,
            COUNT(DISTINCT a.record_id) as total_animals,
            COUNT(DISTINCT v.animal_id) as vaccinated_animals
        FROM barangay_tbl b
        LEFT JOIN animal_tbl a ON b.barangay_id = a.barangay_id
        LEFT JOIN vaccination_tbl v ON a.record_id = v.animal_id AND v.status = 'Vaccinated'
        GROUP BY b.barangay_id, b.barangay_name
        ORDER BY b.barangay_name ASC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccination Coverage Report</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #fff;
            color: #000;
            padding: 20px;
            font-size: 12px;
        }

        /* HEADER SECTION */
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .logo-left {
            position: absolute;
            left: 50px;
            top: 0;
            width: 80px;
            height: 80px;
        }

        .logo-right {
            position: absolute;
            right: 50px;
            top: 0;
            width: 80px;
            height: 80px;
        }

        .header-text p {
            margin-bottom: 2px;
            font-size: 12px;
        }

        .header-text h4 {
            font-weight: bold;
            color: #1b4332;
            margin-top: 10px;
            font-family: 'Helvetica', serif;
        }

        .divider {
            border-top: 3px solid #1b4332;
            margin: 20px 0;
        }

        /* TABLE STYLES */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            -webkit-print-color-adjust: exact;
        }

        .text-left {
            text-align: left !important;
            padding-left: 10px;
        }

        .grand-total-row th {
            background-color: #d1e7dd !important;
            font-size: 12px;
            -webkit-print-color-adjust: exact;
        }

        /* SIGNATURES */
        .signatories-container {
            margin-top: 50px;
            display: flex;
            justify-content: space-around;
        }

        .sig-block {
            width: 35%;
            text-align: center;
        }

        .sig-line {
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin-top: 40px;
            text-transform: uppercase;
            font-size: 13px;
        }

        .sig-role {
            font-size: 11px;
            margin-top: 3px;
        }

        /* Print Specifics */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0.5in;
            }

            body {
                padding: 0;
            }

            .btn-print {
                display: none;
            }
        }

        .btn-print {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-print button {
            background-color: #1b4332;
            color: #fff;
            padding: 10px 20px;
            border: none;
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <div class="btn-print">
        <button onclick="window.print()">Proceed to Print (A4 Portrait)</button>
    </div>

    <div class="report-header">
        <img src="logo.png" class="logo-left" alt="Logo" onerror="this.style.display='none'">
        <div class="header-text">
            <p>Republic of the Philippines</p>
            <p>Province of Marinduque</p>
            <p style="font-weight:bold;">MUNICIPALITY OF MOGPOG</p>
            <h4>MUNICIPAL AGRICULTURE OFFICE</h4>
        </div>
        <img src="mogpoglogo.svg" class="logo-right" alt="Logo" onerror="this.style.display='none'">

        <div class="divider"></div>
        <h3 style="margin-bottom: 5px; text-transform: uppercase;">BARANGAY VACCINATION COVERAGE REPORT</h3>
        <p style="margin-top: 0; font-weight: bold;">As of <?= $report_date ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No.</th>
                <th width="25%">Barangay Name</th>
                <th width="15%">Total Registered Pets</th>
                <th width="15%">Total Vaccinated</th>
                <th width="15%">Total Unvaccinated</th>
                <th width="12%">Coverage %</th>
                <th width="13%">Risk Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $grand_total_pets = 0;
            $grand_total_vax = 0;
            $counter = 1;

            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $brgy_name = htmlspecialchars($row['barangay_name']);
                    $total = (int) $row['total_animals'];
                    $vax = (int) $row['vaccinated_animals'];
                    $unvax = $total - $vax;

                    // Add to Grand Totals
                    $grand_total_pets += $total;
                    $grand_total_vax += $vax;

                    // Calculate Percentages
                    if ($total > 0) {
                        $pct_vax = round(($vax / $total) * 100, 1);
                    } else {
                        $pct_vax = 0;
                    }

                    // Risk Status Text
                    $risk_status = "<span style='color: #e63946; font-weight: bold;'>CRITICAL</span>";
                    if ($pct_vax >= 70)
                        $risk_status = "<span style='color: #f6c23e; font-weight: bold;'>MODERATE</span>";
                    if ($pct_vax >= 90)
                        $risk_status = "<span style='color: #2d6a4f; font-weight: bold;'>SAFE</span>";
                    if ($total == 0)
                        $risk_status = "<span style='color: #6c757d;'>NO DATA</span>";

                    echo "<tr>";
                    echo "<td>" . $counter++ . "</td>";
                    echo "<td class='text-left font-weight-bold'>BRGY. " . strtoupper($brgy_name) . "</td>";
                    echo "<td>" . ($total > 0 ? number_format($total) : '-') . "</td>";
                    echo "<td>" . ($vax > 0 ? number_format($vax) : '-') . "</td>";
                    echo "<td>" . ($unvax > 0 ? number_format($unvax) : '-') . "</td>";
                    echo "<td><strong>" . $pct_vax . "%</strong></td>";
                    echo "<td>" . $risk_status . "</td>";
                    echo "</tr>";
                }

                // Compute Municipal Overall Average
                $overall_pct = 0;
                $grand_total_unvax = $grand_total_pets - $grand_total_vax;
                if ($grand_total_pets > 0) {
                    $overall_pct = round(($grand_total_vax / $grand_total_pets) * 100, 1);
                }

                // Print Grand Total Row
                echo "<tr class='grand-total-row'>";
                echo "<th colspan='2' class='text-right'>MUNICIPAL GRAND TOTAL:</th>";
                echo "<th>" . number_format($grand_total_pets) . "</th>";
                echo "<th>" . number_format($grand_total_vax) . "</th>";
                echo "<th>" . number_format($grand_total_unvax) . "</th>";
                echo "<th>" . $overall_pct . "%</th>";
                echo "<th>OVERALL</th>";
                echo "</tr>";

            } else {
                echo "<tr><td colspan='7' style='padding: 20px;'>No barangay data available.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="signatories-container">
        <div class="sig-block">
            <div style="text-align: left;">Prepared & Generated By:</div>
            <div class="sig-line"><?= $prepared_by_name ?></div>
            <div class="sig-role"><?= $prepared_by_role ?></div>
        </div>
        <div class="sig-block">
            <div style="text-align: left;">Noted & Verified By:</div>
            <div class="sig-line">MARNELLI R. NUÑEZ</div>
            <div class="sig-role">Agriculturist I / OIC-MAO</div>
        </div>
    </div>

</body>

</html>