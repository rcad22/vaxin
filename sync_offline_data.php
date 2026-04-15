<?php
// Set headers to accept JSON from Javascript
header('Content-Type: application/json');
include 'config.php';

// Kunin ang raw JSON data na ipinasa ng Javascript
$json_data = file_get_contents('php://input');
$records = json_decode($json_data, true);

if (empty($records)) {
    echo json_encode(['status' => 'error', 'message' => 'No data received.']);
    exit();
}

$success_count = 0;
$error_count = 0;

foreach ($records as $record) {

    // 1. CLEAN ALL INPUTS (To prevent SQL Injection)
    $owner_name = mysqli_real_escape_string($conn, $record['owner_name']);
    $contact_no = mysqli_real_escape_string($conn, $record['contact_no']);
    $owner_gender = mysqli_real_escape_string($conn, $record['owner_gender']);
    $owner_birthday = !empty($record['owner_birthday']) ? $record['owner_birthday'] : null;
    $is_pwd = (int) $record['is_pwd'];
    $is_senior = (int) $record['is_senior'];
    $barangay_id = (int) $record['barangay_id'];

    // ANG BAGONG SIGNATURE (Base64 String)
    $owner_signature = $record['owner_signature'] ?? '';

    $animal_name = mysqli_real_escape_string($conn, $record['animal_name']);
    $species_id = (int) $record['species_id'];
    $birth_date = !empty($record['birth_date']) ? $record['birth_date'] : null;
    $sex = mysqli_real_escape_string($conn, $record['sex']);
    $breed = mysqli_real_escape_string($conn, $record['breed']);
    $color = mysqli_real_escape_string($conn, $record['color']);
    $is_stray = (int) $record['is_stray'];
    $is_fixed = (int) $record['is_fixed'];

    $vaccine_id = (int) $record['vaccine_id'];
    $remarks = mysqli_real_escape_string($conn, $record['remarks']);
    $vaccinator_id = (int) $record['vaccinator_id'];

    // ANG BAGONG BARANGAY OFFICIAL DATA
    $official_name = mysqli_real_escape_string($conn, $record['official_name'] ?? '');
    $official_designation = mysqli_real_escape_string($conn, $record['official_designation'] ?? '');

    $vaccination_date = date('Y-m-d H:i:s', strtotime($record['date_recorded']));

    // 2. GENERATE ANIMAL ID
    $brgy_query = mysqli_query($conn, "SELECT barangay_code FROM barangay_tbl WHERE barangay_id = '$barangay_id'");
    $brgy_code = "UNK";
    if ($brgy_row = mysqli_fetch_assoc($brgy_query)) {
        $brgy_code = $brgy_row['barangay_code'];
    }

    $year = date("Y");
    $rand_num = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $animal_id_tag = $brgy_code . "-" . $year . "-" . $rand_num;

    // 3. INSERT INTO ANIMAL_TBL (Kasama na ang owner_signature)
    $sql_animal = "INSERT INTO animal_tbl (
                    animal_id_tag, owner_name, contact_no, barangay_id, owner_gender, owner_birthday, is_pwd, is_senior, owner_signature,
                    animal_name, species_id, breed, sex, color, birth_date, is_stray, is_fixed, remarks
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql_animal);

    $animal_remarks = $remarks ? $remarks . " (Synced Offline)" : "(Synced Offline)";

    // Ang parameters format (sssisssisssssssiis)
    $stmt->bind_param(
        "sssisssisssssssiis",
        $animal_id_tag,
        $owner_name,
        $contact_no,
        $barangay_id,
        $owner_gender,
        $owner_birthday,
        $is_pwd,
        $is_senior,
        $owner_signature,
        $animal_name,
        $species_id,
        $breed,
        $sex,
        $color,
        $birth_date,
        $is_stray,
        $is_fixed,
        $animal_remarks
    );

    if ($stmt->execute()) {
        $new_animal_id = mysqli_insert_id($conn);

        // 4. INSERT INTO VACCINATION_TBL (Kasama na ang official_name at official_designation)
        $sql_vax = "INSERT INTO vaccination_tbl (animal_id, vaccine_id, vaccination_date, vaccinator_id, remarks, official_name, official_designation) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt_vax = $conn->prepare($sql_vax);
        $vax_remarks = "OFFLINE SYNC";

        $stmt_vax->bind_param("iisisss", $new_animal_id, $vaccine_id, $vaccination_date, $vaccinator_id, $vax_remarks, $official_name, $official_designation);

        if ($stmt_vax->execute()) {
            $success_count++;
        } else {
            $error_count++;
        }
        $stmt_vax->close();

    } else {
        $error_count++;
    }
    $stmt->close();
}

// Magpadala ng reply pabalik sa Javascript
if ($success_count > 0) {
    // LOG IT TO SYSTEM (Sprint 1 Integration)
    logSystemActivity($conn, $vaccinator_id, "Field Vaccinator", "Successfully synced $success_count offline records to database.");

    echo json_encode([
        'status' => 'success',
        'message' => "Successfully synced $success_count animal records and vaccinations to the main database!"
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => "Failed to sync records. Please check database connection."
    ]);
}
?>