<?php
session_start();
date_default_timezone_set("Asia/Manila");
include 'config.php';

if (isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $lockoutTime = 10 * 60;
    $maxLoginAttempts = 3;

    $currentTime = time();
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    $sql = "SELECT * FROM login_attempts WHERE ip_address = '$ipAddress'";
    $result = mysqli_query($conn, $sql);

    $failedAttemptsCount = mysqli_num_rows($result);

    if ($failedAttemptsCount >= $maxLoginAttempts) {
        $lastAttemptTime = mysqli_fetch_assoc($result)['last_attempt'];
        $remainingLockoutTime = $lastAttemptTime + $lockoutTime - $currentTime;

        if ($remainingLockoutTime > 0) {
            $minutesRemaining = ceil($remainingLockoutTime / 60);

            // LOG SECURITY EVENT: System locked due to brute force attempt
            logSystemActivity($conn, 'SYSTEM', $username, "System locked IP address for $minutesRemaining mins due to multiple failed attempts.", "Failed");

            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({
                        title: "System Locked",
                        text: "Too many failed attempts. Try again after ' . $minutesRemaining . ' minutes.",
                        icon: "error",
                        confirmButtonText: "Understood",
                        confirmButtonColor: "#1b4332",
                        backdrop: `rgba(27,67,50,0.8)`,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                    });
                });
            </script>';
        } else {
            $sql = "DELETE FROM login_attempts WHERE ip_address = '$ipAddress'";
            mysqli_query($conn, $sql);
        }
    } else {
        $sql = "SELECT * FROM user_tbl WHERE BINARY username = '$username' AND useractive = '1'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);
            $usertype = $row['usertype'];

            if (password_verify($password, $row['password'])) {
                $sql = "DELETE FROM login_attempts WHERE ip_address = '$ipAddress'";
                mysqli_query($conn, $sql);

                // ==============================================================
                // ENTERPRISE UPGRADE: GAMITIN ANG GLOBAL LOGGER PARA SA SUCCESS!
                // ==============================================================
                logSystemActivity($conn, $row['userid'], $username, "Successfully logged into the system.");

                $encodedUrl = base64_encode("./user/accounts/");

                $_SESSION['userid'] = $row['userid'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['fullname'] = $row['fullname'];

                // ENTERPRISE UPGRADE: Save user credentials to LocalStorage for future Offline Access
                echo '<script>
                    document.addEventListener("DOMContentLoaded", function () {
                        
                        // 1. Save data to browser for OFFLINE MODE
                        const offlineUserData = {
                            userid: "' . $row['userid'] . '",
                            role: "' . $row['role'] . '",
                            fullname: "' . $row['fullname'] . '"
                        };
                        localStorage.setItem("vaxin_offline_user", JSON.stringify(offlineUserData));

                        // 2. Standard Online Login Animation
                        const Toast = Swal.mixin({
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener("mouseenter", Swal.stopTimer)
                                toast.addEventListener("mouseleave", Swal.resumeTimer)
                            }
                        });

                        Toast.fire({
                            icon: "success",
                            title: "Authentication Verified. Entering VAX-IN..."
                        });

                        setTimeout(function () {
                            window.location.href = atob("' . $encodedUrl . '");
                        }, 2000);
                    });
                </script>';
            } else {
                $sql = "INSERT INTO login_attempts (ip_address, last_attempt) VALUES ('$ipAddress', $currentTime)";
                mysqli_query($conn, $sql);

                // ==============================================================
                // ENTERPRISE UPGRADE: I-RECORD ANG MALING PASSWORD (HACK ATTEMPT)
                // ==============================================================
                logSystemActivity($conn, $row['userid'], $username, "Failed login attempt (Incorrect Password)", "Failed");

                echo '<script>
                    document.addEventListener("DOMContentLoaded", function () {
                        Swal.fire({
                            title: "Invalid Credentials",
                            text: "The username or password you entered is incorrect.",
                            icon: "warning",
                            confirmButtonColor: "#1b4332",
                            confirmButtonText: "Try Again",
                        });
                    });
                </script>';
            }
        } else {
            // ==============================================================
            // ENTERPRISE UPGRADE: I-RECORD KUNG WALANG GANYANG USERNAME
            // ==============================================================
            logSystemActivity($conn, 'UNKNOWN', $username, "Failed login attempt (Account not found or inactive)", "Failed");

            echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    title: "Access Denied",
                    text: "Account not found or currently inactive.",
                    icon: "warning",
                    confirmButtonColor: "#1b4332",
                    confirmButtonText: "Okay",
                });
            });
        </script>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>VAX-IN | Secure Login</title>
    <link href="img/logo.png" rel="icon">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        /* (KEEP ALL YOUR EXISTING CSS HERE) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            overflow: hidden;
            background-color: #f4f7f6;
        }

        .branding-side {
            flex: 1.2;
            background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .form-side {
            flex: 1;
            background: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.05);
            z-index: 2;
        }

        .brand-content {
            text-align: center;
            z-index: 2;
            animation: fadeIn 1s ease-out;
        }

        .brand-logo {
            width: 150px;
            height: 150px;
            background: white;
            border-radius: 50%;
            padding: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            border: 4px solid #74c69d;
            transition: transform 0.3s ease;
        }

        .brand-logo:hover {
            transform: scale(1.05);
        }

        .brand-title {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .brand-subtitle {
            font-size: 1.1rem;
            color: #b7e4c7;
            font-weight: 300;
        }

        .mascot-container {
            margin-top: 40px;
            font-size: 5rem;
            color: #74c69d;
            position: relative;
            height: 100px;
            transition: all 0.3s ease;
        }

        #mascot-icon {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            top: -100px;
            left: -100px;
        }

        .shape-2 {
            width: 300px;
            height: 300px;
            bottom: -50px;
            right: -50px;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            animation: slideInRight 0.8s ease-out;
        }

        .form-header {
            margin-bottom: 40px;
        }

        .form-header h2 {
            color: #1b4332;
            font-weight: 700;
            font-size: 2rem;
        }

        .form-header p {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .form-floating {
            position: relative;
            margin-bottom: 25px;
        }

        .form-floating input {
            width: 100%;
            padding: 18px 20px 18px 50px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            background: #f8f9fa;
            font-size: 1rem;
            color: #2d3436;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-floating label {
            position: absolute;
            top: 50%;
            left: 50px;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 1rem;
            pointer-events: none;
            transition: all 0.2s ease-out;
        }

        .form-floating i.icon-prefix {
            position: absolute;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
            color: #2d6a4f;
            font-size: 1.2rem;
            transition: 0.3s;
        }

        .form-floating input:focus,
        .form-floating input:not(:placeholder-shown) {
            border-color: #2d6a4f;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(45, 106, 79, 0.08);
        }

        .form-floating input:focus~label,
        .form-floating input:not(:placeholder-shown)~label {
            top: 0;
            left: 45px;
            transform: translateY(-50%) scale(0.85);
            background: #ffffff;
            padding: 0 8px;
            color: #1b4332;
            font-weight: 600;
        }

        .form-floating input:focus~i.icon-prefix {
            color: #1b4332;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #adb5bd;
            transition: 0.3s;
            font-size: 1.1rem;
            padding: 5px;
        }

        .toggle-password:hover {
            color: #1b4332;
        }

        #capsLockWarning {
            display: none;
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            animation: fadeIn 0.3s;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            border: none;
            background: #1b4332;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(27, 67, 50, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .btn-login:hover {
            background: #2d6a4f;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(27, 67, 50, 0.3);
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 992px) {
            body {
                flex-direction: column;
            }

            .branding-side {
                flex: 0.8;
                padding: 40px 20px;
            }

            .form-side {
                border-top-left-radius: 30px;
                border-top-right-radius: 30px;
                margin-top: -30px;
                padding: 50px 20px;
            }

            .brand-logo {
                width: 100px;
                height: 100px;
            }

            .brand-title {
                font-size: 2rem;
            }

            .mascot-container {
                font-size: 3.5rem;
                margin-top: 20px;
            }
        }

        /* NEW CSS FOR OFFLINE MODE BOX */
        #offlineBox {
            display: none;
            text-align: center;
            animation: fadeIn 1s;
        }

        .offline-btn {
            background-color: #e63946;
            margin-top: 20px;
        }

        .offline-btn:hover {
            background-color: #c1121f;
        }
    </style>
</head>

<body>
    <div class="branding-side">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="brand-content">
            <img src="./user/img/logo.png" class="brand-logo" alt="VAX-IN Logo"
                onerror="this.src='https://cdn-icons-png.flaticon.com/512/2983/2983748.png'">
            <h1 class="brand-title">VAX-IN</h1>
            <p class="brand-subtitle" id="connectionStatusText"> Mogpog MAO Vaccination Information System</p>
            <div class="mascot-container">
                <i class="fas fa-dog" id="mascot-icon"></i>
            </div>
        </div>
    </div>

    <div class="form-side">
        <div class="login-box">

            <div id="onlineBox">
                <div class="form-header">
                    <h2>Welcome</h2>
                    <p>Please enter your authorized credentials to access the Mogpog MAO system.</p>
                </div>
                <form method="POST" autocomplete="off" id="loginForm">
                    <div class="form-floating">
                        <input type="text" name="username" id="username" placeholder=" " required>
                        <label for="username">Username</label>
                        <i class="fas fa-user icon-prefix"></i>
                    </div>
                    <div class="form-floating">
                        <input type="password" name="password" id="password" placeholder=" " required>
                        <label for="password"> Password</label>
                        <i class="fas fa-lock icon-prefix"></i>
                        <i class="fas fa-eye toggle-password" id="togglePassword" title="Show/Hide Password"></i>
                    </div>
                    <div id="capsLockWarning"><i class="fas fa-exclamation-triangle me-1"></i> Caps Lock is turned ON.
                    </div>
                    <button type="submit" name="submit" class="btn-login" id="loginBtn">
                        <span>Secure Login</span> <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>

            <div id="offlineBox">
                <div class="form-header">
                    <h2 style="color: #e63946;"><i class="fas fa-wifi-slash mr-2"></i>Offline Mode</h2>
                    <p>No internet connection detected.</p>
                </div>

                <div class="alert alert-warning text-left small">
                    <i class="fas fa-info-circle mr-2"></i> You are currently offline. The system recognizes a
                    previously saved session. You can proceed to record data, which will sync when you are back online.
                </div>

                <h5 class="font-weight-bold mt-4" id="offlineUserName" style="color: #1b4332;">User Name</h5>
                <p class="text-muted small" id="offlineUserRole">System Role</p>

                <button type="button" class="btn-login offline-btn"
                    onclick="window.location.href='offline_field_mode.html'">
                    <span>Proceed to Field Data Entry</span> <i class="fas fa-satellite-dish"></i>
                </button>
            </div>

        </div>
    </div>

    <script>
        // (Keep your Mascot and Toggle Password Logic Here)
        const mascotIcon = document.getElementById('mascot-icon');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');

        usernameInput.addEventListener('focus', () => { mascotIcon.className = 'fas fa-cat'; mascotIcon.style.transform = 'scale(1.1) translateY(-10px)'; mascotIcon.style.color = '#fff'; });
        usernameInput.addEventListener('blur', () => { mascotIcon.className = 'fas fa-dog'; mascotIcon.style.transform = 'scale(1) translateY(0)'; mascotIcon.style.color = '#74c69d'; });
        passwordInput.addEventListener('focus', () => { mascotIcon.className = 'fas fa-paw'; mascotIcon.style.transform = 'rotate(-20deg) scale(1.2)'; mascotIcon.style.color = '#b7e4c7'; });
        passwordInput.addEventListener('blur', () => { mascotIcon.className = 'fas fa-dog'; mascotIcon.style.transform = 'rotate(0deg) scale(1)'; mascotIcon.style.color = '#74c69d'; });

        const togglePassword = document.querySelector('#togglePassword');
        togglePassword.addEventListener('click', function (e) {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye'); this.classList.toggle('fa-eye-slash');
            if (type === 'text') { mascotIcon.className = 'fas fa-surprise'; mascotIcon.style.color = '#fff'; }
            else { mascotIcon.className = 'fas fa-paw'; mascotIcon.style.color = '#b7e4c7'; }
        });

        const capsLockWarning = document.getElementById("capsLockWarning");
        passwordInput.addEventListener("keyup", function (event) {
            if (event.getModifierState("CapsLock")) capsLockWarning.style.display = "block";
            else capsLockWarning.style.display = "none";
        });

        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('loginBtn');
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> <span>Authenticating...</span>';
            btn.style.opacity = '0.8'; btn.style.cursor = 'wait'; btn.style.pointerEvents = 'none';
        });

        // =========================================================
        // PWA OFFLINE DETECTOR LOGIC
        // =========================================================
        window.addEventListener('load', function () {
            // Check if device has NO internet
            if (!navigator.onLine) {
                document.getElementById('connectionStatusText').innerHTML = "<i class='fas fa-exclamation-circle text-warning'></i> Offline Field Mode Active";

                // Check if user previously logged in
                const savedData = localStorage.getItem("vaxin_offline_user");

                if (savedData) {
                    // Valid previous session found! Show the Offline Dashboard Button.
                    const user = JSON.parse(savedData);
                    document.getElementById('onlineBox').style.display = 'none';
                    document.getElementById('offlineBox').style.display = 'block';

                    document.getElementById('offlineUserName').innerText = user.fullname;
                    document.getElementById('offlineUserRole').innerText = user.role;
                } else {
                    // No internet AND no saved session. Cannot log in.
                    Swal.fire({
                        title: "No Internet",
                        text: "You are offline and have no saved session. Please connect to the internet to log in for the first time.",
                        icon: "error",
                        confirmButtonColor: "#1b4332",
                    });
                }
            }
        });
    </script>
</body>

</html>