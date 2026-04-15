<?php
session_start();
include '../../config.php';

// CYBERSECURITY: Admin at Support Staff lang ang pwedeng mag-generate ng reports
if (!isset($_SESSION['userid']) || ($_SESSION['role'] !== 'Administrator' && $_SESSION['role'] !== 'Support Staff')) {
    die("Unauthorized Access.");
}

// --- FILTER LOGIC (Hinugot mula sa URL parameters) ---
$selected_brgy = isset($_GET['barangay_id']) ? $_GET['barangay_id'] : 'all';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t');

// Build Query Conditions
$where_clauses = ["v.vaccination_date BETWEEN '$from_date' AND '$to_date'"];
if ($selected_brgy !== 'all') {
    $where_clauses[] = "a.barangay_id = '$selected_brgy'";
}
$where_sql = "WHERE " . implode(' AND ', $where_clauses);

// 1. GET TOTAL VACCINATIONS
$total_sql = "SELECT COUNT(v.log_id) as total FROM vaccination_tbl v 
              JOIN animal_tbl a ON v.animal_id = a.record_id $where_sql";
$total_res = mysqli_query($conn, $total_sql);
$total_vaccinations = mysqli_fetch_assoc($total_res)['total'];

// 2. GET BREAKDOWN BY SPECIES
$species_sql = "SELECT s.species_name, COUNT(v.log_id) as count 
                FROM vaccination_tbl v 
                JOIN animal_tbl a ON v.animal_id = a.record_id 
                JOIN species_tbl s ON a.species_id = s.species_id 
                $where_sql 
                GROUP BY s.species_name";
$species_res = mysqli_query($conn, $species_sql);
$species_breakdown = [];
while ($row = mysqli_fetch_assoc($species_res)) {
    $species_breakdown[$row['species_name']] = $row['count'];
}

// Get Selected Barangay Name
$brgy_name_display = "All Barangays (Municipality-Wide)";
if ($selected_brgy !== 'all') {
    $b_name_query = mysqli_query($conn, "SELECT barangay_name FROM barangay_tbl WHERE barangay_id = '$selected_brgy'");
    if ($b_row = mysqli_fetch_assoc($b_name_query)) {
        $brgy_name_display = "Brgy. " . $b_row['barangay_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Report - VAX-IN MAO</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Arial', sans-serif;
            /* Standard font for official documents */
            background-color: #fff;
            color: #000;
            padding: 20px;
        }

        /* LETTERHEAD STYLES */
        .report-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .logo-left {
            position: absolute;
            left: 150px;
            top: 0;
            width: 100px;
            height: 100px;
        }
        .logo-right {
            position: absolute;
            right: 150px;
            top: 0;
            width: 100px;
            height: 100px;
        }

        .header-text p {
            margin-bottom: 2px;
            font-size: 14px;
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

        /* SUMMARY STYLES */
        .summary-box {
            border: 1px solid #000;
            padding: 15px;
            margin-bottom: 30px;
        }

        .summary-title {
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        /* TABLE STYLES */
        .table {
            border-collapse: collapse !important;
            width: 100%;
            margin-bottom: 40px;
        }

        .table th,
        .table td {
            border: 1px solid #000 !important;
            padding: 8px;
            font-size: 12px;
        }

        .table th {
            background-color: #f0f0f0 !important;
            -webkit-print-color-adjust: exact;
            text-align: center;
        }

        /* SIGNATURE STYLES */
        .signature-section {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }

        .sign-block {
            width: 250px;
            text-align: center;
        }

        .sign-line {
            border-bottom: 1px solid #000;
            height: 40px;
            margin-bottom: 5px;
        }

        .sign-name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
            margin-bottom: 0;
        }

        .sign-title {
            font-size: 12px;
            color: #555;
        }

        /* Print Specific Adjustments */
        /* Print Specific Adjustments */
        @media print {

            /* Ginawa nating Landscape at A4 size ang default */
            @page {
                margin: 0.5in;
                size: A4 landscape;
            }

            body {
                padding: 0;
            }

            .btn-print {
                display: none;
            }

            /* Hide print button when printing */
        }
    </style>
</head>

<body>

    <div class="text-center mb-4 btn-print">
        <button onclick="window.print()" class="btn btn-success px-5 font-weight-bold"
            style="background-color: #1b4332;">
            Proceed to Print
        </button>
        <p class="text-muted small mt-2">Set paper size to A4 or Letter.</p>
    </div>

    <div id="document">

        <div class="report-header">
            <img src="logo.png" class="logo-left" alt="MAO Logo" onerror="this.style.display='none'">
            <div class="header-text">
                <p>Republic of the Philippines</p>
                <p>Province of Marinduque</p>
                <p class="font-weight-bold">MUNICIPALITY OF MOGPOG</p>
                <h4>MUNICIPAL AGRICULTURE OFFICE</h4>
            </div>
            <img src="mogpoglogo.svg" class="logo-right" alt="MAO Logo" onerror="this.style.display='none'">

            <div class="divider"></div>
            <h5 class="font-weight-bold text-uppercase">Vaccination Accomplishment Report</h5>
        </div>

        <div class="row mb-3">
            <div class="col-6">
                <p class="mb-1"><strong>Location Covered:</strong> <?= $brgy_name_display ?></p>
            </div>
            <div class="col-6 text-right">
                <p class="mb-1"><strong>Period:</strong> <?= date('M d, Y', strtotime($from_date)) ?> to
                    <?= date('M d, Y', strtotime($to_date)) ?>
                </p>
            </div>
        </div>

        <div class="summary-box">
            <div class="summary-title">Executive Summary</div>
            <div class="row text-center">
                <div class="col-md-3 border-right border-dark">
                    <h6 class="mb-0">Total Vaccinations</h6>
                    <h3 class="font-weight-bold mb-0"><?= number_format($total_vaccinations) ?></h3>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <?php if (empty($species_breakdown)): ?>
                            <div class="col-12">
                                <p class="mb-0 mt-2">No vaccination data available for this period.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($species_breakdown as $species => $count): ?>
                                <div class="col">
                                    <h6 class="mb-0"><?= htmlspecialchars($species) ?></h6>
                                    <h4 class="font-weight-bold mb-0"><?= number_format($count) ?></h4>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th width="10%">Date</th>
                    <th width="15%">Animal ID</th>
                    <th width="15%">Species & Breed</th>
                    <th width="20%">Owner's Name</th>
                    <th width="20%">Vaccine Administered</th>
                    <th width="20%">Vaccinator / Personnel</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $detail_sql = "SELECT v.vaccination_date, a.animal_id_tag, a.animal_name, a.owner_name, a.breed, 
                                s.species_name, vac.vaccine_name, u.fullname as vaccinator 
                                FROM vaccination_tbl v 
                                JOIN animal_tbl a ON v.animal_id = a.record_id 
                                JOIN species_tbl s ON a.species_id = s.species_id 
                                JOIN vaccine_tbl vac ON v.vaccine_id = vac.vaccine_id 
                                JOIN user_tbl u ON v.vaccinator_id = u.userid 
                                $where_sql 
                                ORDER BY v.vaccination_date ASC, a.owner_name ASC";
                $detail_res = mysqli_query($conn, $detail_sql);

                if (mysqli_num_rows($detail_res) > 0) {
                    while ($row = mysqli_fetch_assoc($detail_res)) {
                        echo "<tr>";
                        echo "<td class='text-center'>" . date('m/d/Y', strtotime($row['vaccination_date'])) . "</td>";
                        echo "<td class='font-weight-bold text-center'>" . htmlspecialchars($row['animal_id_tag']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['species_name']) . "<br><small>(" . htmlspecialchars($row['breed']) . ")</small></td>";
                        echo "<td>" . htmlspecialchars($row['owner_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['vaccine_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['vaccinator']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center py-4'>No vaccination records found for the selected criteria.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="signature-section">
            <div class="sign-block">
                <p class="text-left small mb-4">Prepared by:</p>
                <div class="sign-line"></div>
                <p class="sign-name"><?= htmlspecialchars($_SESSION['fullname']) ?></p>
                <p class="sign-title"><?= htmlspecialchars($_SESSION['role']) ?></p>
            </div>

            <div class="sign-block">
                <p class="text-left small mb-4">Noted and Verified by:</p>
                <div class="sign-line"></div>
                <p class="sign-name">MARNELLI R. NUÑEZ</p>
                <p class="sign-title">Agriculturist I / OIC-MAO</p>
            </div>
        </div>

    </div>
    <script>
        window.onload = function () {
            // Slight delay to ensure fonts and logo load before printing
            setTimeout(function () {
                window.print();
            }, 500);
        };
    </script>
</body>

</html>