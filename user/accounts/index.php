<?php

                include '../../config.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include './../template/header.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content">
                <?php include './../template/navbar.php';


                $userid = $_SESSION['userid'] ?? null;
                // Cybersecurity: Fetch user role from session for Role-Based Access Control (RBAC)
// Roles: 'Administrator', 'Support Staff', 'Field Vaccinator'
                $user_role = $_SESSION['role'] ?? 'Field Vaccinator';

                $current_page = basename($_SERVER['PHP_SELF'], '.php');

                // Define grouped pages based on VAX-IN requirements
                $record_pages = ['animals', 'add_animal', 'vaccination_logs'];
                $report_pages = ['reports_barangay', 'reports_summary', 'reports_mimaropa', 'vaccination_status', 'list_barangay_animals'];
                $admin_pages = ['manage_accounts', 'pending_accounts', 'activity_log'];

                date_default_timezone_set("Asia/Manila");

                // ==========================================
// 1. DYNAMIC GREETING LOGIC
// ==========================================
                $hour = date('H');
                if ($hour >= 5 && $hour < 12) {
                    $greeting = "Good Morning";
                    $weather_icon = "fa-sun text-warning";
                } else if ($hour >= 12 && $hour < 18) {
                    $greeting = "Good Afternoon";
                    $weather_icon = "fa-cloud-sun text-warning";
                } else {
                    $greeting = "Good Evening";
                    $weather_icon = "fa-moon text-light";
                }

                // ==========================================
// 2. DATA FETCHING FOR KPI CARDS (Summary)
// ==========================================
                $anim_query = mysqli_query($conn, "SELECT COUNT(record_id) as total FROM animal_tbl");
                $total_animals = $anim_query ? mysqli_fetch_assoc($anim_query)['total'] : 0;

                $vax_query = mysqli_query($conn, "SELECT COUNT(log_id) as total FROM vaccination_tbl WHERE MONTH(vaccination_date) = MONTH(CURRENT_DATE()) AND YEAR(vaccination_date) = YEAR(CURRENT_DATE())");
                $vax_this_month = $vax_query ? mysqli_fetch_assoc($vax_query)['total'] : 0;

                $user_query = mysqli_query($conn, "SELECT COUNT(userid) as total FROM user_tbl WHERE useractive = 1");
                $active_users = $user_query ? mysqli_fetch_assoc($user_query)['total'] : 0;

                $all_vax_query = mysqli_query($conn, "SELECT COUNT(log_id) as total FROM vaccination_tbl");
                $total_vax_alltime = $all_vax_query ? mysqli_fetch_assoc($all_vax_query)['total'] : 0;

                // ==========================================
// 3. DATA FETCHING FOR DYNAMIC CHARTS
// ==========================================
                $brgy_sql = "SELECT b.barangay_name, COUNT(v.log_id) as vax_count 
             FROM barangay_tbl b 
             LEFT JOIN animal_tbl a ON b.barangay_id = a.barangay_id 
             LEFT JOIN vaccination_tbl v ON a.record_id = v.animal_id 
             GROUP BY b.barangay_id 
             ORDER BY vax_count DESC LIMIT 5";
                $brgy_res = mysqli_query($conn, $brgy_sql);
                $brgy_labels = [];
                $brgy_data = [];
                if ($brgy_res) {
                    while ($row = mysqli_fetch_assoc($brgy_res)) {
                        $brgy_labels[] = $row['barangay_name'];
                        $brgy_data[] = (int) $row['vax_count'];
                    }
                }

                $species_sql = "SELECT s.species_name, COUNT(a.record_id) as anim_count 
                FROM species_tbl s 
                LEFT JOIN animal_tbl a ON s.species_id = a.species_id 
                GROUP BY s.species_id 
                HAVING anim_count > 0 
                ORDER BY anim_count DESC";
                $species_res = mysqli_query($conn, $species_sql);
                $species_labels = [];
                $species_data = [];
                if ($species_res) {
                    while ($row = mysqli_fetch_assoc($species_res)) {
                        $species_labels[] = $row['species_name'];
                        $species_data[] = (int) $row['anim_count'];
                    }
                }
                ?>
                <style>
                    body {
                        font-family: 'Poppins', sans-serif;
                    }

                    /* Dashboard Link Wrapper Styles */
                    .kpi-link {
                        text-decoration: none !important;
                        color: inherit;
                        display: block;
                        cursor: pointer;
                    }

                    .kpi-link:hover {
                        text-decoration: none !important;
                        color: inherit;
                    }

                    /* Non-clickable card styling for vaccinators */
                    .kpi-box {
                        display: block;
                        color: inherit;
                    }

                    /* Interactive Card Hover Effects */
                    .hover-card {
                        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s;
                        border-radius: 12px !important;
                    }

                    .hover-card:hover {
                        transform: translateY(-8px);
                        box-shadow: 0 15px 30px rgba(27, 67, 50, 0.15) !important;
                    }

                    .chart-container {
                        height: 350px;
                        width: 100%;
                        position: relative;
                    }

                    /* VAX-IN WELCOME BANNER */
                    .welcome-banner {
                        background: linear-gradient(135deg, #1b4332 0%, #40916c 100%);
                        color: white;
                        border-radius: 15px;
                        padding: 30px 40px;
                        position: relative;
                        overflow: hidden;
                        box-shadow: 0 10px 25px rgba(27, 67, 50, 0.2);
                        margin-bottom: 30px;
                    }

                    .welcome-title {
                        font-weight: 800;
                        font-size: 2rem;
                        letter-spacing: 0.5px;
                    }

                    /* Floating Background Pets */
                    .bg-pet {
                        position: absolute;
                        color: rgba(255, 255, 255, 0.05);
                        font-size: 8rem;
                        z-index: 1;
                    }

                    .bg-dog {
                        bottom: -20px;
                        right: 250px;
                        transform: rotate(15deg);
                    }

                    .bg-cat {
                        top: -10px;
                        right: 120px;
                        transform: rotate(-10deg);
                    }

                    .bg-bone {
                        bottom: 20px;
                        right: 50px;
                        transform: rotate(45deg);
                        font-size: 5rem;
                    }

                    .banner-content {
                        position: relative;
                        z-index: 2;
                    }

                    /* Live Clock Design */
                    .clock-container {
                        text-align: right;
                        border-left: 2px solid rgba(255, 255, 255, 0.2);
                        padding-left: 20px;
                    }

                    .live-clock {
                        font-size: 2.2rem;
                        font-weight: 800;
                        letter-spacing: 2px;
                        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
                    }

                    .live-date {
                        font-size: 1rem;
                        opacity: 0.9;
                        text-transform: uppercase;
                        letter-spacing: 1px;
                    }

                    /* Green Agri Theme Custom Classes */
                    .text-agri-dark {
                        color: #1b4332 !important;
                    }

                    .text-agri-green {
                        color: #2d6a4f !important;
                    }

                    .border-left-agri-dark {
                        border-left: .3rem solid #1b4332 !important;
                    }

                    .border-left-agri-green {
                        border-left: .3rem solid #2d6a4f !important;
                    }

                    .border-left-agri-light {
                        border-left: .3rem solid #52b788 !important;
                    }

                    @media (max-width: 768px) {
                        .clock-container {
                            text-align: left;
                            border-left: none;
                            padding-left: 0;
                            margin-top: 15px;
                            border-top: 1px solid rgba(255, 255, 255, 0.2);
                            padding-top: 15px;
                        }

                        .bg-pet {
                            display: none;
                        }
                    }
                </style>
                <div class="container-fluid pt-4">

                    <div class="welcome-banner">
                        <i class="fas fa-dog bg-pet bg-dog"></i>
                        <i class="fas fa-cat bg-pet bg-cat"></i>
                        <i class="fas fa-bone bg-pet bg-bone"></i>
                        <div class="row align-items-center banner-content">
                            <div class="col-md-8">
                                <h2 class="welcome-title mb-1">
                                    <i class="fas <?= $weather_icon ?> mr-2"></i><?= $greeting ?>!
                                </h2>
                                <p class="mb-0" style="font-size: 1.1rem; opacity: 0.9;">Welcome back to your Mogpog MAO
                                    Vaccination Information System. Here's your overview for today.</p>
                            </div>
                            <div class="col-md-4">
                                <div class="clock-container">
                                    <div class="live-clock" id="liveClock">00:00:00</div>
                                    <div class="live-date"><?= date('l, F j, Y') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($user_role === 'Administrator' || $user_role === 'Support Staff'): ?>
                        <div class="d-sm-flex align-items-center justify-content-end mb-4">
                            <a href="reports_summary.php"
                                class="d-none d-sm-inline-block btn btn-sm text-white shadow-sm font-weight-bold px-3 py-2"
                                style="background-color: #1b4332; border-radius: 8px;">
                                <i class="fas fa-chart-line fa-sm text-white-50 mr-2"></i> View Detailed Report
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-xl-<?= ($user_role === 'Administrator') ? '3' : '4' ?> col-md-6 mb-4">
                            <?php if ($user_role !== 'Field Vaccinator'): ?>
                                <a href="list_barangay_animals" class="kpi-link" title="Click to view animals by barangay">
                                <?php else: ?>
                                    <div class="kpi-box">
                                    <?php endif; ?>
                                    <div class="card border-left-agri-dark shadow h-100 py-2 hover-card bg-white">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div
                                                        class="text-xs font-weight-bold text-agri-dark text-uppercase mb-1">
                                                        Registered Animals</div>
                                                    <div class="h4 mb-0 font-weight-bold text-gray-800">
                                                        <?= number_format($total_animals) ?>
                                                    </div>
                                                    <small class="text-muted">Total across all barangays</small>
                                                </div>
                                                <div class="col-auto">
                                                    <div
                                                        style="background: #e9f5e9; padding: 15px; border-radius: 50%;">
                                                        <i class="fas fa-paw fa-2x text-agri-green"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($user_role !== 'Field Vaccinator'): ?>
                                </a><?php else: ?>
                            </div><?php endif; ?>
                    </div>

                    <div class="col-xl-<?= ($user_role === 'Administrator') ? '3' : '4' ?> col-md-6 mb-4">
                        <?php if ($user_role !== 'Field Vaccinator'): ?>
                            <a href="vaccination_status" class="kpi-link" title="Click to view vaccination percentages">
                            <?php else: ?>
                                <div class="kpi-box">
                                <?php endif; ?>
                                <div class="card border-left-agri-green shadow h-100 py-2 hover-card bg-white">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div
                                                    class="text-xs font-weight-bold text-agri-green text-uppercase mb-1">
                                                    Vaccinations (This Month)</div>
                                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                                    <?= number_format($vax_this_month) ?>
                                                </div>
                                                <small class="text-success font-weight-bold"><i
                                                        class="fas fa-calendar-check mr-1"></i> Current period</small>
                                            </div>
                                            <div class="col-auto">
                                                <div style="background: #d1e7dd; padding: 15px; border-radius: 50%;">
                                                    <i class="fas fa-syringe fa-2x" style="color: #0f5132;"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($user_role !== 'Field Vaccinator'): ?>
                            </a><?php else: ?>
                        </div><?php endif; ?>
                </div>

                <?php if ($user_role === 'Administrator'): ?>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <a href="manage_accounts" class="kpi-link" title="Click to manage staff accounts">
                            <div class="card border-left-warning shadow h-100 py-2 hover-card bg-white">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Active
                                                Personnel</div>
                                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($active_users) ?>
                                            </div>
                                            <small class="text-muted">System administrators & staff</small>
                                        </div>
                                        <div class="col-auto">
                                            <div style="background: #fff3cd; padding: 15px; border-radius: 50%;">
                                                <i class="fas fa-users-cog fa-2x" style="color: #856404;"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="col-xl-<?= ($user_role === 'Administrator') ? '3' : '4' ?> col-md-6 mb-4">
                    <?php if ($user_role !== 'Field Vaccinator'): ?>
                        <a href="reports_barangay" class="kpi-link" title="Click to view detailed reports">
                        <?php else: ?>
                            <div class="kpi-box">
                            <?php endif; ?>
                            <div class="card border-left-agri-light shadow h-100 py-2 hover-card bg-white">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-uppercase mb-1"
                                                style="color: #52b788;">Total Administered</div>
                                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($total_vax_alltime) ?>
                                            </div>
                                            <small class="text-muted">All-time vaccination logs</small>
                                        </div>
                                        <div class="col-auto">
                                            <div style="background: #e2e3e5; padding: 15px; border-radius: 50%;">
                                                <i class="fas fa-chart-line fa-2x text-secondary"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($user_role !== 'Field Vaccinator'): ?>
                        </a><?php else: ?>
                    </div><?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8 col-lg-7 mb-4">
                <div class="card shadow mb-4 h-100" style="border-radius: 15px;">
                    <div class="card-header py-3 bg-white" style="border-radius: 15px 15px 0 0;">
                        <h6 class="m-0 font-weight-bold text-agri-dark"><i class="fas fa-chart-bar mr-2"></i>Vaccination
                            Coverage by Barangay (Top 5)</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($brgy_labels)): ?>
                            <div class="text-center py-5 mt-4 text-muted">
                                <i class="fas fa-chart-bar fa-3x mb-3" style="opacity: 0.3;"></i>
                                <p>No vaccination data available yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="chart-area chart-container">
                                <canvas id="barangayChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-5 mb-4">
                <div class="card shadow mb-4 h-100" style="border-radius: 15px;">
                    <div class="card-header py-3 bg-white" style="border-radius: 15px 15px 0 0;">
                        <h6 class="m-0 font-weight-bold text-agri-dark"><i class="fas fa-chart-pie mr-2"></i>Registered
                            Animals</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($species_labels)): ?>
                            <div class="text-center py-5 mt-4 text-muted">
                                <i class="fas fa-paw fa-3x mb-3" style="opacity: 0.3;"></i>
                                <p>No animal records available yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="chart-pie chart-container mb-3" style="height: 250px;">
                                <canvas id="animalTypeChart"></canvas>
                            </div>
                            <div class="mt-2 text-center small" id="doughnutLegend"></div>
                        <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>

    <script>
        // ==================================================
        // LIVE REAL-TIME CLOCK JAVASCRIPT
        // ==================================================
        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            let minutes = now.getMinutes();
            let seconds = now.getSeconds();
            let ampm = hours >= 12 ? 'PM' : 'AM';

            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;

            const timeString = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
            document.getElementById('liveClock').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();


        // Set Default Chart Font
        Chart.defaults.global.defaultFontFamily = 'Poppins', 'sans-serif';
        Chart.defaults.global.defaultFontColor = '#858796';

        // 1. BARANGAY CHART 
        const brgyLabels = <?= json_encode($brgy_labels) ?>;
        const brgyVaccinations = <?= json_encode($brgy_data) ?>;

        if (brgyLabels.length > 0) {
            var ctxBrgy = document.getElementById("barangayChart");
            new Chart(ctxBrgy, {
                type: 'bar',
                data: {
                    labels: brgyLabels,
                    datasets: [{
                        label: "Vaccinations",
                        backgroundColor: "#2d6a4f",
                        hoverBackgroundColor: "#1b4332",
                        borderColor: "#1b4332",
                        data: brgyVaccinations,
                        barPercentage: 0.6,
                        categoryPercentage: 0.8
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{ gridLines: { display: false, drawBorder: false }, ticks: { maxTicksLimit: 5 } }],
                        yAxes: [{
                            ticks: { beginAtZero: true, padding: 10, callback: function (value) { return value.toLocaleString(); } },
                            gridLines: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] }
                        }]
                    },
                    legend: { display: false },
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)", bodyFontColor: "#858796", titleFontColor: '#1b4332',
                        borderColor: '#dddfeb', borderWidth: 1, xPadding: 15, yPadding: 15, displayColors: false,
                        caretPadding: 10,
                        callbacks: { label: function (tooltipItem, chart) { return 'Vaccinations: ' + tooltipItem.yLabel.toLocaleString(); } }
                    }
                }
            });
        }

        // 2. ANIMAL TYPE DOUGHNUT CHART 
        const animalLabels = <?= json_encode($species_labels) ?>;
        const animalCounts = <?= json_encode($species_data) ?>;
        const themeColors = ['#1b4332', '#2d6a4f', '#40916c', '#52b788', '#74c69d', '#95d5b2', '#b7e4c7'];

        let animalColors = [];
        for (let i = 0; i < animalLabels.length; i++) { animalColors.push(themeColors[i % themeColors.length]); }

        if (animalLabels.length > 0) {
            var ctxAnimal = document.getElementById("animalTypeChart");
            new Chart(ctxAnimal, {
                type: 'doughnut',
                data: {
                    labels: animalLabels,
                    datasets: [{ data: animalCounts, backgroundColor: animalColors, hoverBackgroundColor: animalColors, hoverBorderColor: "rgba(234, 236, 244, 1)" }],
                },
                options: {
                    maintainAspectRatio: false, cutoutPercentage: 75, legend: { display: false },
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)", bodyFontColor: "#858796", borderColor: '#dddfeb',
                        borderWidth: 1, xPadding: 15, yPadding: 15, displayColors: true,
                        callbacks: { label: function (tooltipItem, data) { return ' ' + data.labels[tooltipItem.index] + ': ' + data.datasets[0].data[tooltipItem.index].toLocaleString() + ' registered'; } }
                    }
                },
            });

            let legendHTML = '';
            for (let i = 0; i < animalLabels.length; i++) {
                legendHTML += `<span class="mr-3 mb-2 d-inline-block"><i class="fas fa-circle" style="color: ${animalColors[i]};"></i> <span class="font-weight-bold text-gray-800">${animalLabels[i]}</span></span>`;
            }
            document.getElementById('doughnutLegend').innerHTML = legendHTML;
        }
    </script>
</body>

</html>