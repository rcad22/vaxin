<?php
// =================================================================
// AJAX INTERCEPTOR: MARK NOTIFICATIONS AS READ
// =================================================================
// Kapag pinindot ang bell, magpapadala ang Javascript ng POST request dito.
// Ise-save natin sa session ang kasalukuyang oras na binasa niya ito.
if (isset($_POST['mark_notifs_read'])) {
    $_SESSION['last_notif_read_time'] = time();
    exit(); // Stop loading the rest of the page for this background request
}
?>

<style>
    /* Bell Animation */
    @keyframes ring {
        0% {
            transform: rotate(0);
        }

        1% {
            transform: rotate(30deg);
        }

        3% {
            transform: rotate(-28deg);
        }

        5% {
            transform: rotate(34deg);
        }

        7% {
            transform: rotate(-32deg);
        }

        9% {
            transform: rotate(30deg);
        }

        11% {
            transform: rotate(-28deg);
        }

        13% {
            transform: rotate(26deg);
        }

        15% {
            transform: rotate(-24deg);
        }

        17% {
            transform: rotate(22deg);
        }

        19% {
            transform: rotate(-20deg);
        }

        21% {
            transform: rotate(18deg);
        }

        23% {
            transform: rotate(-16deg);
        }

        25% {
            transform: rotate(14deg);
        }

        27% {
            transform: rotate(-12deg);
        }

        29% {
            transform: rotate(10deg);
        }

        31% {
            transform: rotate(-8deg);
        }

        33% {
            transform: rotate(6deg);
        }

        35% {
            transform: rotate(-4deg);
        }

        37% {
            transform: rotate(2deg);
        }

        39% {
            transform: rotate(-1deg);
        }

        41% {
            transform: rotate(1deg);
        }

        43% {
            transform: rotate(0);
        }

        100% {
            transform: rotate(0);
        }
    }

    .bell-active {
        animation: ring 4s .7s ease-in-out infinite;
        transform-origin: 50% 4px;
        color: #2d6a4f;
        /* Green Theme */
    }

    /* Dropdown Styling */
    .dropdown-list {
        width: 22rem !important;
    }

    .dropdown-header {
        background-color: #1b4332;
        /* Deep Green Theme */
        border: 1px solid #1b4332;
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
        color: #fff;
        font-weight: 800;
        text-transform: uppercase;
        border-top-left-radius: calc(.35rem - 1px);
        border-top-right-radius: calc(.35rem - 1px);
    }

    .dropdown-item {
        white-space: normal;
        padding-top: .5rem;
        padding-bottom: .5rem;
        border-left: 1px solid #e3e6f0;
        border-right: 1px solid #e3e6f0;
        border-bottom: 1px solid #e3e6f0;
        line-height: 1.3rem;
    }

    /* Badge Pulse */
    .badge-pulse {
        box-shadow: 0 0 0 rgba(231, 74, 59, 0.4);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(231, 74, 59, 0.4);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(231, 74, 59, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(231, 74, 59, 0);
        }
    }

    .unread-item {
        background-color: #f8f9fc;
    }

    /* Light gray background for unread items */
</style>

<nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow" style="background:white;">
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars text-success"></i>
    </button>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown no-arrow mx-1">
            <?php
            // Get the last time the user opened the notifications (Default to midnight today if not set)
            $last_read_time = isset($_SESSION['last_notif_read_time']) ? $_SESSION['last_notif_read_time'] : strtotime('today midnight');

            // Fetch the 5 most recent vaccination records added today
            $notif_sql = "SELECT v.created_at, a.animal_name, a.animal_id_tag, u.fullname, vac.vaccine_name 
                          FROM vaccination_tbl v 
                          JOIN animal_tbl a ON v.animal_id = a.record_id 
                          JOIN user_tbl u ON v.vaccinator_id = u.userid 
                          JOIN vaccine_tbl vac ON v.vaccine_id = vac.vaccine_id 
                          WHERE DATE(v.created_at) = CURRENT_DATE() 
                          ORDER BY v.created_at DESC LIMIT 5";
            $notif_res = mysqli_query($conn, $notif_sql);

            $notifications = [];
            $unreadCount = 0;

            if ($notif_res) {
                while ($row = mysqli_fetch_assoc($notif_res)) {
                    $notifications[] = $row;
                    // Count only those that are newer than the last read time
                    if (strtotime($row['created_at']) > $last_read_time) {
                        $unreadCount++;
                    }
                }
            }

            // Dynamic CSS classes based on unread count
            $bellClass = ($unreadCount > 0) ? 'bell-active' : '';
            $badgeClass = ($unreadCount > 0) ? 'badge-pulse' : '';
            ?>

            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-bell fa-fw <?= $bellClass ?>"></i>
                <?php if ($unreadCount > 0): ?>
                    <span id="notifBadge"
                        class="badge badge-danger badge-counter <?= $badgeClass ?>"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>

            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="alertsDropdown">
                <h6 class="dropdown-header">
                    <i class="fas fa-satellite-dish mr-2"></i> System Updates
                </h6>

                <div style="max-height: 300px; overflow-y: auto;">
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $notif):
                            $notif_time = strtotime($notif['created_at']);
                            $is_unread = ($notif_time > $last_read_time); // Check if this specific item is new
                    
                            // Calculate time ago
                            $time_diff = time() - $notif_time;
                            if ($time_diff < 60)
                                $time_ago = $time_diff . " secs ago";
                            elseif ($time_diff < 3600)
                                $time_ago = floor($time_diff / 60) . " mins ago";
                            else
                                $time_ago = floor($time_diff / 3600) . " hrs ago";

                            $staff_fname = explode(" ", $notif['fullname'])[0];

                            // Add a class if it's unread so it looks slightly highlighted
                            $item_class = $is_unread ? 'unread-item' : '';
                            ?>
                            <a class="dropdown-item d-flex align-items-center <?= $item_class ?>" href="vaccination_logs.php">
                                <div class="mr-3">
                                    <div class="icon-circle" style="background-color: #2d6a4f;">
                                        <i class="fas fa-syringe text-white"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="small text-gray-500 font-weight-bold">
                                        <?= $time_ago ?>
                                        <?php if ($is_unread)
                                            echo '<span class="badge badge-danger ml-1">New</span>'; ?>
                                    </div>
                                    <span class="small font-weight-bold text-dark">
                                        <?= htmlspecialchars($staff_fname) ?> logged a
                                        <?= htmlspecialchars($notif['vaccine_name']) ?> for
                                        <?= htmlspecialchars($notif['animal_name'] ?: $notif['animal_id_tag']) ?>.
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <a class="dropdown-item text-center small text-gray-500" href="#">No new activity today.</a>
                    <?php endif; ?>
                </div>

                <a class="dropdown-item text-center small text-gray-500 bg-light" href="vaccination_logs.php">View All
                    Vaccination Logs</a>
            </div>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 font-weight-bold small">
                    <?php
                    $user_display_name = isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : "MAO Staff";
                    echo $user_display_name;
                    ?>
                </span>
                <img class="img-profile rounded-circle border"
                    src="https://ui-avatars.com/api/?name=<?= urlencode($user_display_name) ?>&background=1b4332&color=fff">
            </a>

            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="profile.php">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profile
                </a>
                <a class="dropdown-item" href="changepassword.php">
                    <i class="fas fa-key fa-sm fa-fw mr-2 text-gray-400"></i> Change Password
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" id="signOutLinkNav">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-danger"></i>
                    <span class="text-danger">Secure Logout</span>
                </a>
            </div>
        </li>
    </ul>
</nav>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function () {
        // MARK AS READ LOGIC
        $('#alertsDropdown').on('click', function () {
            let badge = $('#notifBadge');

            // Kung may pulang badge, itatago natin agad para maganda sa paningin
            if (badge.length > 0 && badge.is(':visible')) {
                // 1. Hide the red counter and stop the ringing animation
                badge.fadeOut('fast');
                $(this).find('.fa-bell').removeClass('bell-active');

                // 2. Remove the "New" text tags inside the dropdown smoothly
                $('.dropdown-list .badge-danger').fadeOut('slow');
                $('.dropdown-item').removeClass('unread-item');

                // 3. Send a background signal (AJAX) to PHP to update the Session timestamp
                $.post(window.location.href, { mark_notifs_read: true });
            }
        });

        // SECURE LOGOUT LOGIC
        document.getElementById('signOutLinkNav')?.addEventListener('click', function (event) {
            event.preventDefault();

            Swal.fire({
                title: 'End Session?',
                text: "Are you sure you want to securely log out of the VAX-IN System?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1b4332',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, log out',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../../logout.php';
                }
            });
        });
    });
</script>