function logActivity($conn,$activity){

    $username = $_SESSION['username'];

    mysqli_query($conn,"
    INSERT INTO activity_log(user,activity,date)
    VALUES('$username','$activity',NOW())
    ");
}