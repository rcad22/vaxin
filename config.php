<?php
date_default_timezone_set('Asia/Manila');

// 1. DATABASE CONNECTION WITH ERROR HANDLING
$conn = mysqli_connect("localhost", "root", "", "vaxin_db");

if (!$conn) {
    // Kung hindi maka-connect sa database, ipapakita ang malinis na error message
    die("<strong>System Error:</strong> Unable to connect to the database. Please contact the Administrator.");
}

// =================================================================
// ENTERPRISE GLOBAL LOGGING & ERROR HANDLING FUNCTION
// =================================================================
function logSystemActivity($conn, $userid, $username, $action_made, $status = 'Success') {
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $loginTime = date("Y-m-d H:i:s");
    
    // Gagamit tayo ng Prepared Statements dito para ligtas sa SQL Injection!
    $sql = "INSERT INTO userlogs_tbl (userid, username, action_made, status, login_time, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssss", $userid, $username, $action_made, $status, $loginTime, $ipAddress);
        $stmt->execute();
        $stmt->close();
    }
}
?>