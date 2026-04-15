<?php
include '../../config.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include './../template/header.php' ?>
    <style>
        .security-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .security-sidebar {
            background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .security-icon-circle {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 40px;
            margin-bottom: 20px;
            border: 2px solid #74c69d;
        }

        /* Input Group for Show/Hide Eye Icon */
        .input-group-text {
            cursor: pointer;
            background-color: transparent;
            border-left: none;
        }

        .form-control {
            border-right: none;
        }

        .form-control:focus+.input-group-append .input-group-text {
            border-color: #2d6a4f;
            /* Green Agri focus */
            color: #1b4332;
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

                $current_userid = $_SESSION['userid'];

                // --- PHP PROCESSING LOGIC (Change Password) ---
                if (isset($_POST['change_password'])) {
                    $current_pass = $_POST['current_password'];
                    $new_pass = $_POST['new_password'];
                    $confirm_pass = $_POST['confirm_password'];

                    // 1. Check if New Password and Confirm Password match
                    if ($new_pass !== $confirm_pass) {
                        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "warning", title: "Passwords Do Not Match", text: "Your new password and confirmation password must be exactly the same.", confirmButtonColor: "#1b4332" });
            });
        </script>';
                    }
                    // 2. Check password length (Enterprise standard: min 8 characters)
                    else if (strlen($new_pass) < 8) {
                        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "warning", title: "Weak Password", text: "Your new password must be at least 8 characters long.", confirmButtonColor: "#1b4332" });
            });
        </script>';
                    } else {
                        // 3. Verify Current Password from Database
                        $stmt = $conn->prepare("SELECT password FROM user_tbl WHERE userid = ?");
                        $stmt->bind_param("i", $current_userid);
                        $stmt->execute();
                        $res = $stmt->get_result();

                        if ($row = $res->fetch_assoc()) {
                            if (password_verify($current_pass, $row['password'])) {
                                // If verified, hash the new password and update
                                $new_hashed_password = password_hash($new_pass, PASSWORD_BCRYPT);

                                $update_stmt = $conn->prepare("UPDATE user_tbl SET password = ? WHERE userid = ?");
                                $update_stmt->bind_param("si", $new_hashed_password, $current_userid);

                                if ($update_stmt->execute()) {
                                    echo '<script>
                        document.addEventListener("DOMContentLoaded", function () {
                            Swal.fire({ 
                                icon: "success", 
                                title: "Password Updated!", 
                                text: "Your account is now secured with your new password.", 
                                confirmButtonColor: "#1b4332", 
                                timer: 2500 
                            }).then(() => {
                                window.location.href = "profile.php"; // Redirect back to profile
                            });
                        });
                    </script>';
                                }
                                $update_stmt->close();
                            } else {
                                // Incorrect Current Password
                                echo '<script>
                    document.addEventListener("DOMContentLoaded", function () {
                        Swal.fire({ icon: "error", title: "Authentication Failed", text: "The current password you entered is incorrect.", confirmButtonColor: "#1b4332" });
                    });
                </script>';
                            }
                        }
                        $stmt->close();
                    }
                }
                ?>
                <div class="container-fluid pt-4">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 fw-bold" style="color: #1b4332 !important;"><i
                                class="fas fa-shield-alt mr-2"></i>Account Security</h1>
                        <a href="profile.php" class="btn btn-outline-secondary btn-sm font-weight-bold shadow-sm"
                            style="border-radius: 8px;">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Profile
                        </a>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-xl-8 col-lg-10">
                            <div class="card security-card">
                                <div class="row no-gutters">

                                    <div class="col-md-5 security-sidebar">
                                        <div class="security-icon-circle">
                                            <i class="fas fa-lock"></i>
                                        </div>
                                        <h4 class="font-weight-bold mb-3">Secure Your Account</h4>
                                        <p class="small text-light mb-0">It is a good practice to change your password
                                            every 90 days.</p>
                                        <ul class="text-left small mt-4 text-light" style="opacity: 0.9;">
                                            <li class="mb-2">Must be at least 8 characters.</li>
                                            <li class="mb-2">Do not use dictionary words.</li>
                                            <li>Never share your password with anyone.</li>
                                        </ul>
                                    </div>

                                    <div class="col-md-7 p-5">
                                        <h5 class="font-weight-bold mb-4" style="color: #1b4332;">Change Password</h5>

                                        <form method="POST">

                                            <div class="form-group mb-4">
                                                <label class="small font-weight-bold text-muted">Current Password <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" name="current_password" id="current_pass"
                                                        class="form-control" placeholder="Enter your current password"
                                                        required>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text toggle-password"
                                                            data-target="#current_pass">
                                                            <i class="fas fa-eye"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <hr class="mb-4">

                                            <div class="form-group mb-3">
                                                <label class="small font-weight-bold text-muted">New Password <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" name="new_password" id="new_pass"
                                                        class="form-control" placeholder="Create a new password"
                                                        required minlength="8">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text toggle-password"
                                                            data-target="#new_pass">
                                                            <i class="fas fa-eye"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group mb-4">
                                                <label class="small font-weight-bold text-muted">Confirm New Password
                                                    <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" name="confirm_password" id="confirm_pass"
                                                        class="form-control" placeholder="Re-type your new password"
                                                        required minlength="8">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text toggle-password"
                                                            data-target="#confirm_pass">
                                                            <i class="fas fa-eye"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="text-right mt-4">
                                                <button type="submit" name="change_password"
                                                    class="btn text-white px-4 font-weight-bold w-100"
                                                    style="background-color: #1b4332; border-radius: 8px;">
                                                    <i class="fas fa-key mr-2"></i> Update Password
                                                </button>
                                            </div>
                                        </form>

                                    </div>
                                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.querySelectorAll('.toggle-password').forEach(function (icon) {
            icon.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const inputField = document.querySelector(targetId);
                const iconElement = this.querySelector('i');

                if (inputField.type === "password") {
                    inputField.type = "text";
                    iconElement.classList.remove('fa-eye');
                    iconElement.classList.add('fa-eye-slash');
                } else {
                    inputField.type = "password";
                    iconElement.classList.remove('fa-eye-slash');
                    iconElement.classList.add('fa-eye');
                }
            });
        });
    </script>
</body>

</html>