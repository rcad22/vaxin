<?php

include '../../config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './../template/header.php' ?>
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .inactive-row { background-color: #f8f9fa; opacity: 0.7; }
        .password-wrapper { position: relative; }
        .password-wrapper .fa-eye { position: absolute; right: 15px; top: 12px; cursor: pointer; color: #6c757d; }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php' ?>
        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <?php
                
$current_admin_id = $_SESSION['userid'];
$current_admin_name = $_SESSION['fullname'];

// --- PHP PROCESSING LOGIC (CRUD for Users) ---

// 1. ADD NEW USER
if (isset($_POST['add_user'])) {
    $fullname = trim(ucwords($_POST['fullname']));
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $contact = trim($_POST['contact']);
    $password = password_hash('vaxin2026', PASSWORD_BCRYPT);
    $status = 1;

    if (strlen($contact) !== 11 || !ctype_digit($contact)) {
        echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "error", title: "Invalid Contact", text: "Contact number must be exactly 11 digits." }); });</script>';
    } else {
        $check_user = $conn->prepare("SELECT userid FROM user_tbl WHERE username = ?");
        $check_user->bind_param("s", $username);
        $check_user->execute();
        if ($check_user->get_result()->num_rows > 0) {
            echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "error", title: "Username Taken", text: "Please choose a different username." }); });</script>';
        } else {
            $check_contact = $conn->prepare("SELECT userid FROM user_tbl WHERE contact_number = ?");
            $check_contact->bind_param("s", $contact);
            $check_contact->execute();
            if ($check_contact->get_result()->num_rows > 0) {
                echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "error", title: "Duplicate Number", text: "This contact number is already registered to another personnel." }); });</script>';
            } else {
                $sql = "INSERT INTO user_tbl (fullname, username, password, role, contact_number, useractive) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $fullname, $username, $password, $role, $contact, $status);

                if ($stmt->execute()) {
                    // SPRINT 1 LOGGING:
                    logSystemActivity($conn, $current_admin_id, $current_admin_name, "Registered new staff account: $fullname ($role)");

                    echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "success", title: "User Added", text: "New account successfully created.", confirmButtonColor: "#1b4332", timer: 2000 }); });</script>';
                }
                $stmt->close();
            }
        }
    }
}

// 2. UPDATE USER
if (isset($_POST['update_user'])) {
    $target_userid = $_POST['userid'];
    $fullname = trim(ucwords($_POST['fullname']));
    $role = $_POST['role'];
    $contact = trim($_POST['contact']);

    if (strlen($contact) !== 11 || !ctype_digit($contact)) {
        echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "error", title: "Invalid Contact" }); });</script>';
    } else {
        $check_contact = $conn->prepare("SELECT userid FROM user_tbl WHERE contact_number = ? AND userid != ?");
        $check_contact->bind_param("si", $contact, $target_userid);
        $check_contact->execute();

        if ($check_contact->get_result()->num_rows > 0) {
            echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "error", title: "Duplicate Number" }); });</script>';
        } else {
            $sql = "UPDATE user_tbl SET fullname = ?, role = ?, contact_number = ? WHERE userid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $fullname, $role, $contact, $target_userid);

            if ($stmt->execute()) {
                // SPRINT 1 LOGGING:
                logSystemActivity($conn, $current_admin_id, $current_admin_name, "Updated profile details of User ID: $target_userid ($fullname)");

                echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "success", title: "User Updated", text: "Account details saved successfully.", confirmButtonColor: "#1b4332", timer: 2000 }); });</script>';
            }
            $stmt->close();
        }
    }
}

// 3. DEACTIVATE USER
if (isset($_POST['deactivate_user'])) {
    $target_userid = $_POST['userid'];

    if ($target_userid == $current_admin_id) {
        echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "error", title: "Action Denied", text: "You cannot deactivate your own account." }); });</script>';
    } else {
        $sql = "UPDATE user_tbl SET useractive = 0 WHERE userid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $target_userid);

        if ($stmt->execute()) {
            // SPRINT 1 LOGGING:
            logSystemActivity($conn, $current_admin_id, $current_admin_name, "Deactivated User ID: $target_userid", "Warning");

            echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "info", title: "Account Deactivated", text: "User can no longer log in.", confirmButtonColor: "#1b4332", timer: 2000 }); });</script>';
        }
        $stmt->close();
    }
}

// SPRINT 2: 4. REACTIVATE USER
if (isset($_POST['reactivate_user'])) {
    $target_userid = $_POST['userid'];

    $sql = "UPDATE user_tbl SET useractive = 1 WHERE userid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $target_userid);

    if ($stmt->execute()) {
        // SPRINT 1 LOGGING:
        logSystemActivity($conn, $current_admin_id, $current_admin_name, "Reactivated User ID: $target_userid");

        echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "success", title: "Account Restored", text: "User can now log in again.", confirmButtonColor: "#2d6a4f", timer: 2000 }); });</script>';
    }
    $stmt->close();
}

// SPRINT 2: 5. ADMIN OVERRIDE - RESET PASSWORD
if (isset($_POST['reset_password'])) {
    $target_userid = $_POST['userid'];
    $new_password = $_POST['new_password'];
    $target_fullname = $_POST['target_fullname'];

    // Hashing the new password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    $sql = "UPDATE user_tbl SET password = ? WHERE userid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $target_userid);

    if ($stmt->execute()) {
        // SPRINT 1 LOGGING:
        logSystemActivity($conn, $current_admin_id, $current_admin_name, "Admin forcibly reset the password of $target_fullname (User ID: $target_userid)", "Warning");

        echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "success", title: "Password Reset", text: "Password has been updated for ' . $target_fullname . '.", confirmButtonColor: "#1b4332" }); });</script>';
    }
    $stmt->close();
}

                
                ?>
                
                <div class="container-fluid pt-4">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 fw-bold" style="color: #1b4332 !important;">Active Accounts</h1>
                        <p class="mb-0 text-muted">Manage system access and privileges for MAO Personnel.</p>
                    </div>

                    <div class="card shadow mb-4" style="border-top: 4px solid #2d6a4f;">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                            <h6 class="m-0 font-weight-bold" style="color: #1b4332;">Registered Users Masterlist</h6>
                            <button class="btn btn-sm text-white" style="background-color: #2d6a4f; border-radius: 8px;" data-toggle="modal" data-target="#addUserModal">
                                <i class="fas fa-user-plus me-2"></i> Register New Staff
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle" id="userTable" width="100%" cellspacing="0">
                                    <thead style="background-color: #f8f9fa; color: #1b4332;">
                                        <tr>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Contact</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // MODIFIED QUERY to show inactive users at the bottom
                                        $sql = "SELECT * FROM user_tbl ORDER BY useractive DESC, role, fullname ASC";
                                        $result = mysqli_query($conn, $sql);

                                        if ($result) {
                                            while ($user = mysqli_fetch_assoc($result)) {
                                                $badgeClass = 'badge-secondary';
                                                if ($user['role'] == 'Administrator')
                                                    $badgeClass = 'badge-danger';
                                                if ($user['role'] == 'Support Staff')
                                                    $badgeClass = 'badge-primary';
                                                if ($user['role'] == 'Field Vaccinator')
                                                    $badgeClass = 'badge-success';

                                                $is_active = $user['useractive'] == 1;
                                                $rowClass = $is_active ? '' : 'inactive-row';
                                                ?>
                                                        <tr class="<?= $rowClass ?>">
                                                            <td class="font-weight-bold <?= $is_active ? 'text-dark' : 'text-muted' ?>">
                                                                <i class="fas fa-user-circle text-muted mr-2"></i><?= htmlspecialchars($user['fullname']) ?>
                                                            </td>
                                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                                            <td><span class="badge <?= $badgeClass ?> p-2"><?= htmlspecialchars($user['role']) ?></span></td>
                                                            <td><?= htmlspecialchars($user['contact_number']) ?></td>
                                                    
                                                            <td class="text-center">
                                                                <?php if ($is_active): ?>
                                                                        <span class="badge badge-success px-3 py-2" style="background-color: #74c69d;">Active</span>
                                                                <?php else: ?>
                                                                        <span class="badge badge-secondary px-3 py-2">Deactivated</span>
                                                                <?php endif; ?>
                                                            </td>
                                                    
                                                            <td class="text-center">
                                                                <?php if ($is_active): ?>
                                                                        <button class="btn btn-warning btn-sm text-dark shadow-sm" data-toggle="modal" data-target="#editUser<?= $user['userid'] ?>" title="Edit User">
                                                                            <i class="fas fa-edit"></i>
                                                                        </button>
                                                            
                                                                        <button class="btn btn-info btn-sm text-white shadow-sm" data-toggle="modal" data-target="#resetPass<?= $user['userid'] ?>" title="Reset Password">
                                                                            <i class="fas fa-key"></i>
                                                                        </button>

                                                                        <?php if ($user['userid'] != $current_admin_id): ?>
                                                                                <button class="btn btn-danger btn-sm shadow-sm btn-deactivate" data-id="<?= $user['userid'] ?>" data-name="<?= htmlspecialchars($user['fullname']) ?>" title="Deactivate User">
                                                                                    <i class="fas fa-user-slash"></i>
                                                                                </button>
                                                                        <?php endif; ?>

                                                                <?php else: ?>
                                                                        <button class="btn btn-success btn-sm shadow-sm btn-reactivate" data-id="<?= $user['userid'] ?>" data-name="<?= htmlspecialchars($user['fullname']) ?>" title="Reactivate User">
                                                                            <i class="fas fa-user-check mr-1"></i> Reactivate
                                                                        </button>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>

                                                        <div class="modal fade" id="editUser<?= $user['userid'] ?>" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content" style="border-radius: 15px;">
                                                                    <div class="modal-header text-white" style="background-color: #f6c23e;">
                                                                        <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-user-edit mr-2"></i>Edit User Account</h5>
                                                                        <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                                                                    </div>
                                                                    <form method="POST">
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="userid" value="<?= $user['userid'] ?>">
                                                                            <div class="form-group mb-3">
                                                                                <label class="small font-weight-bold text-muted">Full Name</label>
                                                                                <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                                                                            </div>
                                                                            <div class="form-group mb-3">
                                                                                <label class="small font-weight-bold text-muted">System Role</label>
                                                                                <select name="role" class="form-control" required>
                                                                                    <option value="Administrator" <?= ($user['role'] == 'Administrator') ? 'selected' : '' ?>>Administrator (MAO Head)</option>
                                                                                    <option value="Support Staff" <?= ($user['role'] == 'Support Staff') ? 'selected' : '' ?>>Support Staff (Office)</option>
                                                                                    <option value="Field Vaccinator" <?= ($user['role'] == 'Field Vaccinator') ? 'selected' : '' ?>>Field Vaccinator</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="form-group mb-3">
                                                                                <label class="small font-weight-bold text-muted">Contact Number</label>
                                                                                <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($user['contact_number']) ?>" maxlength="11" minlength="11" pattern="[0-9]{11}" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer border-0">
                                                                            <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                                                                            <button type="submit" name="update_user" class="btn btn-warning text-dark font-weight-bold">Save Changes</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="modal fade" id="resetPass<?= $user['userid'] ?>" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content" style="border-radius: 15px;">
                                                                    <div class="modal-header text-white" style="background-color: #17a2b8;">
                                                                        <h5 class="modal-title font-weight-bold"><i class="fas fa-shield-alt mr-2"></i>Admin Override: Reset Password</h5>
                                                                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                                    </div>
                                                                    <form method="POST">
                                                                        <div class="modal-body p-4">
                                                                            <div class="alert alert-warning small">
                                                                                <i class="fas fa-exclamation-triangle mr-1"></i> You are about to forcibly change the password for <strong><?= htmlspecialchars($user['fullname']) ?></strong>.
                                                                            </div>
                                                                            <input type="hidden" name="userid" value="<?= $user['userid'] ?>">
                                                                            <input type="hidden" name="target_fullname" value="<?= htmlspecialchars($user['fullname']) ?>">
                                                                    
                                                                            <div class="form-group mb-3 password-wrapper">
                                                                                <label class="small font-weight-bold text-dark">Type New Password</label>
                                                                                <input type="password" name="new_password" class="form-control bg-light reset-pw-input" minlength="8" placeholder="Minimum 8 characters" required>
                                                                                <i class="fas fa-eye reset-pw-toggle" title="Show/Hide"></i>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer border-0 bg-light">
                                                                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                                                                            <button type="submit" name="reset_password" class="btn btn-info font-weight-bold">Update Password</button>
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

    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px; border: none;">
                <form method="POST">
                    <div class="modal-header text-white" style="background-color: #2d6a4f; border-top-left-radius: 15px; border-top-right-radius: 15px;">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-user-plus mr-2"></i>Register New Personnel</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle mr-1"></i> Default password for new users is <strong>vaxin2026</strong>.
                        </div>
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold" style="color: #1b4332;">Full Name</label>
                            <input type="text" name="fullname" class="form-control bg-light" placeholder="Juan Dela Cruz" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold" style="color: #1b4332;">Username</label>
                            <input type="text" name="username" class="form-control bg-light" placeholder="juan.delacruz" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold" style="color: #1b4332;">System Role</label>
                            <select name="role" class="form-control bg-light" required>
                                <option value="">Select a Role...</option>
                                <option value="Administrator">Administrator (MAO Head)</option>
                                <option value="Support Staff">Support Staff (Office)</option>
                                <option value="Field Vaccinator">Field Vaccinator</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold" style="color: #1b4332;">Contact Number</label>
                            <input type="text" name="contact" class="form-control bg-light" placeholder="09XXXXXXXXX" maxlength="11" minlength="11" pattern="[0-9]{11}" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn text-white px-4" style="background-color: #1b4332;">Register User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="deactivateForm" method="POST" style="display: none;">
        <input type="hidden" name="userid" id="deactivateUserId">
        <input type="hidden" name="deactivate_user" value="1">
    </form>

    <form id="reactivateForm" method="POST" style="display: none;">
        <input type="hidden" name="userid" id="reactivateUserId">
        <input type="hidden" name="reactivate_user" value="1">
    </form>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include './../template/script.php'; ?>
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#userTable').DataTable({
                "pageLength": 10,
                "language": { "search": "Search User:" }
            });

            // Password Toggle for Reset Password Modal
            $('.reset-pw-toggle').on('click', function() {
                let input = $(this).siblings('.reset-pw-input');
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    input.attr('type', 'password');
                    $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            // DEACTIVATE ACTION
            $('.btn-deactivate').on('click', function(e) {
                e.preventDefault();
                const userId = $(this).data('id');
                const userName = $(this).data('name');

                Swal.fire({
                    title: 'Deactivate Account?',
                    html: `Are you sure you want to revoke access for <strong>${userName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Deactivate',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#deactivateUserId').val(userId);
                        $('#deactivateForm').submit();
                    }
                });
            });

            // REACTIVATE ACTION
            $('.btn-reactivate').on('click', function(e) {
                e.preventDefault();
                const userId = $(this).data('id');
                const userName = $(this).data('name');

                Swal.fire({
                    title: 'Restore Account?',
                    html: `Are you sure you want to reactivate access for <strong>${userName}</strong>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2d6a4f',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Reactivate',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#reactivateUserId').val(userId);
                        $('#reactivateForm').submit();
                    }
                });
            });
        });
    </script>
</body>
</html>