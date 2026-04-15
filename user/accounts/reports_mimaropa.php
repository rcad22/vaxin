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
                // --- FILTER LOGIC ---
                $filter_brgy = isset($_GET['barangay_id']) ? $_GET['barangay_id'] : '';
                $filter_date = isset($_GET['report_date']) ? $_GET['report_date'] : date('Y-m-d');

                // SPRINT 5: Report Options
                $report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'B'; // Default to Consolidated
                $filter_vaccinator = isset($_GET['vaccinator_id']) ? $_GET['vaccinator_id'] : '';

                // Build Query for Preview Table
                $where_clauses = ["DATE(v.vaccination_date) = '$filter_date'"];

                // STRICT MIMAROPA FILTER: Dogs & Cats only, Anti-Rabies only
                $where_clauses[] = "(s.species_name LIKE '%dog%' OR s.species_name LIKE '%cat%' OR s.species_name LIKE '%canine%' OR s.species_name LIKE '%feline%')";
                $where_clauses[] = "vac.vaccine_name LIKE '%rabies%'";

                if ($filter_brgy) {
                    $where_clauses[] = "a.barangay_id = '$filter_brgy'";
                }
                if ($report_type === 'A' && $filter_vaccinator) {
                    $where_clauses[] = "v.vaccinator_id = '$filter_vaccinator'";
                }

                $where_sql = "WHERE " . implode(' AND ', $where_clauses);

                // Count how many records match
                $count_sql = "SELECT COUNT(v.log_id) as total FROM vaccination_tbl v 
              JOIN animal_tbl a ON v.animal_id = a.record_id 
              JOIN species_tbl s ON a.species_id = s.species_id
              JOIN vaccine_tbl vac ON v.vaccine_id = vac.vaccine_id
              $where_sql";
                $count_res = mysqli_query($conn, $count_sql);
                $total_records = $count_res ? mysqli_fetch_assoc($count_res)['total'] : 0;

                // The Print URL with new parameters
                $print_url = "print_mimaropa_report.php?barangay_id=$filter_brgy&report_date=$filter_date&report_type=$report_type&vaccinator_id=$filter_vaccinator";

                ?>
                <div class="container-fluid pt-4">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 fw-bold" style="color: #1b4332 !important;">
                            <i class="fas fa-file-contract mr-2"></i>MIMAROPA Accomplishment Report
                        </h1>
                        <?php if ($total_records > 0): ?>
                            <a href="<?= $print_url ?>" target="_blank"
                                class="btn text-white shadow-sm px-4 font-weight-bold pulse-btn"
                                style="background-color: #1b4332; border-radius: 8px;">
                                <i class="fas fa-print me-2"></i> Print Official Form
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="card shadow mb-4 filter-card" style="border-radius: 10px;">
                        <div class="card-body">
                            <h6 class="font-weight-bold mb-3" style="color: #1b4332;"><i
                                    class="fas fa-filter mr-2"></i>Report Parameters</h6>

                            <div class="alert alert-info small py-2">
                                <i class="fas fa-info-circle mr-1"></i> System automatically filters for
                                <strong>Anti-Rabies</strong> and <strong>Cats/Dogs</strong> only to comply with MIMAROPA
                                Standard.
                            </div>

                            <form method="GET" action="">
                                <div class="row align-items-end">

                                    <div class="col-md-3 mb-3">
                                        <label class="small font-weight-bold text-dark">Report Format</label>
                                        <select name="report_type" id="reportType" class="form-control" required>
                                            <option value="B" <?= ($report_type == 'B') ? 'selected' : '' ?>>Option B:
                                                Consolidated Barangay</option>
                                            <option value="A" <?= ($report_type == 'A') ? 'selected' : '' ?>>Option A:
                                                Individual Vaccinator</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="small font-weight-bold text-dark">Barangay</label>
                                        <select name="barangay_id" class="form-control select2-single" required>
                                            <option value="">Select Barangay...</option>
                                            <?php
                                            $b_query = mysqli_query($conn, "SELECT * FROM barangay_tbl ORDER BY barangay_name ASC");
                                            while ($b = mysqli_fetch_assoc($b_query)) {
                                                $sel = ($filter_brgy == $b['barangay_id']) ? 'selected' : '';
                                                echo "<option value='{$b['barangay_id']}' $sel>{$b['barangay_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="col-md-2 mb-3">
                                        <label class="small font-weight-bold text-dark">Date</label>
                                        <input type="date" name="report_date" class="form-control"
                                            value="<?= $filter_date ?>" required>
                                    </div>

                                    <div class="col-md-2 mb-3" id="vaccinatorContainer"
                                        style="display: <?= ($report_type == 'A') ? 'block' : 'none' ?>;">
                                        <label class="small font-weight-bold text-dark">Select Vaccinator</label>
                                        <select name="vaccinator_id" id="vaccinator_id"
                                            class="form-control select2-single">
                                            <option value="">Select Staff...</option>
                                            <?php
                                            $u_query = mysqli_query($conn, "SELECT userid, fullname FROM user_tbl");
                                            while ($u = mysqli_fetch_assoc($u_query)) {
                                                $sel = ($filter_vaccinator == $u['userid']) ? 'selected' : '';
                                                echo "<option value='{$u['userid']}' $sel>{$u['fullname']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="col-md-2 mb-3">
                                        <button type="submit" class="btn w-100 text-white font-weight-bold"
                                            style="background-color: #2d6a4f;">
                                            <i class="fas fa-search mr-1"></i> Load Preview
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4" style="border-top: 4px solid #2d6a4f;">
                        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold" style="color: #1b4332;">Data Preview
                                (<?= $report_type == 'A' ? 'Individual' : 'Consolidated' ?>)</h6>
                            <span class="badge badge-success p-2">Found: <?= $total_records ?> Record(s)</span>
                        </div>
                        <div class="card-body">
                            <?php if ($total_records == 0): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-clipboard-list fa-3x text-gray-300 mb-3"></i>
                                    <p class="text-muted mb-0">No Anti-Rabies records found for the selected parameters.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle" id="previewTable"
                                        width="100%" cellspacing="0">
                                        <thead style="background-color: #f8f9fa; color: #1b4332;">
                                            <tr>
                                                <th>Owner's Name</th>
                                                <th>Pet's Name</th>
                                                <th>Species & Breed</th>
                                                <th>Vaccine Administered</th>
                                                <th>Staff</th>
                                                <th class="text-center">
                                                    <?= $report_type == 'B' ? 'V/NV Status' : 'Signature & Remarks' ?>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT a.owner_name, a.animal_name, a.breed, a.owner_signature, s.species_name, 
                                                           vac.vaccine_name, u.fullname as vaccinator, v.status, v.remarks as vax_remarks
                                                    FROM vaccination_tbl v 
                                                    JOIN animal_tbl a ON v.animal_id = a.record_id 
                                                    JOIN species_tbl s ON a.species_id = s.species_id 
                                                    JOIN vaccine_tbl vac ON v.vaccine_id = vac.vaccine_id
                                                    JOIN user_tbl u ON v.vaccinator_id = u.userid
                                                    $where_sql 
                                                    ORDER BY a.owner_name ASC";
                                            $result = mysqli_query($conn, $sql);

                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $is_vax = ($row['status'] === 'Vaccinated');
                                                $v_mark = $is_vax ? '<span class="text-success font-weight-bold">V</span>' : '<span class="text-danger font-weight-bold">NV</span>';

                                                echo "<tr>";
                                                echo "<td class='font-weight-bold align-middle'>" . htmlspecialchars($row['owner_name']) . "</td>";
                                                echo "<td class='align-middle'>" . htmlspecialchars($row['animal_name']) . "</td>";
                                                echo "<td class='align-middle'>" . htmlspecialchars($row['species_name']) . " (" . htmlspecialchars($row['breed']) . ")</td>";
                                                echo "<td class='text-success font-weight-bold align-middle'>" . htmlspecialchars($row['vaccine_name']) . "</td>";
                                                echo "<td class='text-muted small align-middle'>" . htmlspecialchars($row['vaccinator']) . "</td>";

                                                // Dynamic Last Column
                                                echo "<td class='text-center align-middle'>";
                                                if ($report_type === 'B') {
                                                    echo $v_mark . " <br><small>" . htmlspecialchars($row['vax_remarks']) . "</small>";
                                                } else {
                                                    if (!empty($row['owner_signature'])) {
                                                        echo "<img src='" . $row['owner_signature'] . "' style='max-height:30px;' alt='Sig'><br>";
                                                    }
                                                    $rem = $is_vax ? "Vaccinated." : "Not Vaccinated: " . $row['vax_remarks'];
                                                    echo "<small>" . htmlspecialchars($rem) . "</small>";
                                                }
                                                echo "</td>";
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
                "language": { "search": "Search Preview:" }
            });
            $('.select2-single').select2();

            // Toggle Vaccinator dropdown
            $('#reportType').on('change', function () {
                if ($(this).val() === 'A') {
                    $('#vaccinatorContainer').slideDown();
                    $('#vaccinator_id').prop('required', true);
                } else {
                    $('#vaccinatorContainer').slideUp();
                    $('#vaccinator_id').prop('required', false).val('').trigger('change');
                }
            });
        });
    </script>
</body>

</html>