<?php
include '../../config.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include './../template/header.php' ?>
    <style>
        .profile-header-card {
            background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);
            color: white;
            border-radius: 15px;
            text-align: center;
            padding: 40px 20px;
            box-shadow: 0 10px 20px rgba(27, 67, 50, 0.15);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid #74c69d;
            /* Light Mint Green border */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 15px;
            background-color: #fff;
        }

        .edit-card {
            border-radius: 15px;
            border: none;
            border-top: 4px solid #2d6a4f;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .form-control:disabled,
        .form-control[readonly] {
            background-color: #e9ecef;
            opacity: 1;
            font-weight: bold;
            color: #6c757d;
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

                // --- PHP PROCESSING LOGIC (Update Profile) ---
                if (isset($_POST['update_profile'])) {
                    $fullname = trim(ucwords($_POST['fullname']));
                    $contact = trim($_POST['contact']);

                    $sql = "UPDATE user_tbl SET fullname = ?, contact_number = ? WHERE userid = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssi", $fullname, $contact, $current_userid);

                    if ($stmt->execute()) {
                        // Update the session variable so the Navbar updates immediately
                        $_SESSION['fullname'] = $fullname;

                        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ 
                    icon: "success", 
                    title: "Profile Updated", 
                    text: "Your personal information has been saved successfully.", 
                    confirmButtonColor: "#1b4332", 
                    timer: 2000 
                });
            });
        </script>';
                    } else {
                        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({ icon: "error", title: "Update Failed", text: "Something went wrong. Please try again.", confirmButtonColor: "#1b4332" });
            });
        </script>';
                    }
                    $stmt->close();
                }

                // FETCH CURRENT USER DETAILS
                $user_sql = "SELECT * FROM user_tbl WHERE userid = ?";
                $user_stmt = $conn->prepare($user_sql);
                $user_stmt->bind_param("i", $current_userid);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();

                if ($user_result->num_rows === 1) {
                    $user_data = $user_result->fetch_assoc();
                } else {
                    die("User record corrupted or missing.");
                }
                $user_stmt->close();
                ?>
                <div class="container-fluid pt-4">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 fw-bold" style="color: #1b4332 !important;"><i
                                class="fas fa-user-circle mr-2"></i>My Profile</h1>
                        <p class="mb-0 text-muted">Manage your MAO VAX-IN account details.</p>
                    </div>

                    <div class="row">
                        <div class="col-xl-4 col-lg-5 mb-4">
                            <div class="profile-header-card">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_data['fullname']) ?>&background=ffffff&color=1b4332&size=128&bold=true"
                                    alt="Profile Picture" class="profile-avatar">

                                <h4 class="font-weight-bold mb-1"><?= htmlspecialchars($user_data['fullname']) ?></h4>
                                <p class="mb-3 text-light"><i
                                        class="fas fa-id-badge mr-2"></i><?= htmlspecialchars($user_data['role']) ?></p>

                                <div class="mt-4 pt-4" style="border-top: 1px solid rgba(255,255,255,0.2);">
                                    <div class="row text-center">
                                        <div class="col-6 border-right"
                                            style="border-color: rgba(255,255,255,0.2) !important;">
                                            <p class="mb-0 small text-uppercase text-light">Status</p>
                                            <h6 class="font-weight-bold mb-0 text-white"><i
                                                    class="fas fa-circle text-success mr-1"
                                                    style="font-size: 10px;"></i> Active</h6>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-0 small text-uppercase text-light">Joined Date</p>
                                            <h6 class="font-weight-bold mb-0 text-white">
                                                <?= date('M Y', strtotime($user_data['dateCreated'])) ?></h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-8 col-lg-7 mb-4">
                            <div class="card edit-card">
                                <div class="card-header bg-white py-3">
                                    <h6 class="m-0 font-weight-bold" style="color: #1b4332;">Personal Information</h6>
                                </div>
                                <div class="card-body p-4">
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-12 mb-4">
                                                <div class="alert alert-info small border-0 bg-light"
                                                    style="color: #2d6a4f;">
                                                    <i class="fas fa-info-circle mr-2"></i> <strong>Note:</strong> Your
                                                    Username and System Role are locked by the Administrator for
                                                    security and audit purposes.
                                                </div>
                                            </div>

                                            <div class="form-group col-md-6 mb-4">
                                                <label class="small font-weight-bold text-muted">Full Name</label>
                                                <input type="text" name="fullname" class="form-control"
                                                    value="<?= htmlspecialchars($user_data['fullname']) ?>" required>
                                            </div>

                                            <div class="form-group col-md-6 mb-4">
                                                <label class="small font-weight-bold text-muted">Contact Number</label>
                                                <input type="text" name="contact" class="form-control"
                                                    value="<?= htmlspecialchars($user_data['contact_number']) ?>"
                                                    placeholder="e.g., 09XX-XXX-XXXX">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="form-group col-md-6 mb-4">
                                                <label class="small font-weight-bold text-muted">Username <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control"
                                                    value="<?= htmlspecialchars($user_data['username']) ?>" readonly
                                                    title="Cannot be changed">
                                                <small class="text-muted">Used for logging into the system.</small>
                                            </div>

                                            <div class="form-group col-md-6 mb-4">
                                                <label class="small font-weight-bold text-muted">System Role <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control"
                                                    value="<?= htmlspecialchars($user_data['role']) ?>" readonly>
                                            </div>
                                        </div>

                                        <hr class="mt-2 mb-4">

                                        <div class="d-flex justify-content-between align-items-center">
                                            <a href="changepassword.php"
                                                class="btn btn-outline-secondary btn-sm font-weight-bold">
                                                <i class="fas fa-key mr-2"></i> Update Password
                                            </a>
                                            <button type="submit" name="update_profile"
                                                class="btn text-white px-5 font-weight-bold"
                                                style="background-color: #1b4332; border-radius: 8px;">
                                                <i class="fas fa-save mr-2"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>
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
</body>

</html>