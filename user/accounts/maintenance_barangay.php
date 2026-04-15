<?php
    include '../../config.php';
 

// --- PHP PROCESSING LOGIC ---

// 1. ADD BARANGAY
if (isset($_POST['add_barangay'])) {
    $barangay_name = trim($_POST['barangay_name']);
    $barangay_code = strtoupper(trim($_POST['barangay_code'])); // Ensure uppercase 3-letter code

    // Check for duplicates
    $check_sql = "SELECT barangay_id FROM barangay_tbl WHERE barangay_name = ? OR barangay_code = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $barangay_name, $barangay_code);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "warning", title: "Duplicate Entry", text: "The Barangay Name or Code already exists.", confirmButtonColor: "#1b4332" });
            });
        </script>';
    } else {
        $sql = "INSERT INTO barangay_tbl (barangay_name, barangay_code) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $barangay_name, $barangay_code);

        if ($stmt->execute()) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({ icon: "success", title: "Success", text: "Barangay added successfully.", confirmButtonColor: "#1b4332", timer: 2000 });
                });
            </script>';
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// 2. UPDATE BARANGAY
if (isset($_POST['update_barangay'])) {
    $barangay_id = $_POST['barangay_id'];
    $barangay_name = trim($_POST['barangay_name']);
    $barangay_code = strtoupper(trim($_POST['barangay_code']));

    $sql = "UPDATE barangay_tbl SET barangay_name = ?, barangay_code = ? WHERE barangay_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $barangay_name, $barangay_code, $barangay_id);

    if ($stmt->execute()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "success", title: "Updated", text: "Barangay details saved.", confirmButtonColor: "#1b4332", timer: 2000 });
            });
        </script>';
    }
    $stmt->close();
}
if (isset($_POST['delete_barangay'])) {
    $barangay_id = $_POST['barangay_id'];
    $sql = "DELETE FROM barangay_tbl WHERE barangay_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $barangay_id);

    if ($stmt->execute()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "info", title: "Deleted", text: "Barangay has been removed.", confirmButtonColor: "#1b4332", timer: 2000 });
            });
        </script>';
    } else {
        // Catch foreign key constraint violation (e.g., if an animal is already using this barangay)
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "error", title: "Cannot Delete", text: "This Barangay is currently assigned to existing animal records.", confirmButtonColor: "#1b4332" });
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
                        <h1 class="h3 mb-0 fw-bold" style="color: #1b4332 !important;"><i
                                class="fas fa-map-marker-alt mr-2"></i>Barangay Maintenance</h1>
                        <p class="mb-0 text-muted">Manage the list of Barangays in Mogpog and their official codes.</p>
                    </div>

                    <div class="card shadow mb-4" style="border-top: 4px solid #2d6a4f;">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                            <h6 class="m-0 font-weight-bold" style="color: #1b4332;">Mogpog Barangays List</h6>
                            <button class="btn btn-sm text-white shadow-sm"
                                style="background-color: #2d6a4f; border-radius: 8px;" data-toggle="modal"
                                data-target="#addBarangayModal">
                                <i class="fas fa-plus me-2"></i> Add Barangay
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered" id="barangayTable" width="100%"
                                    cellspacing="0">
                                    <thead style="background-color: #f8f9fa; color: #1b4332;">
                                        <tr>
                                            <th width="10%">#</th>
                                            <th>Barangay Name</th>
                                            <th width="20%" class="text-center">3-Letter Code</th>
                                            <th width="15%" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Fetch all barangays
                                        // Assuming your table is named 'barangay_tbl'
                                        // Make sure to run the CREATE TABLE script for this later if you haven't!
                                        $sql = "SELECT * FROM barangay_tbl ORDER BY barangay_name ASC";
                                        $result = mysqli_query($conn, $sql);

                                        if ($result && mysqli_num_rows($result) > 0) {
                                            $count = 1;
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                ?>
                                                <tr>
                                                    <td class="font-weight-bold text-muted"><?= $count++ ?></td>
                                                    <td class="font-weight-bold text-dark">
                                                        <?= htmlspecialchars($row['barangay_name']) ?></td>
                                                    <td class="text-center">
                                                        <span class="badge p-2"
                                                            style="background-color: #e9f5e9; color: #2d6a4f; border: 1px solid #b7e4c7; font-size: 0.9rem;">
                                                            <?= htmlspecialchars($row['barangay_code']) ?>
                                                        </span>
                                                    </td>

                                                    <td class="text-center">
                                                        <button class="btn btn-warning btn-sm text-dark shadow-sm"
                                                            data-toggle="modal"
                                                            data-target="#editBarangay<?= $row['barangay_id'] ?>" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>

                                                        <button class="btn btn-danger btn-sm shadow-sm btn-delete"
                                                            data-id="<?= $row['barangay_id'] ?>"
                                                            data-name="<?= htmlspecialchars($row['barangay_name']) ?>"
                                                            title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </td>
                                                </tr>

                                                <div class="modal fade" id="editBarangay<?= $row['barangay_id'] ?>"
                                                    tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content" style="border-radius: 15px;">
                                                            <div class="modal-header text-dark"
                                                                style="background-color: #f6c23e;">
                                                                <h5 class="modal-title font-weight-bold"><i
                                                                        class="fas fa-edit mr-2"></i>Edit Barangay</h5>
                                                                <button type="button" class="close text-dark"
                                                                    data-dismiss="modal">&times;</button>
                                                            </div>
                                                            <form method="POST">
                                                                <div class="modal-body p-4">
                                                                    <input type="hidden" name="barangay_id"
                                                                        value="<?= $row['barangay_id'] ?>">

                                                                    <div class="form-group mb-3">
                                                                        <label
                                                                            class="small font-weight-bold text-muted">Barangay
                                                                            Name</label>
                                                                        <input type="text" name="barangay_name"
                                                                            class="form-control"
                                                                            value="<?= htmlspecialchars($row['barangay_name']) ?>"
                                                                            required>
                                                                    </div>

                                                                    <div class="form-group mb-3">
                                                                        <label
                                                                            class="small font-weight-bold text-muted">3-Letter
                                                                            Code (For Animal ID)</label>
                                                                        <input type="text" name="barangay_code"
                                                                            class="form-control text-center font-weight-bold"
                                                                            value="<?= htmlspecialchars($row['barangay_code']) ?>"
                                                                            maxlength="3" minlength="3"
                                                                            oninput="this.value = this.value.replace(/[^A-Za-z]/g, '').toUpperCase();"
                                                                            required>
                                                                        <small class="text-muted">Must be exactly 3 letters
                                                                            (e.g., GNB, MRK).</small>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer border-0 bg-light"
                                                                    style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                                                                    <button type="button" class="btn btn-outline-secondary"
                                                                        data-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="update_barangay"
                                                                        class="btn btn-warning text-dark font-weight-bold px-4">Update
                                                                        Changes</button>
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

    <div class="modal fade" id="addBarangayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px; border: none;">
                <form method="POST">
                    <div class="modal-header text-white"
                        style="background-color: #2d6a4f; border-top-left-radius: 15px; border-top-right-radius: 15px;">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2"></i>Add New Barangay
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info small">
                            [cite_start]<i class="fas fa-info-circle mr-1"></i> The 3-letter code will be used to
                            generate Unique Animal IDs[cite: 86].
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold" style="color: #1b4332;">Barangay Name</label>
                            <input type="text" name="barangay_name" class="form-control bg-light"
                                placeholder="e.g., Gitnang Bayan" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold" style="color: #1b4332;">3-Letter Code</label>
                            <input type="text" name="barangay_code"
                                class="form-control bg-light text-center font-weight-bold" placeholder="GNB"
                                maxlength="3" minlength="3"
                                oninput="this.value = this.value.replace(/[^A-Za-z]/g, '').toUpperCase();" required>
                            <small class="text-muted">Use standard abbreviations (A-Z only).</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light"
                        style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_barangay" class="btn text-white px-4"
                            style="background-color: #1b4332;">Save Barangay</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="barangay_id" id="deleteBarangayId">
        <input type="hidden" name="delete_barangay" value="1">
    </form>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include './../template/script.php'; ?>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function () {
            // Initialize DataTable
            $('#barangayTable').DataTable({
                "pageLength": 10,
                "language": {
                    "search": "Search Barangay:",
                }
            });

            // Interactive Delete with SweetAlert2
            $('.btn-delete').on('click', function (e) {
                e.preventDefault();
                const barangayId = $(this).data('id');
                const barangayName = $(this).data('name');

                Swal.fire({
                    title: 'Delete Barangay?',
                    html: `Are you sure you want to permanently delete <strong>${barangayName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#deleteBarangayId').val(barangayId);
                        $('#deleteForm').submit();
                    }
                });
            });
        });
    </script>
</body>

</html>