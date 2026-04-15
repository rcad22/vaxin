<?php
session_start();

// Cybersecurity: Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ./../../");
    exit();
}

$userid = $_SESSION['userid'] ?? null;
// Cybersecurity: Fetch user role from session for Role-Based Access Control (RBAC)
$user_role = $_SESSION['role'] ?? 'Field Vaccinator';

$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Define grouped pages based on VAX-IN requirements
$record_pages = ['animals', 'add_animal', 'vaccination_logs'];
$report_pages = ['reports_barangay', 'reports_summary', 'reports_mimaropa', 'vaccination_status', 'list_barangay_animals'];
$admin_pages = ['manage_accounts', 'pending_accounts', 'activity_log'];

// Flag to check if current user is a Field Vaccinator
$is_vaccinator = ($user_role === 'Field Vaccinator');
?>

<style>
    /* ========================================================
       PREMIUM ENTERPRISE SIDEBAR STYLING
       ======================================================== */
    .sidebar {
        position: sticky !important;
        top: 0;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 1050;
    }

    .sidebar::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.3);
        border-radius: 10px;
    }

    /* Main Sidebar Background - Smooth Green Gradient */
    #accordionSidebar {
        background: linear-gradient(180deg, #153826 0%, #205c42 100%) !important;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.15);
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    /* Navigation Links Styling */
    .nav-item .nav-link {
        color: rgba(255, 255, 255, 0.75) !important;
        transition: all 0.2s ease-in-out;
        border-left: 4px solid transparent;
        padding: 12px 15px !important;
        margin: 4px 10px;
        border-radius: 8px;
    }

    /* Hover Effect */
    .nav-item .nav-link:hover {
        color: #fff !important;
        background-color: rgba(255, 255, 255, 0.08);
        transform: translateX(4px);
    }

    /* Active/Current Page Styling - Glowing Soft Mint Accent */
    .nav-item.active>.nav-link {
        background-color: rgba(255, 255, 255, 0.15) !important;
        color: #fff !important;
        font-weight: 600;
        border-left: 4px solid #74c69d;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        margin: 4px 10px;
    }

    /* Icons Styling */
    .nav-link i {
        color: #95d5b2;
        margin-right: 12px;
        font-size: 1.1rem;
        width: 20px;
        text-align: center;
    }

    .nav-item.active .nav-link i {
        color: #fff;
        text-shadow: 0 0 8px rgba(255, 255, 255, 0.4);
    }

    /* Section Headings */
    .sidebar-heading {
        color: rgba(255, 255, 255, 0.5) !important;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-top: 20px;
        margin-bottom: 5px;
        padding-left: 20px;
    }

    /* Dropdown/Collapse Menu Styling */
    .collapse-inner {
        background-color: #f8f9fa !important;
        border-radius: 10px !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.05);
        margin: 0 10px;
    }

    .collapse-item {
        color: #495057 !important;
        margin: 2px 8px;
        border-radius: 6px;
        transition: all 0.2s;
        font-size: 0.85rem;
    }

    .collapse-item:hover {
        background-color: #e9ecef !important;
        color: #153826 !important;
        padding-left: 15px !important;
        font-weight: bold;
    }

    .collapse-item.active {
        background-color: #e9f5e9 !important;
        color: #153826 !important;
        font-weight: 700;
        border-left: 3px solid #2d6a4f;
    }

    /* Logo Styling */
    .sidebar-brand-icon img {
        border: 2px solid rgba(255, 255, 255, 0.8) !important;
        transition: transform 0.3s;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .sidebar-brand:hover .sidebar-brand-icon img {
        transform: rotate(-10deg) scale(1.1);
    }

    /* ========================================================
       MOBILE RESPONSIVENESS & INTERACTIVITY
       ======================================================== */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        display: none;
        /* Hidden by default */
        opacity: 0;
        transition: opacity 0.3s ease;
        backdrop-filter: blur(2px);
    }

    .mobile-bottom-nav {
        display: none;
    }

    @media (max-width: 767.98px) {

        <?php if ($is_vaccinator): ?>
            /* VACCINATOR MOBILE: Hide standard sidebar entirely */
            #accordionSidebar {
                display: none !important;
            }

            /* HIDE TOP HAMBURGER MENU FOR VACCINATOR */
            #sidebarToggleTop {
                display: none !important;
            }

            /* Show beautiful bottom app navigation */
            .mobile-bottom-nav {
                display: flex;
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                background-color: #ffffff;
                box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.08);
                z-index: 1050;
                justify-content: space-around;
                padding: 12px 0;
                padding-bottom: env(safe-area-inset-bottom, 12px);
                border-top-left-radius: 20px;
                border-top-right-radius: 20px;
            }

            .mobile-nav-item {
                text-align: center;
                color: #a0aab2;
                text-decoration: none;
                flex: 1;
                transition: all 0.2s ease;
            }

            .mobile-nav-item:hover {
                color: #1b4332;
                text-decoration: none;
            }

            .mobile-nav-item i {
                display: block;
                font-size: 1.3rem;
                margin-bottom: 4px;
                transition: transform 0.2s;
            }

            .mobile-nav-item span {
                display: block;
                font-size: 0.65rem;
                font-weight: 600;
            }

            .mobile-nav-item.active {
                color: #1b4332;
            }

            .mobile-nav-item.active i {
                color: #2d6a4f;
                transform: translateY(-3px) scale(1.15);
            }

            body {
                padding-bottom: 80px !important;
            }

        <?php else: ?>
            /* ADMIN/SUPPORT MOBILE: Sliding Drawer Sidebar */
            #accordionSidebar {
                position: fixed !important;
                left: -260px;
                /* Hide off-screen */
                width: 260px !important;
                display: flex !important;
                /* Force visibility */
                flex-direction: column;
                z-index: 1050 !important;
                /* Ensure it stays on top */
                transition: left 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
            }

            /* When active/toggled on mobile */
            #accordionSidebar.mobile-show {
                left: 0 !important;
            }

            .sidebar-overlay.mobile-show {
                display: block;
                opacity: 1;
            }

            .mobile-close-btn {
                display: block !important;
            }

        <?php endif; ?>
    }

    .mobile-close-btn {
        display: none;
        position: absolute;
        top: 15px;
        right: 15px;
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.5rem;
        cursor: pointer;
        z-index: 1060;
    }
</style>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">

    <i class="fas fa-times mobile-close-btn" id="closeSidebarBtn"></i>

    <a class="sidebar-brand d-flex align-items-center justify-content-center py-4" href="index">
        <div class="sidebar-brand-icon">
            <img src="./../img/logo.png" width="55" height="55" style="border-radius:50%; object-fit: cover;"
                alt="VAX-IN Logo">
        </div>
        <div class="sidebar-brand-text mx-3">VAX-IN<br><small
                style="font-size:0.6rem; letter-spacing:1px; color:#b7e4c7;">MOGPOG MAO</small></div>
    </a>

    <hr class="sidebar-divider my-0" style="border-color: rgba(255,255,255,0.1);">

    <li class="nav-item <?= $current_page == 'index' ? 'active' : '' ?> mt-3">
        <a class="nav-link" href="index">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block my-2" style="border-color: rgba(255,255,255,0.1);">

    <div class="sidebar-heading">Data Management</div>

    <li class="nav-item <?= in_array($current_page, $record_pages) ? 'active' : '' ?>">
        <a class="nav-link <?= in_array($current_page, $record_pages) ? '' : 'collapsed' ?>" href="#"
            data-toggle="collapse" data-target="#records"
            aria-expanded="<?= in_array($current_page, $record_pages) ? 'true' : 'false' ?>" aria-controls="records">
            <i class="fas fa-fw fa-paw"></i>
            <span>Pet Records</span>
        </a>
        <div id="records" class="collapse <?= in_array($current_page, $record_pages) ? 'show' : '' ?>"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner">
                <h6 class="collapse-header">Manage Pets:</h6>
                <a class="collapse-item <?= $current_page == 'animals' ? 'active' : '' ?>" href="animals">Animals
                    Masterlist</a>
                <a class="collapse-item <?= $current_page == 'vaccination_logs' ? 'active' : '' ?>"
                    href="vaccination_logs">Vaccination History</a>
            </div>
        </div>
    </li>

    <?php if ($user_role === 'Administrator' || $user_role === 'Support Staff'): ?>
        <hr class="sidebar-divider d-none d-md-block my-2" style="border-color: rgba(255,255,255,0.1);">

        <div class="sidebar-heading">Analytics</div>

        <li class="nav-item <?= in_array($current_page, $report_pages) ? 'active' : '' ?>">
            <a class="nav-link <?= in_array($current_page, $report_pages) ? '' : 'collapsed' ?>" href="#"
                data-toggle="collapse" data-target="#reports"
                aria-expanded="<?= in_array($current_page, $report_pages) ? 'true' : 'false' ?>" aria-controls="reports">
                <i class="fas fa-fw fa-file-pdf"></i>
                <span>Reports (PDF)</span>
            </a>
            <div id="reports" class="collapse <?= in_array($current_page, $report_pages) ? 'show' : '' ?>"
                data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner">
                    <h6 class="collapse-header">Generate Forms:</h6>
                    <a class="collapse-item <?= $current_page == 'list_barangay_animals' ? 'active' : '' ?>"
                        href="list_barangay_animals">Animal List</a>
                    <a class="collapse-item <?= $current_page == 'reports_barangay' ? 'active' : '' ?>"
                        href="reports_barangay">By Barangay</a>
                    <a class="collapse-item <?= $current_page == 'reports_mimaropa' ? 'active' : '' ?>"
                        href="reports_mimaropa">MIMAROPA Printout</a>
                    <a class="collapse-item <?= $current_page == 'reports_summary' ? 'active' : '' ?>"
                        href="reports_summary">Vaccine Summary</a>
                    <a class="collapse-item <?= $current_page == 'vaccination_status' ? 'active' : '' ?>"
                        href="vaccination_status">Coverage Status</a>
                </div>
            </div>
        </li>
    <?php endif; ?>

    <?php if ($user_role === 'Administrator'): ?>
        <hr class="sidebar-divider d-none d-md-block my-2" style="border-color: rgba(255,255,255,0.1);">

        <div class="sidebar-heading">System Setup</div>

        <?php $maintenance_pages = ['maintenance_barangay', 'maintenance_pets', 'maintenance_vaccines']; ?>

        <li class="nav-item <?= in_array($current_page, $maintenance_pages) ? 'active' : '' ?>">
            <a class="nav-link <?= in_array($current_page, $maintenance_pages) ? '' : 'collapsed' ?>" href="#"
                data-toggle="collapse" data-target="#maintenance"
                aria-expanded="<?= in_array($current_page, $maintenance_pages) ? 'true' : 'false' ?>"
                aria-controls="maintenance">
                <i class="fas fa-fw fa-cogs"></i>
                <span>Maintenance</span>
            </a>
            <div id="maintenance" class="collapse <?= in_array($current_page, $maintenance_pages) ? 'show' : '' ?>"
                data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner">
                    <h6 class="collapse-header">System Tables:</h6>
                    <a class="collapse-item <?= $current_page == 'maintenance_barangay' ? 'active' : '' ?>"
                        href="maintenance_barangay">Manage Barangays</a>
                    <a class="collapse-item <?= $current_page == 'maintenance_pets' ? 'active' : '' ?>"
                        href="maintenance_pets">Pet Categories</a>
                    <a class="collapse-item <?= $current_page == 'maintenance_vaccines' ? 'active' : '' ?>"
                        href="maintenance_vaccines">Vaccine Types</a>
                </div>
            </div>
        </li>

        <hr class="sidebar-divider d-none d-md-block my-2" style="border-color: rgba(255,255,255,0.1);">

        <div class="sidebar-heading">System Control</div>

        <li class="nav-item <?= in_array($current_page, $admin_pages) ? 'active' : '' ?>">
            <a class="nav-link <?= in_array($current_page, $admin_pages) ? '' : 'collapsed' ?>" href="#"
                data-toggle="collapse" data-target="#admin"
                aria-expanded="<?= in_array($current_page, $admin_pages) ? 'true' : 'false' ?>" aria-controls="admin">
                <i class="fas fa-fw fa-users-cog"></i>
                <span>Administration</span>
            </a>
            <div id="admin" class="collapse <?= in_array($current_page, $admin_pages) ? 'show' : '' ?>"
                data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner">
                    <h6 class="collapse-header">Access Management:</h6>
                    <a class="collapse-item <?= $current_page == 'manage_accounts' ? 'active' : '' ?>"
                        href="manage_accounts">Manage User Accounts</a>
                    <div class="collapse-divider"></div>
                    <h6 class="collapse-header">System Logs:</h6>
                    <a class="collapse-item <?= $current_page == 'activity_log' ? 'active' : '' ?>"
                        href="activity_log">Activity Log</a>
                </div>
            </div>
        </li>
    <?php endif; ?>

    <hr class="sidebar-divider my-3" style="border-color: rgba(255,255,255,0.1);">

    <li class="nav-item mb-4">
        <a class="nav-link sign-out-trigger" href="#" style="color: #ff9b9b !important;">
            <i class="fas fa-fw fa-power-off text-danger"></i>
            <span class="font-weight-bold">Sign Out</span>
        </a>
    </li>

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"
            style="background-color: rgba(255,255,255,0.2);"></button>
    </div>
</ul>

<?php if ($is_vaccinator): ?>
    <div class="mobile-bottom-nav">
        <a href="index" class="mobile-nav-item <?= $current_page == 'index' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="animals" class="mobile-nav-item <?= $current_page == 'animals' ? 'active' : '' ?>">
            <i class="fas fa-paw"></i>
            <span>Animals</span>
        </a>
        <a href="vaccination_logs" class="mobile-nav-item <?= $current_page == 'vaccination_logs' ? 'active' : '' ?>">
            <i class="fas fa-syringe"></i>
            <span>Vax Logs</span>
        </a>
        <a href="#" class="mobile-nav-item sign-out-trigger text-danger">
            <i class="fas fa-power-off"></i>
            <span>Logout</span>
        </a>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {

        <?php if (!$is_vaccinator): ?>
            // 1. SMART MOBILE SIDEBAR TOGGLE (For Admins/Support ONLY)
            const sidebar = document.getElementById('accordionSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const closeBtn = document.getElementById('closeSidebarBtn');

            // Hanapin yung default hamburger menu ng SB Admin 2 (Nasa navbar.php ito)
            const topbarToggle = document.getElementById('sidebarToggleTop');

            function toggleMobileSidebar(e) {
                if (window.innerWidth < 768) {
                    if (e) {
                        e.preventDefault(); // Pigilan yung default action ng browser
                        e.stopPropagation(); // Pigilan yung default action ng SB Admin 2 script
                    }
                    sidebar.classList.toggle('mobile-show');
                    overlay.classList.toggle('mobile-show');
                }
            }

            // Kapag pinindot yung hamburger menu sa taas
            if (topbarToggle) {
                topbarToggle.addEventListener('click', toggleMobileSidebar);
            }

            // Kapag pinindot yung "X" sa loob ng sidebar
            if (closeBtn) {
                closeBtn.addEventListener('click', toggleMobileSidebar);
            }

            // Kapag pinindot yung madilim na background
            if (overlay) {
                overlay.addEventListener('click', toggleMobileSidebar);
            }
        <?php endif; ?>

        // 2. SIGN OUT CONFIRMATION (Desktop & Mobile)
        document.querySelectorAll('.sign-out-trigger').forEach(function (elem) {
            elem.addEventListener('click', function (event) {
                event.preventDefault();
                Swal.fire({
                    title: 'Sign Out?',
                    text: "Are you sure you want to end your secure session?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#1b4332',
                    cancelButtonColor: '#e63946',
                    confirmButtonText: 'Yes, sign out',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../../logout.php';
                    }
                });
            });
        });

    });
</script>