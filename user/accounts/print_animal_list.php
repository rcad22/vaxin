<?php
session_start();
include '../../config.php';

// CYBERSECURITY
if (!isset($_SESSION['userid']) || ($_SESSION['role'] !== 'Administrator' && $_SESSION['role'] !== 'Support Staff')) {
    die("Unauthorized Access.");
}

$prepared_by_name = strtoupper($_SESSION['fullname']);
$prepared_by_role = $_SESSION['role'];

// --- FILTER LOGIC ---
$filter_brgy = isset($_GET['barangay_id']) ? $_GET['barangay_id'] : '';
$filter_species = isset($_GET['species_id']) ? $_GET['species_id'] : '';

$brgy_name_display = "ALL BARANGAYS";
$species_name_display = "ALL ANIMAL TYPES";

$where_clauses = [];
if ($filter_brgy) {
    $where_clauses[] = "a.barangay_id = '$filter_brgy'";
    $b_query = mysqli_query($conn, "SELECT barangay_name FROM barangay_tbl WHERE barangay_id = '$filter_brgy'");
    if ($b_row = mysqli_fetch_assoc($b_query))
        $brgy_name_display = strtoupper($b_row['barangay_name']);
}

if ($filter_species) {
    $where_clauses[] = "a.species_id = '$filter_species'";
    $s_query = mysqli_query($conn, "SELECT species_name FROM species_tbl WHERE species_id = '$filter_species'");
    if ($s_row = mysqli_fetch_assoc($s_query))
        $species_name_display = strtoupper($s_row['species_name']);
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

$sql = "SELECT a.*, b.barangay_name, s.species_name 
        FROM animal_tbl a 
        LEFT JOIN barangay_tbl b ON a.barangay_id = b.barangay_id 
        LEFT JOIN species_tbl s ON a.species_id = s.species_id 
        $where_sql 
        ORDER BY b.barangay_name ASC, a.owner_name ASC";
$result = mysqli_query($conn, $sql);
$total_rows = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masterlist of Animals</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #fff;
            color: #000;
            padding: 20px;
            font-size: 11px;
        }

        .report-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
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

        .header-text {
            text-align: center;
        }

        .title-text {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 3px;
        }

        .subtitle-text {
            font-size: 12px;
            margin-top: 0;
        }

        .meta-info {
            margin-bottom: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 11px;
            -webkit-print-color-adjust: exact;
        }

        .text-left {
            text-align: left !important;
            padding-left: 5px;
        }

        .signatories-container {
            margin-top: 50px;
            display: flex;
            justify-content: space-around;
        }

        .sig-block {
            width: 30%;
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

        @media print {
            @page {
                size: legal landscape;
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
        <button onclick="window.print()">Proceed to Print (Legal Landscape)</button>
    </div>

    <div class="report-header">
        <img src="logo.png" class="logo-left" alt="Logo" onerror="this.style.display='none'">
        <div class="header-text">
            <div class="subtitle-text">Republic of the Philippines</div>
            <div class="subtitle-text">Province of Marinduque</div>
            <div class="subtitle-text" style="font-weight:bold;">MUNICIPALITY OF MOGPOG</div>
            <div class="title-text" style="margin-top:10px;">MASTERLIST OF REGISTERED ANIMALS</div>
        </div>
        <img src="mogpoglogo.svg" class="logo-right" alt="Logo" onerror="this.style.display='none'">
    </div>

    <div class="meta-info">
        LOCATION: <?= $brgy_name_display ?> <br>
        FILTER: <?= $species_name_display ?> <br>
        TOTAL RECORDS: <?= number_format($total_rows) ?>
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%">NO.</th>
                <th width="12%">ANIMAL ID TAG</th>
                <th width="18%">OWNER'S FULL NAME</th>
                <th width="15%">BARANGAY</th>
                <th width="12%">PET NAME</th>
                <th width="10%">SPECIES</th>
                <th width="10%">BREED</th>
                <th width="5%">SEX</th>
                <th width="8%">COLOR</th>
                <th width="7%">STATUS</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $total_rows > 0) {
                $counter = 1;
                while ($row = mysqli_fetch_assoc($result)) {
                    $status = "Owned";
                    if ($row['is_stray'])
                        $status = "Stray";

                    echo "<tr>";
                    echo "<td>" . $counter++ . "</td>";
                    echo "<td style='font-weight:bold;'>" . htmlspecialchars($row['animal_id_tag']) . "</td>";
                    echo "<td class='text-left'>" . htmlspecialchars($row['owner_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['barangay_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['animal_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['species_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['breed']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['sex']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['color']) . "</td>";
                    echo "<td>" . $status . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='10' style='padding: 20px;'>No animals registered under these filters.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="signatories-container">
        <div class="sig-block">
            <div style="text-align: left;">Generated By:</div>
            <div class="sig-line"><?= $prepared_by_name ?></div>
            <div class="sig-role"><?= $prepared_by_role ?></div>
        </div>
        <div class="sig-block">
            <div style="text-align: left;">Noted By:</div>
            <div class="sig-line">MARNELLI R. NUÑEZ</div>
            <div class="sig-role">Agriculturist I / OIC-MAO</div>
        </div>
    </div>

</body>

</html>