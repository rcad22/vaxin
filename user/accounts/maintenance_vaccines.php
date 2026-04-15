<?php
include '../../config.php';

// --- PHP PROCESSING LOGIC ---

// 1. ADD NEW VACCINE
if (isset($_POST['add_vaccine'])) {
    $vaccine_name = trim(ucwords($_POST['vaccine_name'])); 
    $description = trim($_POST['description']);
    
    // Kunin ang array ng selected species at gawing comma-separated string
    $target_species_array = isset($_POST['target_species']) ? $_POST['target_species'] : [];
    $target_species = implode(', ', $target_species_array);

    $check_sql = "SELECT vaccine_id FROM vaccine_tbl WHERE vaccine_name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $vaccine_name);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "warning", title: "Duplicate Entry", text: "This Vaccine Type already exists.", confirmButtonColor: "#1b4332" });
            });
        </script>';
    } else {
        $sql = "INSERT INTO vaccine_tbl (vaccine_name, target_species, description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $vaccine_name, $target_species, $description);

        if ($stmt->execute()) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({ icon: "success", title: "Vaccine Added", text: "New vaccine successfully saved.", confirmButtonColor: "#1b4332", timer: 2000 });
                });
            </script>';
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// 2. UPDATE VACCINE
if (isset($_POST['update_vaccine'])) {
    $vaccine_id = $_POST['vaccine_id'];
    $vaccine_name = trim(ucwords($_POST['vaccine_name']));
    $description = trim($_POST['description']);
    
    // I-convert ulit ang array to comma-separated string
    $target_species_array = isset($_POST['target_species']) ? $_POST['target_species'] : [];
    $target_species = implode(', ', $target_species_array);

    $sql = "UPDATE vaccine_tbl SET vaccine_name = ?, target_species = ?, description = ? WHERE vaccine_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $vaccine_name, $target_species, $description, $vaccine_id);

    if ($stmt->execute()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "success", title: "Record Updated", text: "Vaccine details saved.", confirmButtonColor: "#1b4332", timer: 2000 });
            });
        </script>';
    }
    $stmt->close();
}

// 3. DELETE VACCINE
if (isset($_POST['delete_vaccine'])) {
    $vaccine_id = $_POST['vaccine_id'];

    $sql = "DELETE FROM vaccine_tbl WHERE vaccine_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $vaccine_id);

    if ($stmt->execute()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "info", title: "Deleted", text: "Vaccine type removed.", confirmButtonColor: "#1b4332", timer: 2000 });
            });
        </script>';
    } else {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "error", title: "Cannot Delete", text: "This vaccine is used in existing logs.", confirmButtonColor: "#1b4332" });
            });
        </script>';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './../template/header.php' ?>
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        /* Green Agri Theme styling for Select2 Tags */
        .select2-container--default .select2-selection--multiple {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 4px;
        }
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #2d6a4f;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #2d6a4f;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 0.85rem;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 8px;
            border-right: 1px solid rgba(255,255,255,0.3);
            padding-right: 8px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #f6c23e;
            background: none;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php' ?>
        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                
                <div class="container-fluid pt-4">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 fw-bold" style="color: #1b4332 !important;"><i class="fas fa-syringe mr-2"></i>Vaccine Types Maintenance</h1>
                        <p class="mb-0 text-muted">Manage the official vaccines and link them to their target animal species.</p>
                    </div>

                    <div class="card shadow mb-4" style="border-top: 4px solid #2d6a4f;">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                            <h6 class="m-0 font-weight-bold" style="color: #1b4332;">MAO Approved Vaccines</h6>
                            <button class="btn btn-sm text-white shadow-sm" style="background-color: #2d6a4f; border-radius: 8px;" data-toggle="modal" data-target="#addVaccineModal">
                                <i class="fas fa-plus-circle me-2"></i> Add New Vaccine
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered" id="vaccineTable" width="100%" cellspacing="0">
                                    <thead style="background-color: #f8f9fa; color: #1b4332;">
                                        <tr>
                                            <th width="5%">ID</th>
                                            <th width="25%">Vaccine Name</th>
                                            <th width="30%">Target Species</th>
                                            <th>Description / Notes</th>
                                            <th width="12%" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT * FROM vaccine_tbl ORDER BY vaccine_name ASC";
                                        $result = mysqli_query($conn, $sql);
                                        
                                        if($result && mysqli_num_rows($result) > 0) {
                                            $count = 1;
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                // Convert the comma-separated string to an array to display badges
                                                $species_list = explode(', ', $row['target_species']);
                                        ?>
                                                <tr>
                                                    <td class="font-weight-bold text-muted"><?= $count++ ?></td>
                                                    <td class="font-weight-bold" style="color: #1b4332;">
                                                        <i class="fas fa-vial mr-2 text-muted"></i> <?= htmlspecialchars($row['vaccine_name']) ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            // Display each species as a nice green badge
                                                            if(!empty($row['target_species'])) {
                                                                foreach($species_list as $sp) {
                                                                    echo '<span class="badge mr-1 mb-1 p-2" style="background-color: #e9f5e9; color: #1b4332; border: 1px solid #b7e4c7;">'.htmlspecialchars($sp).'</span>';
                                                                }
                                                            } else {
                                                                echo '<span class="badge badge-secondary p-2">None Assigned</span>';
                                                            }
                                                        ?>
                                                    </td>
                                                    <td class="text-muted small">
                                                        <?= htmlspecialchars($row['description']) ?: '<span class="text-light-gray font-italic">No additional notes</span>' ?>
                                                    </td>
                                                    
                                                    <td class="text-center">
                                                        <button class="btn btn-warning btn-sm text-dark shadow-sm" data-toggle="modal" data-target="#editVaccine<?= $row['vaccine_id'] ?>" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        
                                                        <button class="btn btn-danger btn-sm shadow-sm btn-delete" data-id="<?= $row['vaccine_id'] ?>" data-name="<?= htmlspecialchars($row['vaccine_name']) ?>" title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </td>
                                                </tr>

                                                <div class="modal fade" id="editVaccine<?= $row['vaccine_id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content" style="border-radius: 15px;">
                                                            <div class="modal-header text-dark" style="background-color: #f6c23e;">
                                                                <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-2"></i>Edit Vaccine</h5>
                                                                <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                                                            </div>
                                                            <form method="POST">
                                                                <div class="modal-body p-4">
                                                                    <input type="hidden" name="vaccine_id" value="<?= $row['vaccine_id'] ?>">
                                                                    
                                                                    <div class="form-group mb-3">
                                                                        <label class="small font-weight-bold text-muted">Vaccine Name</label>
                                                                        <input type="text" name="vaccine_name" class="form-control font-weight-bold" value="<?= htmlspecialchars($row['vaccine_name']) ?>" required>
                                                                    </div>
                                                                    
                                                                    <div class="form-group mb-3">
                                                                        <label class="small font-weight-bold text-muted">Target Animal Type</label>
                                                                        <select name="target_species[]" class="form-control select2-multi" multiple="multiple" style="width: 100%;" required>
                                                                            <?php
                                                                            // Fetch all species from database for the dropdown
                                                                            $species_query = mysqli_query($conn, "SELECT species_name FROM species_tbl ORDER BY species_name ASC");
                                                                            while ($sp = mysqli_fetch_assoc($species_query)) {
                                                                                // Check if this species is in the current vaccine's list
                                                                                $selected = in_array($sp['species_name'], $species_list) ? 'selected' : '';
                                                                                echo "<option value='" . htmlspecialchars($sp['species_name']) . "' $selected>" . htmlspecialchars($sp['species_name']) . "</option>";
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                    </div>

                                                                    <div class="form-group mb-3">
                                                                        <label class="small font-weight-bold text-muted">Description / Notes</label>
                                                                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($row['description']) ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer border-0 bg-light" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                                                                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="update_vaccine" class="btn btn-warning text-dark font-weight-bold px-4">Update Changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
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

    <div class="modal fade" id="addVaccineModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px; border: none;">
                <form method="POST">
                    <div class="modal-header text-white" style="background-color: #2d6a4f; border-top-left-radius: 15px; border-top-right-radius: 15px;">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2"></i>Add New Vaccine</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold" style="color: #1b4332;">Vaccine Name</label>
                            <input type="text" name="vaccine_name" class="form-control bg-light font-weight-bold" placeholder="e.g., Anti-Rabies Vaccine (ARV)" required>
                        </div>

                        <div class="form-group mb-4">
                            <label class="small font-weight-bold" style="color: #1b4332;">Target Animal Type</label>
                            <select name="target_species[]" class="form-control select2-multi" multiple="multiple" style="width: 100%;" data-placeholder="Click to select animals..." required>
                                <?php
                                // Fetch all species directly from the database
                                $species_query = mysqli_query($conn, "SELECT species_name FROM species_tbl ORDER BY species_name ASC");
                                while ($sp = mysqli_fetch_assoc($species_query)) {
                                    echo "<option value='" . htmlspecialchars($sp['species_name']) . "'>" . htmlspecialchars($sp['species_name']) . "</option>";
                                }
                                ?>
                            </select>
                            <small class="text-muted mt-1 d-block">You can select multiple animals.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold" style="color: #1b4332;">Description / Notes (Optional)</label>
                            <textarea name="description" class="form-control bg-light" rows="3" placeholder="Add usage details, dosage notes, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_vaccine" class="btn text-white px-4" style="background-color: #1b4332;">Save Vaccine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="vaccine_id" id="deleteVaccineId">
        <input type="hidden" name="delete_vaccine" value="1">
    </form>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include './../template/script.php'; ?>
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#vaccineTable').DataTable({
                "pageLength": 10,
                "language": {
                    "search": "Search Vaccine:",
                }
            });

            // Initialize Select2 for Multi-Select Dropdowns
            $('.select2-multi').select2({
                placeholder: "Click to select animals...",
                allowClear: true,
                dropdownParent: $('#addVaccineModal') // Needed so it doesn't hide behind the modal
            });

            // Re-initialize Select2 specifically for Edit Modals when they are opened
            $('[id^="editVaccine"]').on('shown.bs.modal', function () {
                $(this).find('.select2-multi').select2({
                    placeholder: "Click to select animals...",
                    allowClear: true,
                    dropdownParent: $(this)
                });
            });

            // Interactive Delete with SweetAlert2
            $('.btn-delete').on('click', function(e) {
                e.preventDefault();
                const vaccineId = $(this).data('id');
                const vaccineName = $(this).data('name');

                Swal.fire({
                    title: 'Delete Vaccine?',
                    html: `Are you sure you want to permanently remove <strong>${vaccineName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#deleteVaccineId').val(vaccineId);
                        $('#deleteForm').submit();
                    }
                });
            });
        });
    </script>
</body>
</html>