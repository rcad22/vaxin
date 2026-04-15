<?php
include '../../config.php';
 
// --- PHP PROCESSING LOGIC ---

// 1. ADD NEW SPECIES (Animal Category)
if (isset($_POST['add_species'])) {
    $species_name = trim(ucwords($_POST['species_name'])); // Capitalize first letters (e.g., "Aso" -> "Aso", "Farm dog" -> "Farm Dog")
    $description = trim($_POST['description']);

    // Check for duplicates
    $check_sql = "SELECT species_id FROM species_tbl WHERE species_name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $species_name);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "warning", title: "Duplicate Entry", text: "This Animal Species already exists in the system.", confirmButtonColor: "#1b4332" });
            });
        </script>';
    } else {
        $sql = "INSERT INTO species_tbl (species_name, description) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $species_name, $description);

        if ($stmt->execute()) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({ icon: "success", title: "Species Added", text: "New animal category successfully saved.", confirmButtonColor: "#1b4332", timer: 2000 });
                });
            </script>';
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// 2. UPDATE SPECIES
if (isset($_POST['update_species'])) {
    $species_id = $_POST['species_id'];
    $species_name = trim(ucwords($_POST['species_name']));
    $description = trim($_POST['description']);

    $sql = "UPDATE species_tbl SET species_name = ?, description = ? WHERE species_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $species_name, $description, $species_id);

    if ($stmt->execute()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "success", title: "Record Updated", text: "Animal category details saved.", confirmButtonColor: "#1b4332", timer: 2000 });
            });
        </script>';
    }
    $stmt->close();
}

// 3. DELETE SPECIES
if (isset($_POST['delete_species'])) {
    $species_id = $_POST['species_id'];

    $sql = "DELETE FROM species_tbl WHERE species_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $species_id);

    if ($stmt->execute()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "info", title: "Deleted", text: "Animal species has been removed from the list.", confirmButtonColor: "#1b4332", timer: 2000 });
            });
        </script>';
    } else {
        // Catch foreign key constraint violation
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "error", title: "Cannot Delete", text: "This species is currently assigned to existing animal records.", confirmButtonColor: "#1b4332" });
            });
        </script>';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include './../template/header.php' ?>

<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php' ?>
        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                
                <div class="container-fluid pt-4">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 fw-bold" style="color: #1b4332 !important;"><i class="fas fa-paw mr-2"></i>Animal Species Maintenance</h1>
                        <p class="mb-0 text-muted">Manage the categories of farm animals and pets handled by MAO.</p>
                    </div>

                    <div class="card shadow mb-4" style="border-top: 4px solid #2d6a4f;">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                            <h6 class="m-0 font-weight-bold" style="color: #1b4332;">Registered Animal Categories</h6>
                            <button class="btn btn-sm text-white shadow-sm" style="background-color: #2d6a4f; border-radius: 8px;" data-toggle="modal" data-target="#addSpeciesModal">
                                <i class="fas fa-plus-circle me-2"></i> Add Animal Category
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered" id="speciesTable" width="100%" cellspacing="0">
                                    <thead style="background-color: #f8f9fa; color: #1b4332;">
                                        <tr>
                                            <th width="10%">ID</th>
                                            <th width="25%">Species Name</th>
                                            <th>Description / Notes</th>
                                            <th width="15%" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Fetch all animal categories
                                        $sql = "SELECT * FROM species_tbl ORDER BY species_name ASC";
                                        $result = mysqli_query($conn, $sql);
                                        
                                        if($result && mysqli_num_rows($result) > 0) {
                                            $count = 1;
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                // Dynamic Icon base on common species
                                                $icon = "fa-paw"; // default
                                                $name_lower = strtolower($row['species_name']);
                                                if (strpos($name_lower, 'dog') !== false || strpos($name_lower, 'aso') !== false) $icon = "fa-dog";
                                                if (strpos($name_lower, 'cat') !== false || strpos($name_lower, 'pusa') !== false) $icon = "fa-cat";
                                                if (strpos($name_lower, 'cow') !== false || strpos($name_lower, 'baka') !== false || strpos($name_lower, 'cattle') !== false || strpos($name_lower, 'carabao') !== false) $icon = "fa-hippo"; // closest alternative in free FA
                                                if (strpos($name_lower, 'pig') !== false || strpos($name_lower, 'swine') !== false || strpos($name_lower, 'baboy') !== false) $icon = "fa-piggy-bank";
                                        ?>
                                                <tr>
                                                    <td class="font-weight-bold text-muted"><?= $count++ ?></td>
                                                    <td class="font-weight-bold text-dark">
                                                        <i class="fas <?= $icon ?> mr-2" style="color: #2d6a4f;"></i> <?= htmlspecialchars($row['species_name']) ?>
                                                    </td>
                                                    <td class="text-muted">
                                                        <?= htmlspecialchars($row['description']) ?: '<span class="text-light-gray font-italic">No description provided</span>' ?>
                                                    </td>
                                                    
                                                    <td class="text-center">
                                                        <button class="btn btn-warning btn-sm text-dark shadow-sm" data-toggle="modal" data-target="#editSpecies<?= $row['species_id'] ?>" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        
                                                        <button class="btn btn-danger btn-sm shadow-sm btn-delete" data-id="<?= $row['species_id'] ?>" data-name="<?= htmlspecialchars($row['species_name']) ?>" title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </td>
                                                </tr>

                                                <div class="modal fade" id="editSpecies<?= $row['species_id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content" style="border-radius: 15px;">
                                                            <div class="modal-header text-dark" style="background-color: #f6c23e;">
                                                                <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-2"></i>Edit Animal Category</h5>
                                                                <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                                                            </div>
                                                            <form method="POST">
                                                                <div class="modal-body p-4">
                                                                    <input type="hidden" name="species_id" value="<?= $row['species_id'] ?>">
                                                                    
                                                                    <div class="form-group mb-3">
                                                                        <label class="small font-weight-bold text-muted">Species / Category Name</label>
                                                                        <input type="text" name="species_name" class="form-control font-weight-bold" value="<?= htmlspecialchars($row['species_name']) ?>" required>
                                                                    </div>
                                                                    
                                                                    <div class="form-group mb-3">
                                                                        <label class="small font-weight-bold text-muted">Description / Notes (Optional)</label>
                                                                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($row['description']) ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer border-0 bg-light" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                                                                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="update_species" class="btn btn-warning text-dark font-weight-bold px-4">Update Changes</button>
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

    <div class="modal fade" id="addSpeciesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px; border: none;">
                <form method="POST">
                    <div class="modal-header text-white" style="background-color: #2d6a4f; border-top-left-radius: 15px; border-top-right-radius: 15px;">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2"></i>Add Animal Category</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle mr-1"></i> Add general classifications such as <strong>Dogs, Cats, Cattle, Swine, Carabao</strong>, etc.
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold" style="color: #1b4332;">Species / Category Name</label>
                            <input type="text" name="species_name" class="form-control bg-light font-weight-bold" placeholder="e.g., Dog" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold" style="color: #1b4332;">Description / Notes (Optional)</label>
                            <textarea name="description" class="form-control bg-light" rows="3" placeholder="Brief description..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_species" class="btn text-white px-4" style="background-color: #1b4332;">Save Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="species_id" id="deleteSpeciesId">
        <input type="hidden" name="delete_species" value="1">
    </form>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include './../template/script.php'; ?>
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#speciesTable').DataTable({
                "pageLength": 10,
                "language": {
                    "search": "Search Animal:",
                }
            });

            // Interactive Delete with SweetAlert2
            $('.btn-delete').on('click', function(e) {
                e.preventDefault();
                const speciesId = $(this).data('id');
                const speciesName = $(this).data('name');

                Swal.fire({
                    title: 'Delete Category?',
                    html: `Are you sure you want to permanently delete <strong>${speciesName}</strong> from the system?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#deleteSpeciesId').val(speciesId);
                        $('#deleteForm').submit();
                    }
                });
            });
        });
    </script>
</body>
</html>