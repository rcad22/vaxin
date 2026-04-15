<?php
session_start();
include '../../config.php';

// CYBERSECURITY
if (!isset($_SESSION['userid']) || ($_SESSION['role'] !== 'Administrator' && $_SESSION['role'] !== 'Support Staff')) {
    die("Unauthorized Access.");
}

// --- FILTER LOGIC ---
$selected_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');
$selected_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : 'all';

$period_text = "For the Year " . $selected_year;
$where_clauses = ["YEAR(v.vaccination_date) = '$selected_year'"];
if ($selected_month !== 'all') {
    $where_clauses[] = "MONTH(v.vaccination_date) = '$selected_month'";
    $month_name = date('F', mktime(0, 0, 0, $selected_month, 1, $selected_year));
    $period_text = "For the Month of $month_name $selected_year";
}
$where_sql = "WHERE " . implode(' AND ', $where_clauses);

// 1. FETCH DATA FOR MATRIX
$species_query = mysqli_query($conn, "SELECT species_id, species_name FROM species_tbl ORDER BY species_name ASC");
$all_species = [];
while ($sp = mysqli_fetch_assoc($species_query)) {
    $all_species[$sp['species_id']] = $sp['species_name'];
}

$brgy_query = mysqli_query($conn, "SELECT barangay_id, barangay_name FROM barangay_tbl ORDER BY barangay_name ASC");
$all_brgys = [];
while ($brgy = mysqli_fetch_assoc($brgy_query)) {
    $all_brgys[$brgy['barangay_id']] = $brgy['barangay_name'];
}

$matrix_data = [];
$matrix_sql = "SELECT a.barangay_id, a.species_id, COUNT(v.log_id) as vax_count 
               FROM vaccination_tbl v 
               JOIN animal_tbl a ON v.animal_id = a.record_id 
               $where_sql 
               GROUP BY a.barangay_id, a.species_id";
$matrix_res = mysqli_query($conn, $matrix_sql);
while ($row = mysqli_fetch_assoc($matrix_res)) {
    $matrix_data[$row['barangay_id']][$row['species_id']] = $row['vax_count'];
}

$total_vax = 0;
$total_sql = "SELECT COUNT(log_id) as total FROM vaccination_tbl v $where_sql";
$total_res = mysqli_query($conn, $total_sql);
if ($total_res)
    $total_vax = mysqli_fetch_assoc($total_res)['total'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Summary - VAX-IN MAO</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #fff;
            color: #000;
            padding: 20px;
        }

        /* LETTERHEAD */
        .report-header {
            text-align: center;
            margin-bottom: 30px;
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

        /* TABLE STYLES */
        .table {
            border-collapse: collapse !important;
            width: 100%;
            margin-bottom: 40px;
            font-size: 12px;
        }

        .table th,
        .table td {
            border: 1px solid #000 !important;
            padding: 6px;
        }

        .table th {
            background-color: #f0f0f0 !important;
            -webkit-print-color-adjust: exact;
            text-align: center;
            vertical-align: middle;
        }

        .table-tfoot th {
            background-color: #e9ecef !important;
            -webkit-print-color-adjust: exact;
            font-size: 14px;
        }

        /* SIGNATURES */
        .signature-section {
            margin-top: 60px;
            display: flex;
            justify-content: space-around;
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
            <h5 class="font-weight-bold text-uppercase">MUNICIPAL VACCINATION SUMMARY REPORT</h5>
            <p class="mb-0 font-weight-bold">Period: <?= $period_text ?></p>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th rowspan="2" width="20%">Barangay Name</th>
                    <th colspan="<?= count($all_species) ?>">Vaccinations by Animal Species</th>
                    <th rowspan="2" width="15%" style="background-color: #d1e7dd !important;">Total per Brgy</th>
                </tr>
                <tr>
                    <?php foreach ($all_species as $id => $name): ?>
                        <th><?= htmlspecialchars($name) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $grand_totals = array_fill_keys(array_keys($all_species), 0);

                foreach ($all_brgys as $b_id => $b_name):
                    $row_total = 0;
                    ?>
                    <tr>
                        <td class="font-weight-bold"><?= htmlspecialchars($b_name) ?></td>
                        <?php
                        foreach ($all_species as $s_id => $s_name):
                            $count = isset($matrix_data[$b_id][$s_id]) ? $matrix_data[$b_id][$s_id] : 0;
                            $row_total += $count;
                            $grand_totals[$s_id] += $count;
                            ?>
                            <td class="text-center"><?= $count > 0 ? number_format($count) : '-' ?></td>
                        <?php endforeach; ?>
                        <td class="text-center font-weight-bold" style="background-color: #f8f9fa !important;">
                            <?= number_format($row_total) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-tfoot">
                <tr>
                    <th class="text-right text-uppercase">Municipal Grand Totals:</th>
                    <?php foreach ($all_species as $s_id => $s_name): ?>
                        <th class="text-center"><?= number_format($grand_totals[$s_id]) ?></th>
                    <?php endforeach; ?>
                    <th class="text-center h5 mb-0" style="background-color: #d1e7dd !important;">
                        <?= number_format($total_vax) ?>
                    </th>
                </tr>
            </tfoot>
        </table>

        <div class="signature-section">
            <div class="sign-block">
                <p class="text-left small mb-4">Report Generated by:</p>
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
        window.onload = function () { setTimeout(function () { window.print(); }, 500); };
    </script>
</body>

</html>