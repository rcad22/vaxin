<?php
include '../../config.php';

// --- FILTER LOGIC ---
$selected_brgy = isset($_GET['barangay_id']) ? $_GET['barangay_id'] : 'all';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01'); // Default: Start of current month
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t');   // Default: End of current month

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

// Get Selected Barangay Name for the Report Header
$brgy_name_display = "All Barangays";
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
    <?php include './../template/header.php' ?>
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />


</head>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php' ?>
        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <style>
                    .select2-container .select2-selection--single {
                        height: 38px !important;
                        border: 2px solid #e9ecef;
                        border-radius: 8px;
                    }

                    .select2-container--default .select2-selection--single .select2-selection__rendered {
                        line-height: 36px;
                        color: #495057;
                    }

                    /* SUMMARY CARDS */
                    .summary-card {
                        border-left: 4px solid #2d6a4f;
                        background: #fff;
                        border-radius: 10px;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
                    }

                    /* OFFICIAL REPORT LETTERHEAD (Hidden on screen, visible on print) */
                    .report-header {
                        display: none;
                        text-align: center;
                        margin-bottom: 30px;
                    }

                    .report-footer {
                        display: none;
                        margin-top: 50px;
                    }

                    .signature-line {
                        border-top: 1px solid #000;
                        width: 250px;
                        margin: 0 auto;
                        margin-top: 40px;
                        padding-top: 5px;
                    }

                    /* PRINT CSS MAGIC */
                    @media print {
                        body * {
                            visibility: hidden;
                        }

                        /* Hide everything by default */
                        #printableArea,
                        #printableArea * {
                            visibility: visible;
                        }

                        /* Show only the report area */
                        #printableArea {
                            position: absolute;
                            left: 0;
                            top: 0;
                            width: 100%;
                            padding: 20px;
                        }

                        /* Hide UI elements inside the printable area */
                        .no-print,
                        .dataTables_filter,
                        .dataTables_info,
                        .dataTables_paginate,
                        .dataTables_length {
                            display: none !important;
                        }

                        /* Show Letterhead and Footer */
                        .report-header {
                            display: block;
                        }

                        .report-footer {
                            display: flex;
                            justify-content: space-between;
                        }

                        /* Adjust table for printing */
                        .table {
                            border-collapse: collapse !important;
                            width: 100%;
                        }

                        .table th,
                        .table td {
                            border: 1px solid #000 !important;
                            padding: 8px !important;
                            color: #000 !important;
                        }

                        .table th {
                            background-color: #f0f0f0 !important;
                            -webkit-print-color-adjust: exact;
                        }

                        .badge {
                            border: none !important;
                            color: #000 !important;
                            padding: 0 !important;
                        }

                        /* Remove card shadows for clean print */
                        .card {
                            border: none !important;
                            box-shadow: none !important;
                        }

                        .summary-card {
                            border: 1px solid #000 !important;
                            margin-bottom: 20px;
                        }
                    }
                </style>
                <div class="container-fluid pt-4">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4 no-print">
                        <h1 class="h3 mb-0 fw-bold" style="color: #1b4332 !important;"><i
                                class="fas fa-file-contract mr-2"></i>Barangay Vaccination Report</h1>

                        <?php

                        $print_url = "print_barangay_report.php?barangay_id=$selected_brgy&from_date=$from_date&to_date=$to_date";

                        ?>
                        <a href="<?= $print_url ?>" target="_blank"
                            class="btn text-white shadow-sm px-4 font-weight-bold"
                            style="background-color: #1b4332; border-radius: 8px;">
                            <i class="fas fa-print me-2"></i> Print Official Report
                        </a>
                    </div>

                    <div class="card shadow mb-4 no-print" style="border-radius: 15px;">
                        <div class="card-body bg-white" style="border-radius: 15px;">
                            <form method="GET" action="">
                                <div class="row align-items-end">
                                    <div class="col-md-4 mb-3">
                                        <label class="small font-weight-bold" style="color: #1b4332;">Select
                                            Barangay</label>
                                        <select name="barangay_id" class="form-control select2-single">
                                            <option value="all" <?= $selected_brgy == 'all' ? 'selected' : '' ?>>All
                                                Barangays (Municipality Summary)</option>
                                            <?php
                                            $b_query = mysqli_query($conn, "SELECT * FROM barangay_tbl ORDER BY barangay_name ASC");
                                            while ($b = mysqli_fetch_assoc($b_query)) {
                                                $sel = ($selected_brgy == $b['barangay_id']) ? 'selected' : '';
                                                echo "<option value='{$b['barangay_id']}' $sel>{$b['barangay_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="small font-weight-bold" style="color: #1b4332;">Date From</label>
                                        <input type="date" name="from_date" class="form-control"
                                            value="<?= $from_date ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="small font-weight-bold" style="color: #1b4332;">Date To</label>
                                        <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>"
                                            required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <button type="submit" class="btn w-100 text-white font-weight-bold"
                                            style="background-color: #2d6a4f;">
                                            <i class="fas fa-filter mr-1"></i> Generate
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="printableArea">

                        <div class="report-header">
                            <img src="img/logo.png"
                                style="width: 80px; height: 80px; position: absolute; left: 50px; top: 0;">
                            <p class="mb-0" style="font-family: 'Times New Roman', serif;">Republic of the Philippines
                            </p>
                            <p class="mb-0" style="font-family: 'Times New Roman', serif;">Province of Marinduque</p>
                            <p class="mb-0 font-weight-bold" style="font-family: 'Times New Roman', serif;">MUNICIPALITY
                                OF MOGPOG</p>
                            <h4 class="font-weight-bold mt-2" style="color: #1b4332;">MUNICIPAL AGRICULTURE OFFICE</h4>
                            <hr style="border-top: 2px solid #1b4332; margin-top: 15px; margin-bottom: 20px;">
                            <h5 class="font-weight-bold text-uppercase">VACCINATION ACCOMPLISHMENT REPORT</h5>
                            <p class="mb-0"><strong>Location:</strong> <?= $brgy_name_display ?></p>
                            <p class="mb-0"><strong>Period Covered:</strong>
                                <?= date('F d, Y', strtotime($from_date)) ?> to
                                <?= date('F d, Y', strtotime($to_date)) ?>
                            </p>
                        </div>

                        <div class="row mb-4 mt-3">
                            <div class="col-md-4">
                                <div class="summary-card p-3 text-center">
                                    <h6 class="text-muted font-weight-bold text-uppercase mb-1">Total Animals Vaccinated
                                    </h6>
                                    <h2 class="font-weight-bold" style="color: #1b4332;">
                                        <?= number_format($total_vaccinations) ?>
                                    </h2>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="summary-card p-3">
                                    <h6 class="text-muted font-weight-bold text-uppercase mb-2">Breakdown by Species
                                    </h6>
                                    <div class="row text-center">
                                        <?php if (empty($species_breakdown)): ?>
                                            <div class="col-12">
                                                <p class="text-muted mb-0">No records found for this period.</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($species_breakdown as $species => $count): ?>
                                                <div class="col">
                                                    <h4 class="font-weight-bold mb-0" style="color: #2d6a4f;">
                                                        <?= number_format($count) ?>
                                                    </h4>
                                                    <small
                                                        class="font-weight-bold text-dark"><?= htmlspecialchars($species) ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm mb-4">
                            <div class="card-header py-3 bg-white no-print">
                                <h6 class="m-0 font-weight-bold" style="color: #1b4332;">Detailed Vaccination Log</h6>
                            </div>
                            <div class="card-body p-0 p-md-3">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover w-100" id="reportTable">
                                        <thead style="background-color: #f8f9fa; color: #1b4332;">
                                            <tr>
                                                <th>Date</th>
                                                <th>Animal ID</th>
                                                <th>Pet/Tag Name</th>
                                                <th>Species & Breed</th>
                                                <th>Owner's Name</th>
                                                <th>Vaccine Administered</th>
                                                <th>Vaccinator</th>
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
                                                    echo "<td>" . date('M d, Y', strtotime($row['vaccination_date'])) . "</td>";
                                                    echo "<td class='font-weight-bold'>" . htmlspecialchars($row['animal_id_tag']) . "</td>";
                                                    echo "<td>" . (htmlspecialchars($row['animal_name']) ?: '<i class="text-muted">N/A</i>') . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['species_name']) . " <small class='text-muted'>(" . htmlspecialchars($row['breed']) . ")</small></td>";
                                                    echo "<td>" . htmlspecialchars($row['owner_name']) . "</td>";
                                                    echo "<td class='font-weight-bold text-success'>" . htmlspecialchars($row['vaccine_name']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['vaccinator']) . "</td>";
                                                    echo "</tr>";
                                                }
                                            } 
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="report-footer">
                            <div class="text-center">
                                <p class="mb-0 small">Prepared by:</p>
                                <div class="signature-line">
                                    <h6 class="font-weight-bold mb-0 text-uppercase">
                                        <?= htmlspecialchars($_SESSION['fullname']) ?>
                                    </h6>
                                    <p class="small text-muted"><?= htmlspecialchars($_SESSION['role']) ?></p>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="mb-0 small">Noted by:</p>
                                <div class="signature-line">
                                    <h6 class="font-weight-bold mb-0 text-uppercase">MUNICIPAL AGRICULTURIST</h6>
                                    <p class="small text-muted">MAO Head / Administrator</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <?php include './../template/footer.php'; ?>
        </div>
    </div>

    <a class="scroll-to-top rounded no-print" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include './../template/script.php'; ?>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize DataTable for on-screen viewing
            $('#reportTable').DataTable({
                "pageLength": 25,
                "ordering": false, // Disable ordering so the PHP SQL sorting remains intact for printing
                "language": { "search": "Search in Report:" }
            });

            // Initialize Select2 for the Barangay Filter
            $('.select2-single').select2();
        });
    </script>
</body>

</html>