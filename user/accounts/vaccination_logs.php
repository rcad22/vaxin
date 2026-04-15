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

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .profile-card {
            background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(27, 67, 50, 0.2);
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

                                $current_user_id = $_SESSION['userid'];
                                $user_role = $_SESSION['role'];
                                $current_fullname = $_SESSION['fullname'];
                                $vaccinator_id = $current_user_id;

                                $specific_animal_id = isset($_GET['animal_id']) ? $_GET['animal_id'] : null;

                                // --- PHP PROCESSING LOGIC ---
                                
                                // 1. ADD VACCINATION LOG
                                if (isset($_POST['add_log'])) {
                                    $animal_id = $_POST['animal_id'];
                                    $status = $_POST['status']; // 'Vaccinated' or 'Not Vaccinated'
                                
                                    $vaccine_id = ($status === 'Vaccinated') ? $_POST['vaccine_id'] : null;
                                    $next_due_date = ($status === 'Vaccinated' && !empty($_POST['next_due_date'])) ? $_POST['next_due_date'] : null;

                                    $vaccination_date = $_POST['vaccination_date'];
                                    $remarks = trim($_POST['remarks']);
                                    $official_name = trim(ucwords($_POST['official_name']));
                                    $official_designation = trim(ucwords($_POST['official_designation']));

                                    $sql = "INSERT INTO vaccination_tbl (animal_id, status, vaccine_id, vaccination_date, next_due_date, vaccinator_id, official_name, official_designation, remarks) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                    $stmt = $conn->prepare($sql);

                                    if ($stmt) {
                                        $stmt->bind_param("isissssss", $animal_id, $status, $vaccine_id, $vaccination_date, $next_due_date, $vaccinator_id, $official_name, $official_designation, $remarks);
                                        if ($stmt->execute()) {
                                            $log_action = ($status === 'Vaccinated') ? "Recorded a successful vaccination for Animal ID: $animal_id" : "Recorded an unsuccessful vaccination attempt for Animal ID: $animal_id";
                                            logSystemActivity($conn, $current_user_id, $current_fullname, $log_action);

                                            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({ icon: "success", title: "Record Saved", text: "Log added successfully.", confirmButtonColor: "#1b4332", timer: 2000 });
                });
            </script>';
                                        } else {
                                            $error_msg = $stmt->error;
                                            logSystemActivity($conn, $current_user_id, $current_fullname, "Failed to record log. Error: $error_msg", "Error");
                                            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({ icon: "error", title: "Database Error", text: "Could not save the record.", confirmButtonColor: "#e63946" });
                });
            </script>';
                                        }
                                        $stmt->close();
                                    }
                                }

                                // 2. UPDATE VACCINATION LOG (NEW)
                                if (isset($_POST['update_log'])) {
                                    $log_id = $_POST['edit_log_id'];
                                    $status = $_POST['edit_status'];

                                    $vaccine_id = ($status === 'Vaccinated') ? $_POST['edit_vaccine_id'] : null;
                                    $next_due_date = ($status === 'Vaccinated' && !empty($_POST['edit_next_due_date'])) ? $_POST['edit_next_due_date'] : null;

                                    $vaccination_date = $_POST['edit_vaccination_date'];
                                    $remarks = trim($_POST['edit_remarks']);
                                    $official_name = trim(ucwords($_POST['edit_official_name']));
                                    $official_designation = trim(ucwords($_POST['edit_official_designation']));

                                    $sql = "UPDATE vaccination_tbl SET status=?, vaccine_id=?, vaccination_date=?, next_due_date=?, official_name=?, official_designation=?, remarks=? WHERE log_id=?";
                                    $stmt = $conn->prepare($sql);

                                    if ($stmt) {
                                        $stmt->bind_param("sisssssi", $status, $vaccine_id, $vaccination_date, $next_due_date, $official_name, $official_designation, $remarks, $log_id);
                                        if ($stmt->execute()) {
                                            logSystemActivity($conn, $current_user_id, $current_fullname, "Updated vaccination log ID: $log_id");
                                            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({ icon: "success", title: "Log Updated", text: "Changes saved successfully.", confirmButtonColor: "#1b4332", timer: 2000 });
                });
            </script>';
                                        } else {
                                            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({ icon: "error", title: "Database Error", text: "Could not update the record.", confirmButtonColor: "#e63946" });
                });
            </script>';
                                        }
                                        $stmt->close();
                                    }
                                }

                                // 3. DELETE VACCINATION LOG (Admin Only)
                                if (isset($_POST['delete_log']) && $user_role === 'Administrator') {
                                    $log_id = $_POST['log_id'];

                                    $sql = "DELETE FROM vaccination_tbl WHERE log_id = ?";
                                    $stmt = $conn->prepare($sql);

                                    if ($stmt) {
                                        $stmt->bind_param("i", $log_id);
                                        if ($stmt->execute()) {
                                            logSystemActivity($conn, $current_user_id, $current_fullname, "Deleted log ID: $log_id", "Warning");
                                            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({ icon: "info", title: "Log Deleted", text: "Record removed.", confirmButtonColor: "#1b4332", timer: 2000 });
                });
            </script>';
                                        }
                                        $stmt->close();
                                    }
                                } ?>
                <div class="container-fluid pt-4">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 fw-bold" style="color: #1b4332 !important;"><i
                                class="fas fa-syringe mr-2"></i>Vaccination Logs</h1>
                        <button class="btn text-white shadow-sm px-4"
                            style="background-color: #2d6a4f; border-radius: 8px;" data-toggle="modal"
                            data-target="#addLogModal">
                            <i class="fas fa-plus-circle me-2"></i> Record Entry
                        </button>
                    </div>

                    <?php
                    // DUAL MODE: Profile card
                    if ($specific_animal_id):
                        $anim_sql = "SELECT a.*, b.barangay_name, s.species_name,
                                     (SELECT COUNT(log_id) FROM vaccination_tbl WHERE animal_id = a.record_id AND status = 'Vaccinated') as vax_count
                                     FROM animal_tbl a 
                                     JOIN barangay_tbl b ON a.barangay_id = b.barangay_id 
                                     JOIN species_tbl s ON a.species_id = s.species_id 
                                     WHERE a.record_id = '$specific_animal_id'";
                        $anim_res = mysqli_query($conn, $anim_sql);

                        if ($animal_data = mysqli_fetch_assoc($anim_res)):
                            $animal_age_str = "Unknown Age";
                            if (!empty($animal_data['birth_date'])) {
                                $bdate = new DateTime($animal_data['birth_date']);
                                $today = new DateTime('today');
                                $diff = $bdate->diff($today);
                                $age_parts = [];
                                if ($diff->y > 0)
                                    $age_parts[] = $diff->y . " yr(s)";
                                if ($diff->m > 0 || $diff->y == 0)
                                    $age_parts[] = $diff->m . " mo(s)";
                                $animal_age_str = implode(", ", $age_parts);
                            }
                            ?>
                                    <div class="profile-card position-relative overflow-hidden">
                                        <i class="fas fa-paw position-absolute"
                                            style="font-size: 8rem; color: rgba(255,255,255,0.1); right: -20px; bottom: -20px;"></i>
                                        <div class="row align-items-center">
                                            <div class="col-md-2 text-center border-right"
                                                style="border-color: rgba(255,255,255,0.2) !important;">
                                                <i class="fas fa-qrcode fa-3x mb-2 text-warning"></i>
                                                <h5 class="font-weight-bold mb-0"><?= $animal_data['animal_id_tag'] ?></h5>
                                                <?php if ($animal_data['vax_count'] > 0): ?>
                                                        <span class="badge badge-success p-2 mt-2 w-100 shadow-sm"
                                                            style="background-color: #52b788; color: #000;">
                                                            <i class="fas fa-shield-alt"></i> VACCINATED
                                                        </span>
                                                <?php else: ?>
                                                        <span class="badge badge-danger p-2 mt-2 w-100 shadow-sm">
                                                            <i class="fas fa-exclamation-triangle"></i> NO RECORD
                                                        </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-5 pl-4 border-right"
                                                style="border-color: rgba(255,255,255,0.2) !important;">
                                                <h4 class="font-weight-bold text-warning mb-1">
                                                    <?= $animal_data['animal_name'] ?: 'No Name (Farm Tag)' ?>
                                                </h4>
                                                <p class="mb-1"><i class="fas fa-tag mr-2"></i><?= $animal_data['species_name'] ?> -
                                                    <?= $animal_data['breed'] ?> (<?= $animal_data['sex'] ?>)
                                                </p>
                                                <p class="mb-1 small text-light"><i class="fas fa-palette mr-1"></i> Color:
                                                    <?= $animal_data['color'] ?> &nbsp;|&nbsp; <i class="fas fa-birthday-cake mr-1"></i>
                                                    Age: <?= $animal_age_str ?>
                                                </p>
                                                <p class="mb-0 mt-2">
                                                    <?= $animal_data['is_stray'] ? '<span class="badge badge-warning text-dark mr-1 p-1">Stray (Ligaw)</span>' : '<span class="badge badge-light text-dark mr-1 p-1">Owned Pet</span>' ?>
                                                    <?= $animal_data['is_fixed'] ? '<span class="badge badge-info p-1">Kapon / Ligate</span>' : '<span class="badge badge-secondary p-1">Hindi Kapon</span>' ?>
                                                </p>
                                            </div>
                                            <div class="col-md-5 pl-4 position-relative">
                                                <h5 class="font-weight-bold mb-1"><i
                                                        class="fas fa-user mr-2"></i><?= $animal_data['owner_name'] ?></h5>
                                                <p class="mb-0 small"><i class="fas fa-map-marker-alt mr-2"></i>Brgy.
                                                    <?= $animal_data['barangay_name'] ?>
                                                </p>
                                                <p class="mb-0 small text-light"><i
                                                        class="fas fa-phone mr-2"></i><?= $animal_data['contact_no'] ?: 'N/A' ?></p>

                                                <?php if ($user_role !== 'Field Vaccinator'): ?>
                                                        <a href="print_animal_record.php?animal_id=<?= $specific_animal_id ?>" target="_blank"
                                                            class="btn btn-warning btn-sm font-weight-bold shadow-sm position-absolute"
                                                            style="bottom: 0; right: 20px;">
                                                            <i class="fas fa-print mr-1"></i> Print Record
                                                        </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                        endif;
                    endif;
                    ?>

                    <div class="card shadow mb-4" style="border-top: 4px solid #2d6a4f;">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle" id="logsTable" width="100%"
                                    cellspacing="0">
                                    <thead style="background-color: #f8f9fa; color: #1b4332;">
                                        <tr>
                                            <th>Date</th>
                                            <?php if (!$specific_animal_id)
                                                echo '<th>Animal ID & Name</th>'; ?>
                                            <th>Status & Details</th>
                                            <th>Staff & Official</th>
                                            <th>Remarks / Reason</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query_condition = $specific_animal_id ? "WHERE v.animal_id = '$specific_animal_id'" : "";

                                        $sql = "SELECT v.*, a.animal_id_tag, a.animal_name, vac.vaccine_name, u.fullname as vaccinator 
                                                FROM vaccination_tbl v 
                                                JOIN animal_tbl a ON v.animal_id = a.record_id 
                                                LEFT JOIN vaccine_tbl vac ON v.vaccine_id = vac.vaccine_id 
                                                JOIN user_tbl u ON v.vaccinator_id = u.userid 
                                                $query_condition 
                                                ORDER BY v.vaccination_date DESC";

                                        $result = mysqli_query($conn, $sql);

                                        if ($result && mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {

                                                $is_vax = ($row['status'] === 'Vaccinated');
                                                $status_badge = $is_vax ? '<span class="badge badge-success p-1 mb-1"><i class="fas fa-check-circle"></i> Vaccinated</span>' : '<span class="badge badge-danger p-1 mb-1"><i class="fas fa-times-circle"></i> Not Vaccinated</span>';
                                                $vax_name = $is_vax ? htmlspecialchars($row['vaccine_name']) : 'N/A';

                                                $due_text = "";
                                                if ($is_vax && $row['next_due_date']) {
                                                    $is_overdue = (strtotime($row['next_due_date']) < time());
                                                    $due_color = $is_overdue ? 'text-danger font-weight-bold' : 'text-info';
                                                    $due_text = "<br><small class='$due_color'>Next Due: " . date('M d, Y', strtotime($row['next_due_date'])) . "</small>";
                                                }
                                                ?>
                                                        <tr>
                                                            <td class="font-weight-bold text-dark align-middle">
                                                                <?= date('M d, Y', strtotime($row['vaccination_date'])) ?>
                                                            </td>

                                                            <?php if (!$specific_animal_id): ?>
                                                                    <td class="align-middle">
                                                                        <a href="?animal_id=<?= $row['animal_id'] ?>" class="font-weight-bold"
                                                                            style="color: #2d6a4f;">
                                                                            <i class="fas fa-qrcode mr-1"></i><?= $row['animal_id_tag'] ?>
                                                                        </a><br>
                                                                        <small class="text-muted"><?= $row['animal_name'] ?></small>
                                                                    </td>
                                                            <?php endif; ?>

                                                            <td class="align-middle">
                                                                <?= $status_badge ?><br>
                                                                <span
                                                                    class="<?= $is_vax ? 'text-success font-weight-bold' : 'text-muted' ?>">
                                                                    <i class="fas fa-vial mr-1"></i><?= $vax_name ?>
                                                                </span>
                                                                <?= $due_text ?>
                                                            </td>

                                                            <td class="align-middle">
                                                                <div><i class="fas fa-user-md text-muted mr-1"></i> <span
                                                                        class="font-weight-bold"><?= htmlspecialchars($row['vaccinator']) ?></span>
                                                                </div>
                                                                <?php if (!empty($row['official_name'])): ?>
                                                                        <div class="small mt-1 text-muted" title="Assisting Barangay Official">
                                                                            <i class="fas fa-user-tie mr-1"></i>
                                                                            <?= htmlspecialchars($row['official_name']) ?>
                                                                            <span
                                                                                class="font-italic">(<?= htmlspecialchars($row['official_designation']) ?>)</span>
                                                                        </div>
                                                                <?php endif; ?>
                                                            </td>

                                                            <td
                                                                class="<?= $is_vax ? 'text-muted' : 'text-danger font-weight-bold' ?> small align-middle">
                                                                <?= htmlspecialchars($row['remarks']) ?: '-' ?>
                                                            </td>

                                                            <td class="text-center align-middle">
                                                                <div class="d-flex flex-column gap-1">
                                                                    <button
                                                                        class="btn btn-warning btn-sm shadow-sm font-weight-bold w-100 mb-1 btn-edit text-dark"
                                                                        data-id="<?= $row['log_id'] ?>"
                                                                        data-animal="<?= $row['animal_id'] ?>"
                                                                        data-status="<?= $row['status'] ?>"
                                                                        data-vaccine="<?= $row['vaccine_id'] ?>"
                                                                        data-date="<?= $row['vaccination_date'] ?>"
                                                                        data-due="<?= $row['next_due_date'] ?>"
                                                                        data-official="<?= htmlspecialchars($row['official_name']) ?>"
                                                                        data-designation="<?= htmlspecialchars($row['official_designation']) ?>"
                                                                        data-remarks="<?= htmlspecialchars($row['remarks']) ?>">
                                                                        <i class="fas fa-edit mr-1"></i> Edit
                                                                    </button>

                                                                    <?php if ($user_role === 'Administrator'): ?>
                                                                            <button
                                                                                class="btn btn-danger btn-sm shadow-sm font-weight-bold w-100 btn-delete"
                                                                                data-id="<?= $row['log_id'] ?>">
                                                                                <i class="fas fa-trash-alt mr-1"></i> Delete
                                                                            </button>
                                                                    <?php endif; ?>
                                                                </div>
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

    <div class="modal fade" id="addLogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 15px; border: none;">
                <form method="POST">
                    <div class="modal-header text-white"
                        style="background-color: #1b4332; border-top-left-radius: 15px; border-top-right-radius: 15px;">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-notes-medical mr-2"></i>Record Entry
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <?php if ($specific_animal_id): ?>
                                <input type="hidden" name="animal_id" value="<?= $specific_animal_id ?>">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle mr-2"></i> Recording entry for
                                    <strong><?= $animal_data['animal_id_tag'] ?> (<?= $animal_data['animal_name'] ?>)</strong>
                                </div>
                        <?php else: ?>
                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-dark">Select Animal *</label>
                                    <select name="animal_id" class="form-control select2-single" style="width: 100%;" required>
                                        <option value="">Search by Tag ID, Pet Name, or Owner...</option>
                                        <?php
                                        $a_query = mysqli_query($conn, "SELECT record_id, animal_id_tag, animal_name, owner_name FROM animal_tbl ORDER BY animal_id_tag DESC");
                                        while ($a = mysqli_fetch_assoc($a_query)) {
                                            echo "<option value='{$a['record_id']}'>{$a['animal_id_tag']} - {$a['animal_name']} (Owner: {$a['owner_name']})</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                        <?php endif; ?>

                        <div class="form-group mb-4 bg-white p-3 border rounded shadow-sm">
                            <label class="small font-weight-bold text-dark d-block border-bottom pb-2 mb-2">Vaccination
                                Status *</label>
                            <div class="custom-control custom-radio custom-control-inline mr-4">
                                <input type="radio" id="statVax" name="status" value="Vaccinated"
                                    class="custom-control-input" checked>
                                <label class="custom-control-label text-success font-weight-bold"
                                    style="font-size: 1.05rem;" for="statVax">Successfully Vaccinated</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="statNotVax" name="status" value="Not Vaccinated"
                                    class="custom-control-input">
                                <label class="custom-control-label text-danger font-weight-bold"
                                    style="font-size: 1.05rem;" for="statNotVax">Not Vaccinated (Failed Attempt)</label>
                            </div>
                        </div>

                        <div class="row" id="vaxTypeSection">
                            <div class="form-group mb-3 col-md-12">
                                <label class="small font-weight-bold text-dark">Vaccine Administered *</label>
                                <select name="vaccine_id" id="vaccine_id" class="form-control select2-single"
                                    style="width: 100%;" required>
                                    <option value="">Select Vaccine...</option>
                                    <?php
                                    $v_query = mysqli_query($conn, "SELECT vaccine_id, vaccine_name, target_species FROM vaccine_tbl ORDER BY vaccine_name ASC");
                                    while ($v = mysqli_fetch_assoc($v_query)) {
                                        echo "<option value='{$v['vaccine_id']}'>{$v['vaccine_name']} (For: {$v['target_species']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group mb-3 col-md-6">
                                <label class="small font-weight-bold text-dark">Date *</label>
                                <input type="date" name="vaccination_date" class="form-control"
                                    value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group mb-3 col-md-6" id="nextDueSection">
                                <label class="small font-weight-bold text-dark">Next Due Date (Optional)</label>
                                <input type="date" name="next_due_date" class="form-control">
                            </div>
                        </div>

                        <div class="row p-3 bg-white border rounded mx-0 mb-3 shadow-sm">
                            <h6 class="w-100 font-weight-bold text-success mb-3"><i
                                    class="fas fa-users mr-2"></i>Assisting Barangay Official (Optional)</h6>
                            <div class="form-group mb-0 col-md-6 border-right">
                                <label class="small font-weight-bold text-dark">Official's Full Name</label>
                                <input type="text" name="official_name" class="form-control"
                                    placeholder="e.g. Juan Cruz">
                            </div>
                            <div class="form-group mb-0 col-md-6 pl-3">
                                <label class="small font-weight-bold text-dark">Designation / Position</label>
                                <input type="text" name="official_designation" class="form-control"
                                    placeholder="e.g. Kagawad">
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="small font-weight-bold text-dark" id="remarksLabel">Remarks / Batch
                                Number</label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="2"
                                placeholder="Notes about the animal's reaction or vaccine batch info..."></textarea>
                        </div>

                    </div>
                    <div class="modal-footer border-0"
                        style="background-color: #e9ecef; border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_log" class="btn text-white px-5"
                            style="background-color: #1b4332; font-weight: bold;">
                            <i class="fas fa-save mr-2"></i> Save Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editLogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 15px; border: none;">
                <form method="POST">
                    <div class="modal-header text-dark"
                        style="background-color: #f6c23e; border-top-left-radius: 15px; border-top-right-radius: 15px;">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-2"></i>Edit Entry</h5>
                        <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <input type="hidden" name="edit_log_id" id="edit_log_id">

                        <div class="form-group mb-4 bg-white p-3 border rounded shadow-sm">
                            <label class="small font-weight-bold text-dark d-block border-bottom pb-2 mb-2">Vaccination
                                Status *</label>
                            <div class="custom-control custom-radio custom-control-inline mr-4">
                                <input type="radio" id="editStatVax" name="edit_status" value="Vaccinated"
                                    class="custom-control-input edit-status-radio">
                                <label class="custom-control-label text-success font-weight-bold"
                                    style="font-size: 1.05rem;" for="editStatVax">Successfully Vaccinated</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="editStatNotVax" name="edit_status" value="Not Vaccinated"
                                    class="custom-control-input edit-status-radio">
                                <label class="custom-control-label text-danger font-weight-bold"
                                    style="font-size: 1.05rem;" for="editStatNotVax">Not Vaccinated (Failed
                                    Attempt)</label>
                            </div>
                        </div>

                        <div class="row" id="editVaxTypeSection">
                            <div class="form-group mb-3 col-md-12">
                                <label class="small font-weight-bold text-dark">Vaccine Administered *</label>
                                <select name="edit_vaccine_id" id="edit_vaccine_id" class="form-control">
                                    <option value="">Select Vaccine...</option>
                                    <?php
                                    $v_query = mysqli_query($conn, "SELECT vaccine_id, vaccine_name, target_species FROM vaccine_tbl ORDER BY vaccine_name ASC");
                                    while ($v = mysqli_fetch_assoc($v_query)) {
                                        echo "<option value='{$v['vaccine_id']}'>{$v['vaccine_name']} (For: {$v['target_species']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group mb-3 col-md-6">
                                <label class="small font-weight-bold text-dark">Date *</label>
                                <input type="date" name="edit_vaccination_date" id="edit_vaccination_date"
                                    class="form-control" required>
                            </div>
                            <div class="form-group mb-3 col-md-6" id="editNextDueSection">
                                <label class="small font-weight-bold text-dark">Next Due Date (Optional)</label>
                                <input type="date" name="edit_next_due_date" id="edit_next_due_date"
                                    class="form-control">
                            </div>
                        </div>

                        <div class="row p-3 bg-white border rounded mx-0 mb-3 shadow-sm">
                            <h6 class="w-100 font-weight-bold text-success mb-3"><i
                                    class="fas fa-users mr-2"></i>Assisting Barangay Official (Optional)</h6>
                            <div class="form-group mb-0 col-md-6 border-right">
                                <label class="small font-weight-bold text-dark">Official's Full Name</label>
                                <input type="text" name="edit_official_name" id="edit_official_name"
                                    class="form-control">
                            </div>
                            <div class="form-group mb-0 col-md-6 pl-3">
                                <label class="small font-weight-bold text-dark">Designation / Position</label>
                                <input type="text" name="edit_official_designation" id="edit_official_designation"
                                    class="form-control">
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="small font-weight-bold text-dark" id="editRemarksLabel">Remarks / Batch
                                Number</label>
                            <textarea name="edit_remarks" id="edit_remarks" class="form-control" rows="2"></textarea>
                        </div>

                    </div>
                    <div class="modal-footer border-0"
                        style="background-color: #e9ecef; border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_log" class="btn btn-warning text-dark font-weight-bold px-5">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="log_id" id="deleteLogId">
        <input type="hidden" name="delete_log" value="1">
    </form>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include './../template/script.php'; ?>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#logsTable').DataTable({
                "pageLength": 10,
                "order": [[0, "desc"]],
                "language": { "search": "Search Log:" }
            });

            $('.select2-single').select2({ dropdownParent: $('#addLogModal') });

            // SPRINT 4: DYNAMIC TOGGLE FOR STATUS (ADD MODAL)
            $('input[name="status"]').on('change', function () {
                if ($(this).val() === 'Vaccinated') {
                    $('#vaxTypeSection, #nextDueSection').slideDown();
                    $('#vaccine_id').prop('required', true);
                    $('#remarksLabel').html('Remarks / Batch Number');
                    $('#remarks').prop('required', false).attr('placeholder', 'Notes about the animal\'s reaction...');
                } else {
                    $('#vaxTypeSection, #nextDueSection').slideUp();
                    $('#vaccine_id').prop('required', false).val('').trigger('change');
                    $('#remarksLabel').html('<span class="text-danger">Reason for NOT Vaccinating *</span>');
                    $('#remarks').prop('required', true).attr('placeholder', 'e.g. Biting, Pregnant, Owner Refused, Sick...');
                }
            });

            // DYNAMIC TOGGLE FOR STATUS (EDIT MODAL)
            $('.edit-status-radio').on('change', function () {
                if ($(this).val() === 'Vaccinated') {
                    $('#editVaxTypeSection, #editNextDueSection').slideDown();
                    $('#edit_vaccine_id').prop('required', true);
                    $('#editRemarksLabel').html('Remarks / Batch Number');
                    $('#edit_remarks').prop('required', false);
                } else {
                    $('#editVaxTypeSection, #editNextDueSection').slideUp();
                    $('#edit_vaccine_id').prop('required', false).val('');
                    $('#editRemarksLabel').html('<span class="text-danger">Reason for NOT Vaccinating *</span>');
                    $('#edit_remarks').prop('required', true);
                }
            });

            // EDIT BUTTON LOGIC
            $('.btn-edit').on('click', function () {
                var status = $(this).data('status');

                $('#edit_log_id').val($(this).data('id'));
                $('#edit_vaccination_date').val($(this).data('date'));
                $('#edit_next_due_date').val($(this).data('due'));
                $('#edit_official_name').val($(this).data('official'));
                $('#edit_official_designation').val($(this).data('designation'));
                $('#edit_remarks').val($(this).data('remarks'));

                if (status === 'Vaccinated') {
                    $('#editStatVax').prop('checked', true);
                    $('#edit_vaccine_id').val($(this).data('vaccine'));
                } else {
                    $('#editStatNotVax').prop('checked', true);
                }

                // Trigger change to update UI
                $('.edit-status-radio:checked').trigger('change');

                $('#editLogModal').modal('show');
            });

            // DELETE BUTTON
            $('.btn-delete').on('click', function (e) {
                e.preventDefault();
                const logId = $(this).data('id');

                Swal.fire({
                    title: 'Delete Log?',
                    text: `Are you sure you want to delete this vaccination record?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#deleteLogId').val(logId);
                        $('#deleteForm').submit();
                    }
                });
            });
        });
    </script>
</body>

</html>