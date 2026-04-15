<?php
session_start();
include '../../config.php';

// CYBERSECURITY: Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    die("Unauthorized Access.");
}

$animal_id = isset($_GET['animal_id']) ? $_GET['animal_id'] : null;

if (!$animal_id) {
    die("No Animal Record Selected.");
}

// FETCH ANIMAL DETAILS
$anim_sql = "SELECT a.*, b.barangay_name, s.species_name FROM animal_tbl a 
             JOIN barangay_tbl b ON a.barangay_id = b.barangay_id 
             JOIN species_tbl s ON a.species_id = s.species_id 
             WHERE a.record_id = '$animal_id'";
$anim_res = mysqli_query($conn, $anim_sql);

if (!$anim_res || mysqli_num_rows($anim_res) === 0) {
    die("Animal record not found.");
}

$animal = mysqli_fetch_assoc($anim_res);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccination Certificate - <?= htmlspecialchars($animal['animal_id_tag']) ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Base Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #e9ecef; /* Light gray background on screen */
            color: #000;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        /* THE CERTIFICATE CANVAS (Screen View) */
        #document {
            background-color: #fff;
            width: 297mm; /* A4 Landscape Width */
            min-height: 210mm; /* A4 Landscape Height */
            padding: 15mm;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
        }

        /* OFFICIAL DOUBLE BORDER */
        .cert-border {
            border: 5px double #1b4332; /* MAO Dark Green */
            padding: 30px 40px;
            height: 100%;
            position: relative;
        }

        /* GOVERNMENT LETTERHEAD */
        .report-header {
            text-align: center;
            margin-bottom: 25px;
            position: relative;
            font-family: 'Times New Roman', Times, serif; /* Gov Standard */
        }
        .logo-left {
            position: absolute;
            left: 0;
            top: 0;
            width: 100px;
            height: 100px;
        }
           .logo-right {
            position: absolute;
            right: 0;
            top: 0;
            width: 100px;
            height: 100px;
        }
        .header-text p { margin-bottom: 2px; font-size: 15px; }
        .header-text h4 { font-weight: bold; color: #1b4332; margin-top: 15px; font-size: 22px; }
        .divider { border-top: 2px solid #1b4332; margin: 15px 0; }

        /* CERTIFICATE TITLE */
        .cert-title {
            text-align: center;
            font-size: 26px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #1b4332;
            margin-bottom: 30px;
            font-family: 'Times New Roman', Times, serif;
        }

        /* INFORMATION GRID (Fill-in-the-blanks style) */
        .info-grid {
            margin-bottom: 30px;
            font-size: 15px;
        }
        .data-row {
            display: flex;
            margin-bottom: 12px;
            align-items: flex-end;
        }
        .data-label {
            font-weight: bold;
            color: #444;
            white-space: nowrap;
            margin-right: 10px;
            text-transform: uppercase;
            font-size: 12px;
        }
        .data-value {
            flex-grow: 1;
            border-bottom: 1px solid #000; /* Line for the "form" look */
            font-weight: bold;
            font-size: 16px;
            padding-left: 5px;
            color: #000;
        }
        .id-highlight {
            font-size: 24px;
            color: #b02a30; /* Dark red for ID to make it pop */
            letter-spacing: 1px;
            border-bottom: 2px solid #b02a30;
        }

        /* TABLE STYLES */
        .table-title { font-weight: bold; text-transform: uppercase; margin-bottom: 10px; border-bottom: 2px solid #1b4332; display: inline-block;}
        .table { border-collapse: collapse !important; width: 100%; margin-bottom: 40px; font-size: 14px;}
        .table th, .table td { border: 1px solid #000 !important; padding: 10px; vertical-align: middle; }
        .table th { background-color: #f0f0f0 !important; -webkit-print-color-adjust: exact; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 12px;}

        /* SIGNATURES */
        .signature-section { display: flex; justify-content: space-between; margin-top: 50px; padding: 0 40px;}
        .sign-block { width: 280px; text-align: center; }
        .sign-line { border-bottom: 1px solid #000; height: 40px; margin-bottom: 5px; }
        .sign-name { font-weight: bold; text-transform: uppercase; font-size: 14px; margin-bottom: 0; }
        .sign-title { font-size: 12px; color: #555; }

        /* PRINT CONTROL */
        .btn-print-container { position: fixed; top: 20px; right: 20px; z-index: 1000; }

        /* MEDIA PRINT SETTINGS */
        @media print {
            /* LANDSCAPE MAGIC */
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
            body { background-color: #fff; padding: 0; display: block; }
            #document { box-shadow: none; padding: 0; width: 100%; min-height: auto; }
            .btn-print-container { display: none; }
        }
    </style>
</head>

<body>

    <div class="btn-print-container">
        <button onclick="window.print()" class="btn btn-success btn-lg shadow font-weight-bold" style="background-color: #1b4332; border: 2px solid #fff;">
            <i class="fas fa-print mr-2"></i> Print Certificate
        </button>
    </div>

    <div id="document">
        <div class="cert-border">
            
            <div class="report-header">
                <img src="logo.png" class="logo-left" alt="MAO Logo" onerror="this.style.display='none'">
                <div class="header-text">
                    <p>Republic of the Philippines</p>
                    <p>Province of Marinduque</p>
                    <p class="font-weight-bold">MUNICIPALITY OF MOGPOG</p>
                    <h4 class="text-uppercase">Municipal Agriculture Office </h4>
                </div>
                <img src="mogpoglogo.svg" class="logo-right" alt="MAO Logo" onerror="this.style.display='none'">
                <div class="divider"></div>
            </div>

            <div class="cert-title">Certificate of Animal Registration & Vaccination</div>

            <div class="info-grid">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="data-row">
                            <span class="data-label">Official Animal ID No.:</span>
                            <span class="data-value id-highlight text-center"><?= htmlspecialchars($animal['animal_id_tag']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-7">
                        <div class="data-row">
                            <span class="data-label">Name of Owner:</span>
                            <span class="data-value"><?= htmlspecialchars($animal['owner_name']) ?></span>
                        </div>
                        <div class="data-row">
                            <span class="data-label">Address / Barangay:</span>
                            <span class="data-value">Brgy. <?= htmlspecialchars($animal['barangay_name']) ?>, Mogpog, Marinduque</span>
                        </div>
                        <div class="data-row">
                            <span class="data-label">Contact Number:</span>
                            <span class="data-value"><?= htmlspecialchars($animal['contact_no']) ?: '<span style="color:#fff;">.</span>' ?></span>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="data-row">
                            <span class="data-label">Animal / Pet Name:</span>
                            <span class="data-value"><?= htmlspecialchars($animal['animal_name']) ?: '<span style="color:#fff;">.</span>' ?></span>
                        </div>
                        <div class="data-row">
                            <span class="data-label">Species & Breed:</span>
                            <span class="data-value"><?= htmlspecialchars($animal['species_name']) ?> (<?= htmlspecialchars($animal['breed']) ?>)</span>
                        </div>
                        <div class="row">
                            <div class="col-6 data-row">
                                <span class="data-label">Sex:</span>
                                <span class="data-value text-center"><?= htmlspecialchars($animal['sex']) ?></span>
                            </div>
                            <div class="col-6 data-row pl-0">
                                <span class="data-label">Color:</span>
                                <span class="data-value text-center"><?= htmlspecialchars($animal['color']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-title">Vaccination History Log</div>
            <table class="table">
                <thead>
                    <tr>
                        <th width="15%">Date Administered</th>
                        <th width="30%">Vaccine Administered</th>
                        <th width="15%">Next Due Date</th>
                        <th width="20%">Administered By (Staff)</th>
                        <th width="20%">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $vax_sql = "SELECT v.*, vac.vaccine_name, u.fullname as vaccinator 
                                FROM vaccination_tbl v 
                                JOIN vaccine_tbl vac ON v.vaccine_id = vac.vaccine_id 
                                JOIN user_tbl u ON v.vaccinator_id = u.userid 
                                WHERE v.animal_id = '$animal_id' 
                                ORDER BY v.vaccination_date DESC";
                    $vax_res = mysqli_query($conn, $vax_sql);

                    if (mysqli_num_rows($vax_res) > 0) {
                        while ($log = mysqli_fetch_assoc($vax_res)) {
                            $due_date = $log['next_due_date'] ? date('M d, Y', strtotime($log['next_due_date'])) : 'N/A';
                            echo "<tr>";
                            echo "<td class='text-center font-weight-bold'>" . date('M d, Y', strtotime($log['vaccination_date'])) . "</td>";
                            echo "<td class='font-weight-bold' style='color: #1b4332;'>" . htmlspecialchars($log['vaccine_name']) . "</td>";
                            echo "<td class='text-center'>" . $due_date . "</td>";
                            echo "<td class='text-center text-uppercase'>" . htmlspecialchars($log['vaccinator']) . "</td>";
                            echo "<td><small>" . htmlspecialchars($log['remarks']) . "</small></td>";
                            echo "</tr>";
                        }
                        
                        // Pad empty rows if history is short to maintain certificate height
                        $empty_rows = 3 - mysqli_num_rows($vax_res);
                        for($i=0; $i<$empty_rows; $i++) {
                            echo "<tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center py-4 text-muted font-italic'>No vaccination records found for this animal.</td></tr>";
                        echo "<tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>";
                        echo "<tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <div class="signature-section">
                <div class="sign-block">
                    <p class="text-left small mb-0">Certified Correct By:</p>
                    <div class="sign-line"></div>
                    <p class="sign-name"><?= htmlspecialchars($_SESSION['fullname']) ?></p>
                    <p class="sign-title"><?= htmlspecialchars($_SESSION['role']) ?></p>
                </div>
                
                <div class="sign-block">
                    <p class="text-left small mb-0">Noted By:</p>
                    <div class="sign-line"></div>
                    <p class="sign-name">AGRICULTURIST I</p>
                    <p class="sign-title">OIC-MAO</p>
                </div>
            </div>

            <div class="text-center mt-4" style="position: absolute; bottom: 20px; width: calc(100% - 80px);">
                <p class="small text-muted mb-0" style="font-size: 10px;">This is a system-generated certificate from the VAX-IN Municipal Vaccination Information System.</p>
                <p class="small text-muted" style="font-size: 10px;">Date Generated: <?= date('F d, Y h:i A') ?></p>
            </div>

        </div> </div> <script>
        // Trigger the print dialog automatically after styles and logos load
        window.onload = function () { 
            setTimeout(function () { 
                window.print(); 
            }, 800); 
        };
    </script>
</body>

</html>