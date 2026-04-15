<?php
session_start();
date_default_timezone_set("Asia/Manila");

include './config.php';

// 1. TATAWAGIN NATIN ANG LOGGER BAGO BURAHIN ANG SESSION
// Sine-siguro natin na may nakalog-in bago tayo mag-log
if (isset($_SESSION['userid']) && isset($_SESSION['fullname'])) {
    $userid = $_SESSION['userid'];
    $fullname = $_SESSION['fullname'];
    
    // Gamitin ang Global Logger mula sa config.php
    logSystemActivity($conn, $userid, $fullname, "Successfully logged out of the system.");
}

// 2. PREPARE REDIRECT URL
$encodedUrl = base64_encode("./");

// 3. SECURELY DESTROY THE SESSION
session_unset(); // Tanggalin lahat ng laman ng $_SESSION variables
session_destroy(); // Sirain ang session file sa server

// 4. REDIRECT TO LOGIN PAGE
header('location:/vax-in/?redirect=' . urlencode($encodedUrl));
exit();
?>