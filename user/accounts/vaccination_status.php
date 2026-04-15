<?php
include '../../config.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include './../template/header.php'; ?>
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .progress-thick {
            height: 25px;
            /* Mas makapal na progress bar */
            border-radius: 8px;
            background-color: #e9ecef;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, .1);
        }

        .progress-bar {
            line-height: 25px;
            font-size: 13px;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .bg-vax {
            background-color: #2d6a4f !important;
        }

        /* Deep Green */
        .bg-unvax {
            background-color: #e63946 !important;
        }

        /* Danger Red */

        .stat-box {
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }

        .stat-box h5 {
            margin-bottom: 0;
            font-weight: bold;
        }

        .stat-box small {
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 1px;
            color: #6c757d;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content">
                <?php include './../template/navbar.php'; ?>

                <div class="container-fluid pt-4">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 fw-bold" style="color: #1b4332 !important;">
                            <i class="fas fa-chart-pie mr-2"></i>Barangay Vaccination Coverage
                        </h1>
                        <div>
                            <a href="print_vaccination_status.php" target="_blank"
                                class="btn text-white btn-sm font-weight-bold shadow-sm mr-2"
                                style="background-color: #1b4332; border-radius: 8px;">
                                <i class="fas fa-print mr-2"></i> Print Status Report
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm font-weight-bold shadow-sm"
                                style="border-radius: 8px;">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 shadow-sm"
                        style="background-color: #e9f5e9; color: #1b4332; border-left: 4px solid #2d6a4f !important;">
                        <i class="fas fa-info-circle mr-2"></i> <strong>Coverage Matrix:</strong> This table displays
                        the total number of registered animals per barangay and calculates the percentage of those that
                        have received at least one (1) vaccination.
                    </div>

                    <div class="card shadow mb-4" style="border-top: 4px solid #2d6a4f; border-radius: 10px;">
                        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center"
                            style="border-radius: 10px 10px 0 0;">
                            <h6 class="m-0 font-weight-bold" style="color: #1b4332;">Coverage Status Report</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle" id="coverageTable"
                                    width="100%" cellspacing="0">
                                    <thead style="background-color: #f8f9fa; color: #1b4332; text-align: center;">
                                        <tr>
                                            <th width="20%">Barangay</th>
                                            <th width="30%">Statistics Breakdown</th>
                                            <th width="35%">Coverage Percentage</th>
                                            <th width="15%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // ADVANCED SQL: Count Distinct Animals and Vaccinated Animals per Barangay
                                        $sql = "SELECT 
                                                    b.barangay_id, 
                                                    b.barangay_name,
                                                    COUNT(DISTINCT a.record_id) as total_animals,
                                                    COUNT(DISTINCT v.animal_id) as vaccinated_animals
                                                FROM barangay_tbl b
                                                LEFT JOIN animal_tbl a ON b.barangay_id = a.barangay_id
                                                LEFT JOIN vaccination_tbl v ON a.record_id = v.animal_id
                                                GROUP BY b.barangay_id, b.barangay_name
                                                ORDER BY b.barangay_name ASC";

                                        $result = mysqli_query($conn, $sql);

                                        if ($result && mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $brgy_id = $row['barangay_id'];
                                                $brgy_name = htmlspecialchars($row['barangay_name']);
                                                $total = (int) $row['total_animals'];
                                                $vax = (int) $row['vaccinated_animals'];
                                                $unvax = $total - $vax;

                                                // Calculate Percentages (Prevent Division by Zero)
                                                if ($total > 0) {
                                                    $pct_vax = round(($vax / $total) * 100, 1);
                                                    $pct_unvax = round(($unvax / $total) * 100, 1);
                                                } else {
                                                    $pct_vax = 0;
                                                    $pct_unvax = 0;
                                                }

                                                // Color Coding for overall safety
                                                $status_color = "#e63946"; // Red if low
                                                if ($pct_vax >= 70)
                                                    $status_color = "#f6c23e"; // Yellow if moderate
                                                if ($pct_vax >= 90)
                                                    $status_color = "#2d6a4f"; // Green if safe
                                                ?>
                                                <tr>
                                                    <td class="align-middle">
                                                        <h6 class="font-weight-bold text-dark mb-0"><?= $brgy_name ?></h6>
                                                        <span class="badge badge-light text-muted border mt-1">ID:
                                                            BRGY-<?= str_pad($brgy_id, 3, '0', STR_PAD_LEFT) ?></span>
                                                    </td>

                                                    <td class="align-middle">
                                                        <div class="d-flex justify-content-center gap-2" style="gap: 10px;">
                                                            <div class="stat-box flex-fill">
                                                                <h5 class="text-dark"><?= number_format($total) ?></h5>
                                                                <small>Total Pets</small>
                                                            </div>
                                                            <div class="stat-box flex-fill"
                                                                style="border-bottom: 3px solid #2d6a4f;">
                                                                <h5 style="color: #2d6a4f;"><?= number_format($vax) ?></h5>
                                                                <small>Vaccinated</small>
                                                            </div>
                                                            <div class="stat-box flex-fill"
                                                                style="border-bottom: 3px solid #e63946;">
                                                                <h5 style="color: #e63946;"><?= number_format($unvax) ?></h5>
                                                                <small>Unvaccinated</small>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td class="align-middle">
                                                        <div class="d-flex justify-content-between mb-1 small font-weight-bold">
                                                            <span style="color: <?= $status_color ?>;">Coverage:
                                                                <?= $pct_vax ?>%</span>
                                                            <span class="text-danger">Risk: <?= $pct_unvax ?>%</span>
                                                        </div>

                                                        <?php if ($total > 0): ?>
                                                            <div class="progress progress-thick">
                                                                <?php if ($pct_vax > 0): ?>
                                                                    <div class="progress-bar bg-vax" role="progressbar"
                                                                        style="width: <?= $pct_vax ?>%" aria-valuenow="<?= $pct_vax ?>"
                                                                        aria-valuemin="0" aria-valuemax="100">
                                                                        <?= $pct_vax ?>%
                                                                    </div>
                                                                <?php endif; ?>

                                                                <?php if ($pct_unvax > 0): ?>
                                                                    <div class="progress-bar bg-unvax" role="progressbar"
                                                                        style="width: <?= $pct_unvax ?>%"
                                                                        aria-valuenow="<?= $pct_unvax ?>" aria-valuemin="0"
                                                                        aria-valuemax="100">
                                                                        <?= $pct_unvax ?>%
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="progress progress-thick">
                                                                <div class="progress-bar bg-secondary w-100 text-light"
                                                                    role="progressbar">No Registered Animals</div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>

                                                    <td class="text-center align-middle">
                                                        <a href="list_barangay_animals.php?barangay_id=<?= $brgy_id ?>"
                                                            class="btn btn-sm text-white shadow-sm w-100 font-weight-bold"
                                                            style="background-color: #1b4332; border-radius: 6px;">
                                                            <i class="fas fa-list-ul mr-1"></i> View List
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include './../template/footer.php'; ?>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <?php include './../template/script.php'; ?>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#coverageTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "language": {
                    "search": "Search Barangay:"
                },
                "order": [[0, "asc"]]
            });
        });
    </script>
</body>

</html>