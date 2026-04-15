<?php
include '../../config.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include './../template/header.php' ?>
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .log-success {
            color: #2d6a4f;
            font-weight: bold;
        }

        .log-failed {
            color: #e63946;
            font-weight: bold;
        }

        .badge-failed {
            background-color: #e63946;
            color: white;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php' ?>
        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content">
                <?php include './../template/navbar.php'; ?>

                <div class="container-fluid pt-4">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 fw-bold" style="color: #1b4332 !important;">
                            <i class="fas fa-list-alt mr-2"></i>System Activity Logs
                        </h1>
                        <p class="mb-0 text-muted">Monitor all user actions, errors, and system events.</p>
                    </div>

                    <div class="card shadow mb-4" style="border-top: 4px solid #2d6a4f; border-radius: 10px;">
                        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center"
                            style="border-radius: 10px 10px 0 0;">
                            <h6 class="m-0 font-weight-bold" style="color: #1b4332;">Comprehensive Audit Trail</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="logsTable" width="100%"
                                    cellspacing="0">
                                    <thead style="background-color: #f8f9fa; color: #1b4332;">
                                        <tr>
                                            <th width="15%">Date & Time</th>
                                            <th width="15%">User / ID</th>
                                            <th width="45%">Action Made (System Event)</th>
                                            <th width="10%" class="text-center">Status</th>
                                            <th width="15%">IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Hahatakin natin lahat ng logs, pinakabago ang nasa itaas
                                        $sql = "SELECT * FROM userlogs_tbl ORDER BY login_time DESC";
                                        $result = mysqli_query($conn, $sql);

                                        if ($result && mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {

                                                // I-format ang petsa at oras (e.g., April 08, 2026 06:45 AM)
                                                $formatted_date = date('M d, Y h:i A', strtotime($row['login_time']));

                                                // Dynamic Status Badge and Text Color
                                                $status_text = $row['status'] ?? 'Success'; // Default to Success if blank
                                                if (strcasecmp($status_text, 'Failed') == 0 || strcasecmp($status_text, 'Error') == 0) {
                                                    $status_badge = '<span class="badge badge-failed px-2 py-1">FAILED</span>';
                                                    $action_class = 'log-failed';
                                                } else {
                                                    $status_badge = '<span class="badge badge-success px-2 py-1">SUCCESS</span>';
                                                    $action_class = 'text-dark';
                                                }
                                                ?>
                                                <tr>
                                                    <td class="small text-muted align-middle"><?= $formatted_date ?></td>
                                                    <td class="font-weight-bold align-middle">
                                                        <i class="fas fa-user-shield text-gray-400 mr-1"></i>
                                                        <?= htmlspecialchars($row['username']) ?><br>
                                                        <small class="text-muted font-weight-normal">ID:
                                                            <?= htmlspecialchars($row['userid']) ?></small>
                                                    </td>
                                                    <td class="<?= $action_class ?> align-middle">
                                                        <?= htmlspecialchars($row['action_made']) ?>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <?= $status_badge ?>
                                                    </td>
                                                    <td class="small text-muted align-middle">
                                                        <i class="fas fa-network-wired mr-1"></i>
                                                        <?= htmlspecialchars($row['ip_address']) ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
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

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#logsTable').DataTable({
                "pageLength": 25, // Medyo mas marami para sa logs
                "ordering": false, // Pinatay natin ang auto-sort ng DataTables dahil naka-sort na ito sa PHP (DESC)
                "language": { "search": "Search Logs (User, Event, IP):" }
            });
        });
    </script>
</body>

</html>