<?php
session_start();
include '../../config.php';

// CYBERSECURITY: Admin at Support Staff lang
if (!isset($_SESSION['userid']) || ($_SESSION['role'] !== 'Administrator' && $_SESSION['role'] !== 'Support Staff')) {
    die("Unauthorized Access.");
}

// Get the user who is printing the report
$prepared_by_name = strtoupper($_SESSION['fullname']);
$prepared_by_role = $_SESSION['role'];

// --- FILTER LOGIC ---
$barangay_id = isset($_GET['barangay_id']) ? $_GET['barangay_id'] : '';
$report_date = isset($_GET['report_date']) ? $_GET['report_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'B';
$vaccinator_id = isset($_GET['vaccinator_id']) ? $_GET['vaccinator_id'] : '';

// Fetch Barangay Name
$brgy_name = "___________________";
if ($barangay_id) {
    $b_query = mysqli_query($conn, "SELECT barangay_name FROM barangay_tbl WHERE barangay_id = '$barangay_id'");
    if ($b_row = mysqli_fetch_assoc($b_query))
        $brgy_name = strtoupper($b_row['barangay_name']);
}

// Ensure "Anti-Rabies" only
$vaccine_name = "ANTI-RABIES VACCINE";

// Build Query to get records for this specific report
$where_clauses = ["DATE(v.vaccination_date) = '$report_date'"];
$where_clauses[] = "(s.species_name LIKE '%dog%' OR s.species_name LIKE '%cat%' OR s.species_name LIKE '%canine%' OR s.species_name LIKE '%feline%')";
$where_clauses[] = "vac.vaccine_name LIKE '%rabies%'";

if ($barangay_id) {
    $where_clauses[] = "a.barangay_id = '$barangay_id'";
}
if ($report_type === 'A' && $vaccinator_id) {
    $where_clauses[] = "v.vaccinator_id = '$vaccinator_id'";
}

$where_sql = "WHERE " . implode(' AND ', $where_clauses);

$sql = "SELECT a.*, s.species_name, v.remarks as vax_remarks, v.status, u.fullname as vaccinator, v.official_name, v.official_designation
        FROM vaccination_tbl v 
        JOIN animal_tbl a ON v.animal_id = a.record_id 
        JOIN species_tbl s ON a.species_id = s.species_id 
        JOIN vaccine_tbl vac ON v.vaccine_id = vac.vaccine_id
        JOIN user_tbl u ON v.vaccinator_id = u.userid
        $where_sql 
        ORDER BY a.owner_name ASC";
$result = mysqli_query($conn, $sql);

// Variables for Option A Signatories
$target_vaccinator_name = "___________________";
$assisting_official_name = "___________________";
$assisting_official_desc = "Barangay Official";
$is_first_row = true;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIMAROPA Accomplishment Report</title>

    <style>
        body { font-family: 'Arial', sans-serif; background-color: #fff; color: #000; padding: 20px; font-size: 11px; }
        .report-header { display: flex; align-items: center; justify-content: center; margin-bottom: 20px; padding-left: 20px; }
        .logo-left { width: 80px; height: 80px; margin-right: 15px; }
        .header-text { display: flex; flex-direction: column; text-align: left; }
        .title-text { font-weight: bold; font-size: 14px; text-transform: uppercase; margin-bottom: 3px; }
        .subtitle-text { font-size: 12px; margin-top: 0; }
        .meta-container { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 12px; }
        .meta-col { width: 45%; }
        .meta-row { display: flex; margin-bottom: 3px; }
        .meta-label { width: 100px; }
        .meta-value { flex-grow: 1; border-bottom: 1px solid #000; padding-left: 5px; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; vertical-align: middle; height: 25px; }
        th { background-color: #f0f0f0; font-weight: bold; font-size: 10px; -webkit-print-color-adjust: exact; }
        .text-left { text-align: left !important; padding-left: 5px; }
        .check-mark { font-family: 'Arial', sans-serif; font-size: 14px; font-weight: bold; }
        .print-signature { max-height: 20px; max-width: 50px; display: block; margin: 0 auto; }

        /* Signatory layout */
        .signatories-container { margin-top: 40px; display: flex; justify-content: space-between; }
        .sig-block { text-align: center; }
        .sig-line { font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 2px; margin-top: 30px; text-transform: uppercase; }
        .sig-role { font-size: 10px; margin-top: 2px; }

        @media print {
            @page { size: legal landscape; margin: 0.5in; }
            body { padding: 0; }
            .btn-print { display: none; }
        }
        .btn-print { text-align: center; margin-bottom: 20px; }
        .btn-print button { background-color: #1b4332; color: #fff; padding: 10px 20px; border: none; font-weight: bold; cursor: pointer; border-radius: 5px; }
    </style>
</head>

<body>

    <div class="btn-print">
        <button onclick="window.print()">Proceed to Print (Set paper to Legal / Long Landscape)</button>
    </div>

    <div class="report-header">
        <img src="logo.png" class="logo-left" alt="Logo" onerror="this.style.display='none'">
        <div class="header-text">
            <div class="title-text">MIMAROPA INITIATIVE: "ONE TIME BIG TIME" Regionwide Rabies Eradication Effort</div>
            <div class="subtitle-text">ACCOMPLISHMENT REPORT and VACCINE UTILIZATION <?= $report_type == 'A' ? '(INDIVIDUAL)' : '(CONSOLIDATED)' ?></div>
        </div>
    </div>

    <div class="meta-container">
        <div class="meta-col">
            <div class="meta-row">
                <div class="meta-label">Region:</div>
                <div class="meta-value">IV-B MIMAROPA</div>
            </div>
            <div class="meta-row">
                <div class="meta-label">Province:</div>
                <div class="meta-value">MARINDUQUE</div>
            </div>
            <div class="meta-row">
                <div class="meta-label">Municipality:</div>
                <div class="meta-value">MOGPOG</div>
            </div>
            <div class="meta-row">
                <div class="meta-label">Barangay:</div>
                <div class="meta-value"><?= $brgy_name ?></div>
            </div>
            <div class="meta-row">
                <div class="meta-label">Date:</div>
                <div class="meta-value"><?= date('m/d/Y', strtotime($report_date)) ?></div>
            </div>
        </div>
        <div class="meta-col">
            <div class="meta-row">
                <div class="meta-label" style="width:120px;">Vaccine used:</div>
                <div class="meta-value"><?= $vaccine_name ?></div>
            </div>
            <div class="meta-row">
                <div class="meta-label" style="width:120px;">Batch No.</div>
                <div class="meta-value">___________________</div>
            </div>
            <div class="meta-row">
                <div class="meta-label" style="width:120px;">Expiration Date:</div>
                <div class="meta-value">___________________</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th colspan="5">Owner's Information</th>
                <th colspan="8">Pet's Information</th>
                <th colspan="2">FIXED</th>
                <?php if ($report_type === 'B'): ?>
                    <th rowspan="2" width="5%">V/NV</th>
                <?php else: ?>
                    <th rowspan="2" width="7%">SIGNATURE</th>
                <?php endif; ?>
                <th rowspan="2" width="10%">REMARKS</th>
            </tr>
            <tr>
                <th width="12%">OWNER'S NAME</th>
                <th width="4%">GEN<br>DER</th>
                <th width="3%">PWD</th>
                <th width="4%">Senior<br>Citizen</th>
                <th width="7%">OWNER'S<br>BIRTHDAY</th>

                <th width="10%">PET'S NAME</th>
                <th width="4%">AGE</th>
                <th width="3%">SEX</th>
                <th width="4%">CANINE<br><small>(Aso)</small></th>
                <th width="4%">FELINE<br><small>(Pusa)</small></th>
                <th width="9%">BREED/COLOR<br>MARKINGS</th>
                <th width="4%">STRAY<br><small>(Ligaw)</small></th>
                <th width="5%">NON STRAY<br><small>(Di-nakaligaw)</small></th>

                <th width="4%">KAPON/LIG<br>ATE<br>(/)</th>
                <th width="4%">HINDI<br>KAPON<br>(/)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {

                    // Extract Signatory info from the first row for Option A
                    if ($is_first_row && $report_type === 'A') {
                        $target_vaccinator_name = htmlspecialchars($row['vaccinator']);
                        if (!empty($row['official_name'])) {
                            $assisting_official_name = htmlspecialchars($row['official_name']);
                            $assisting_official_desc = htmlspecialchars($row['official_designation']);
                        }
                        $is_first_row = false;
                    }

                    // Logic 1: Compute Age
                    $age_str = "";
                    if (!empty($row['birth_date'])) {
                        $bdate = new DateTime($row['birth_date']);
                        $today = new DateTime('today');
                        $diff = $bdate->diff($today);
                        if ($diff->y > 0) $age_str = $diff->y . "y";
                        else $age_str = $diff->m . "m";
                    }

                    // Logic 2: Identify Species (Canine/Feline)
                    $is_dog = (stripos($row['species_name'], 'dog') !== false || stripos($row['species_name'], 'canine') !== false);
                    $is_cat = (stripos($row['species_name'], 'cat') !== false || stripos($row['species_name'], 'feline') !== false);

                    // Combine Breed and Color
                    $breed_color = htmlspecialchars($row['breed']) . " / " . htmlspecialchars($row['color']);

                    echo "<tr>";
                    echo "<td class='text-left'>" . htmlspecialchars($row['owner_name']) . "</td>";
                    echo "<td>" . ($row['owner_gender'] == 'Male' ? 'M' : ($row['owner_gender'] == 'Female' ? 'F' : '')) . "</td>";
                    echo "<td><span class='check-mark'>" . ($row['is_pwd'] ? '&#10003;' : '') . "</span></td>";
                    echo "<td><span class='check-mark'>" . ($row['is_senior'] ? '&#10003;' : '') . "</span></td>";
                    echo "<td>" . (!empty($row['owner_birthday']) ? date('m/d/Y', strtotime($row['owner_birthday'])) : '') . "</td>";

                    echo "<td class='text-left'>" . htmlspecialchars($row['animal_name']) . "</td>";
                    echo "<td>" . $age_str . "</td>";
                    echo "<td>" . htmlspecialchars($row['sex']) . "</td>";
                    echo "<td><span class='check-mark'>" . ($is_dog ? '&#10003;' : '') . "</span></td>";
                    echo "<td><span class='check-mark'>" . ($is_cat ? '&#10003;' : '') . "</span></td>";
                    echo "<td>" . $breed_color . "</td>";
                    echo "<td><span class='check-mark'>" . ($row['is_stray'] == 1 ? '&#10003;' : '') . "</span></td>";
                    echo "<td><span class='check-mark'>" . ($row['is_stray'] == 0 ? '&#10003;' : '') . "</span></td>";

                    echo "<td><span class='check-mark'>" . ($row['is_fixed'] == 1 ? '&#10003;' : '') . "</span></td>";
                    echo "<td><span class='check-mark'>" . ($row['is_fixed'] == 0 ? '&#10003;' : '') . "</span></td>";

                    // DYNAMIC COLUMNS (V/NV vs Signature)
                    $is_vax = ($row['status'] === 'Vaccinated');
                    
                    if ($report_type === 'B') {
                        // Option B: Just V or NV
                        echo "<td style='font-weight:bold;'>" . ($is_vax ? "V" : "NV") . "</td>";
                        echo "<td class='text-left'>" . ($is_vax ? "" : htmlspecialchars($row['vax_remarks'])) . "</td>";
                    } else {
                        // Option A: Print Signature + Detailed Remarks
                        echo "<td>";
                        if (!empty($row['owner_signature'])) {
                            echo "<img src='" . $row['owner_signature'] . "' class='print-signature' alt='Sig'>";
                        }
                        echo "</td>";
                        $detailed_remarks = $is_vax ? "Vaccinated. " . $row['vax_remarks'] : "Not Vaccinated: " . $row['vax_remarks'];
                        echo "<td class='text-left' style='font-size:9px;'>" . htmlspecialchars($detailed_remarks) . "</td>";
                    }

                    echo "</tr>";
                }

                // Add empty rows to fill up the page if records are few
                $empty_rows = 15 - mysqli_num_rows($result);
                for ($i = 0; $i < $empty_rows; $i++) {
                    echo "<tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
                }
            } else {
                for ($i = 0; $i < 15; $i++) {
                    echo "<tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
                }
            }
            ?>
        </tbody>
    </table>

    <div class="signatories-container">
        <?php if ($report_type === 'A'): ?>
            <div class="sig-block" style="width: 22%;">
                <div style="text-align: left;">Vaccinated By:</div>
                <div class="sig-line"><?= $target_vaccinator_name ?></div>
                <div class="sig-role">Field Vaccinator</div>
            </div>
            <div class="sig-block" style="width: 22%;">
                <div style="text-align: left;">Assisted By:</div>
                <div class="sig-line"><?= strtoupper($assisting_official_name) ?></div>
                <div class="sig-role"><?= $assisting_official_desc ?></div>
            </div>
            <div class="sig-block" style="width: 22%;">
                <div style="text-align: left;">Prepared By:</div>
                <div class="sig-line"><?= $prepared_by_name ?></div>
                <div class="sig-role"><?= $prepared_by_role ?></div>
            </div>
            <div class="sig-block" style="width: 22%;">
                <div style="text-align: left;">Noted By:</div>
                <div class="sig-line">MARNELLI R. NUÑEZ</div>
                <div class="sig-role">Agriculturist I/OIC-MAO</div>
            </div>
        <?php else: ?>
            <div class="sig-block" style="width: 45%;">
                <div style="text-align: left; width: 60%; margin: 0 auto;">Prepared By:</div>
                <div class="sig-line" style="width: 60%; margin-left: auto; margin-right: auto;"><?= $prepared_by_name ?></div>
                <div class="sig-role"><?= $prepared_by_role ?></div>
            </div>
            <div class="sig-block" style="width: 45%;">
                <div style="text-align: left; width: 60%; margin: 0 auto;">Noted By:</div>
                <div class="sig-line" style="width: 60%; margin-left: auto; margin-right: auto;">MARNELLI R. NUÑEZ</div>
                <div class="sig-role">Agriculturist I/OIC-MAO</div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>