<?php
include '../../config.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include './../template/header.php' ?>
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

        .filter-card {
            background: linear-gradient(to right, #f8f9fa, #ffffff);
            border-left: 5px solid #2d6a4f;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php' ?>
        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <?php

                // CYBERSECURITY: Admin at Support Staff lang
                if (!isset($_SESSION['userid']) || ($_SESSION['role'] !== 'Administrator' && $_SESSION['role'] !== 'Support Staff')) {
                    header("Location: ./../admin/index.php");
                    exit();
                }

                // --- FILTER LOGIC ---
                $filter_brgy = isset($_GET['barangay_id']) ? $_GET['barangay_id'] : '';
                $filter_species = isset($_GET['species_id']) ? $_GET['species_id'] : '';

                $where_clauses = [];
                if ($filter_brgy)
                    $where_clauses[] = "a.barangay_id = '$filter_brgy'";
                if ($filter_species)
                    $where_clauses[] = "a.species_id = '$filter_species'";

                $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

                // Count Records
                $count_sql = "SELECT COUNT(record_id) as total FROM animal_tbl a $where_sql";
                $count_res = mysqli_query($conn, $count_sql);
                $total_records = $count_res ? mysqli_fetch_assoc($count_res)['total'] : 0;

                $print_url = "print_animal_list.php?barangay_id=$filter_brgy&species_id=$filter_species";
                ?>

                <div class="container-fluid pt-4">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 fw-bold" style="color: #1b4332 !important;">
                            <i class="fas fa-list-alt mr-2"></i>Generate Animal Masterlist
                        </h1>
                        <?php if ($total_records > 0): ?>
                            <a href="<?= $print_url ?>" target="_blank"
                                class="btn text-white shadow-sm px-4 font-weight-bold"
                                style="background-color: #1b4332; border-radius: 8px;">
                                <i class="fas fa-print me-2"></i> Print Masterlist
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="card shadow mb-4 filter-card" style="border-radius: 10px;">
                        <div class="card-body">
                            <h6 class="font-weight-bold mb-3" style="color: #1b4332;"><i
                                    class="fas fa-filter mr-2"></i>Filter Options</h6>
                            <form method="GET" action="">
                                <div class="row align-items-end">
                                    <div class="col-md-4 mb-3">
                                        <label class="small font-weight-bold text-dark">Barangay</label>
                                        <select name="barangay_id" class="form-control select2-single">
                                            <option value="">All Barangays</option>
                                            <?php
                                            $b_query = mysqli_query($conn, "SELECT * FROM barangay_tbl ORDER BY barangay_name ASC");
                                            while ($b = mysqli_fetch_assoc($b_query)) {
                                                $sel = ($filter_brgy == $b['barangay_id']) ? 'selected' : '';
                                                echo "<option value='{$b['barangay_id']}' $sel>{$b['barangay_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="small font-weight-bold text-dark">Animal Type (Species)</label>
                                        <select name="species_id" class="form-control select2-single">
                                            <option value="">All Types</option>
                                            <?php
                                            $s_query = mysqli_query($conn, "SELECT * FROM species_tbl ORDER BY species_name ASC");
                                            while ($s = mysqli_fetch_assoc($s_query)) {
                                                $sel = ($filter_species == $s['species_id']) ? 'selected' : '';
                                                echo "<option value='{$s['species_id']}' $sel>{$s['species_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <button type="submit" class="btn w-100 text-white font-weight-bold"
                                            style="background-color: #2d6a4f;">
                                            <i class="fas fa-search mr-1"></i> Generate List
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4" style="border-top: 4px solid #2d6a4f;">
                        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold" style="color: #1b4332;">Masterlist Preview</h6>
                            <span class="badge badge-success p-2">Found: <?= number_format($total_records) ?>
                                Record(s)</span>
                        </div>
                        <div class="card-body">
                            <?php if ($total_records == 0): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-paw fa-3x text-gray-300 mb-3"></i>
                                    <p class="text-muted mb-0">No animals found for the selected filters.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle" id="previewTable"
                                        width="100%" cellspacing="0">
                                        <thead style="background-color: #f8f9fa; color: #1b4332;">
                                            <tr>
                                                <th>Animal ID</th>
                                                <th>Owner Name</th>
                                                <th>Pet Name</th>
                                                <th>Species & Breed</th>
                                                <th>Sex / Color</th>
                                                <th>Barangay</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT a.*, b.barangay_name, s.species_name 
                                                    FROM animal_tbl a 
                                                    LEFT JOIN barangay_tbl b ON a.barangay_id = b.barangay_id 
                                                    LEFT JOIN species_tbl s ON a.species_id = s.species_id 
                                                    $where_sql 
                                                    ORDER BY b.barangay_name ASC, a.owner_name ASC";
                                            $result = mysqli_query($conn, $sql);

                                            while ($row = mysqli_fetch_assoc($result)) {
                                                echo "<tr>";
                                                echo "<td class='font-weight-bold text-success'>" . htmlspecialchars($row['animal_id_tag']) . "</td>";
                                                echo "<td class='font-weight-bold'>" . htmlspecialchars($row['owner_name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['animal_name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['species_name']) . " (" . htmlspecialchars($row['breed']) . ")</td>";
                                                echo "<td>" . htmlspecialchars($row['sex']) . " / " . htmlspecialchars($row['color']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['barangay_name']) . "</td>";
                                                echo "</tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
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
            $('#previewTable').DataTable({
                "pageLength": 15,
                "ordering": false,
                "language": { "search": "Search in Preview:" }
            });
            $('.select2-single').select2();
        });
    </script>
</body>

</html>