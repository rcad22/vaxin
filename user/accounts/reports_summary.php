<?php
include '../../config.php';

// --- FILTER LOGIC ---
$selected_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');
$selected_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : 'all';

$where_clauses = ["YEAR(v.vaccination_date) = '$selected_year'"];
if ($selected_month !== 'all') {
    $where_clauses[] = "MONTH(v.vaccination_date) = '$selected_month'";
}
$where_sql = "WHERE " . implode(' AND ', $where_clauses);

// 1. DYNAMIC MATRIX LOGIC (Barangay vs Species)
// Kunin lahat ng Species para maging Columns
$species_query = mysqli_query($conn, "SELECT species_id, species_name FROM species_tbl ORDER BY species_name ASC");
$all_species = [];
while ($sp = mysqli_fetch_assoc($species_query)) {
    $all_species[$sp['species_id']] = $sp['species_name'];
}

// Kunin lahat ng Barangays para maging Rows
$brgy_query = mysqli_query($conn, "SELECT barangay_id, barangay_name FROM barangay_tbl ORDER BY barangay_name ASC");
$all_brgys = [];
while ($brgy = mysqli_fetch_assoc($brgy_query)) {
    $all_brgys[$brgy['barangay_id']] = $brgy['barangay_name'];
}

// Kunin ang mga Counts
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

// 2. QUICK STATS
$total_vax = 0;
$total_sql = "SELECT COUNT(log_id) as total FROM vaccination_tbl v $where_sql";
$total_res = mysqli_query($conn, $total_sql);
if ($total_res)
    $total_vax = mysqli_fetch_assoc($total_res)['total'];
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

                <div class="container-fluid pt-4">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 fw-bold" style="color: #1b4332 !important;"><i
                                class="fas fa-chart-pie mr-2"></i>Municipal Vaccination Summary</h1>

                        <?php $print_url = "print_summary_report.php?filter_year=$selected_year&filter_month=$selected_month"; ?>
                        <a href="<?= $print_url ?>" target="_blank"
                            class="btn text-white shadow-sm px-4 font-weight-bold"
                            style="background-color: #1b4332; border-radius: 8px;">
                            <i class="fas fa-print me-2"></i> Print Official Summary
                        </a>
                    </div>

                    <div class="card shadow mb-4" style="border-radius: 15px;">
                        <div class="card-body bg-white" style="border-radius: 15px;">
                            <form method="GET" action="">
                                <div class="row align-items-end">
                                    <div class="col-md-4 mb-3">
                                        <label class="small font-weight-bold" style="color: #1b4332;">Select
                                            Year</label>
                                        <select name="filter_year" class="form-control select2-single">
                                            <?php
                                            $current_y = date('Y');
                                            for ($y = $current_y; $y >= 2024; $y--) {
                                                $sel = ($selected_year == $y) ? 'selected' : '';
                                                echo "<option value='$y' $sel>$y</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="small font-weight-bold" style="color: #1b4332;">Select
                                            Month</label>
                                        <select name="filter_month" class="form-control select2-single">
                                            <option value="all" <?= $selected_month == 'all' ? 'selected' : '' ?>>Entire
                                                Year (Annual Summary)</option>
                                            <?php
                                            for ($m = 1; $m <= 12; $m++) {
                                                $month_name = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
                                                $sel = ($selected_month == $m) ? 'selected' : '';
                                                echo "<option value='$m' $sel>$month_name</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <button type="submit" class="btn w-100 text-white font-weight-bold"
                                            style="background-color: #2d6a4f;">
                                            <i class="fas fa-sync-alt mr-1"></i> Generate Matrix
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2"
                                style="border-left: 4px solid #1b4332; border-radius: 10px;">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-uppercase mb-1"
                                                style="color: #1b4332;">Total Vaccinations for Period</div>
                                            <div class="h3 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($total_vax) ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-syringe fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4" style="border-top: 4px solid #2d6a4f;">
                        <div class="card-header py-3 bg-white">
                            <h6 class="m-0 font-weight-bold" style="color: #1b4332;">Barangay vs. Species Matrix</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="matrixTable" width="100%"
                                    cellspacing="0">
                                    <thead style="background-color: #f8f9fa; color: #1b4332;">
                                        <tr>
                                            <th>Barangay Name</th>
                                            <?php foreach ($all_species as $id => $name): ?>
                                                <th class="text-center"><?= htmlspecialchars($name) ?></th>
                                            <?php endforeach; ?>
                                            <th class="text-center bg-light font-weight-bold">Total per Brgy</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $grand_totals = array_fill_keys(array_keys($all_species), 0);

                                        foreach ($all_brgys as $b_id => $b_name):
                                            $row_total = 0;
                                            ?>
                                            <tr>
                                                <td class="font-weight-bold text-dark"><?= htmlspecialchars($b_name) ?></td>
                                                <?php
                                                foreach ($all_species as $s_id => $s_name):
                                                    $count = isset($matrix_data[$b_id][$s_id]) ? $matrix_data[$b_id][$s_id] : 0;
                                                    $row_total += $count;
                                                    $grand_totals[$s_id] += $count;
                                                    ?>
                                                    <td class="text-center">
                                                        <?= $count > 0 ? number_format($count) : '<span class="text-muted">-</span>' ?>
                                                    </td>
                                                <?php endforeach; ?>
                                                <td class="text-center bg-light font-weight-bold" style="color: #1b4332;">
                                                    <?= number_format($row_total) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot style="background-color: #e9f5e9; color: #1b4332;">
                                        <tr>
                                            <th class="font-weight-bold text-right">MUNICIPAL TOTALS:</th>
                                            <?php foreach ($all_species as $s_id => $s_name): ?>
                                                <th class="text-center font-weight-bold">
                                                    <?= number_format($grand_totals[$s_id]) ?></th>
                                            <?php endforeach; ?>
                                            <th class="text-center font-weight-bold h5 mb-0">
                                                <?= number_format($total_vax) ?></th>
                                        </tr>
                                    </tfoot>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#matrixTable').DataTable({
                "pageLength": 50,
                "ordering": true,
                "language": { "search": "Search Barangay:" }
            });
            $('.select2-single').select2();
        });
    </script>
</body>

</html>