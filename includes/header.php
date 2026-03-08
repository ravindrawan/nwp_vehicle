<?php
// Ensure constants and session are loaded
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/constants.php';
}
require_once __DIR__ . '/../config/session.php';

$user = currentUser();
$userName  = $user['full_name'] ?? 'Guest';
$userRole  = $user['role'] ?? '';
$userInitial = strtoupper(substr($userName, 0, 1));

// Role label map
$roleLabelMap = [
    'super_admin'      => 'Super Admin',
    'office_admin'     => 'Office Admin',
    'subject_officer'  => 'Subject Officer',
    'general_user'     => 'General User',
];
$roleLabel = $roleLabelMap[$userRole] ?? ucwords(str_replace('_', ' ', $userRole));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?><?= APP_NAME ?></title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <!-- Custom -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<div class="app-wrapper">

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ═══ SIDEBAR ════════════════════════════════════════════════ -->
<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <div class="brand-icon"><i class="bi bi-truck-front-fill"></i></div>
            <div>
                <div>Fleet Management</div>
                <div class="brand-sub">North Western Province</div>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="list-unstyled mb-0">

            <!-- Dashboard — all roles -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>dashboard.php"
                   class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </li>

            <?php if (hasAnyRole(['super_admin', 'subject_officer', 'office_admin', 'general_user'])): ?>
            <!-- SEARCH — all roles -->
            <li class="sidebar-section-label">Booking</li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>search.php"
                   class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'search.php' ? 'active' : '' ?>">
                    <i class="bi bi-search"></i> Search Vehicles
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>modules/bookings/my_bookings.php"
                   class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'my_bookings.php' ? 'active' : '' ?>">
                    <i class="bi bi-calendar-check"></i> My Bookings
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasAnyRole(['super_admin', 'subject_officer', 'office_admin'])): ?>
            <!-- BOOKINGS MANAGEMENT -->
            <li class="sidebar-section-label">Booking Management</li>
            <?php if (hasAnyRole(['office_admin', 'super_admin'])): ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>modules/bookings/pending.php"
                   class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'pending.php' ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i> Pending Approvals
                    <?php
                    // Show pending count badge
                    try {
                        global $pdo;
                        if (!isset($pdo)) {
                            require_once __DIR__ . '/../config/db.php';
                        }
                        $countSql = hasRole('super_admin')
                            ? "SELECT COUNT(*) FROM bookings WHERE status='pending'"
                            : "SELECT COUNT(*) FROM bookings b JOIN vehicles v ON b.vehicle_id=v.id WHERE b.status='pending' AND v.office_id=:oid";
                        $countStmt = $pdo->prepare($countSql);
                        if (!hasRole('super_admin')) $countStmt->bindValue(':oid', currentOfficeId());
                        $countStmt->execute();
                        $pendingCount = $countStmt->fetchColumn();
                        if ($pendingCount > 0): ?>
                            <span class="badge bg-warning text-dark ms-auto"><?= $pendingCount ?></span>
                        <?php endif;
                    } catch(Exception $e) {}
                    ?>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>modules/bookings/list.php"
                   class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'list.php' && strpos($_SERVER['PHP_SELF'], 'bookings') !== false ? 'active' : '' ?>">
                    <i class="bi bi-list-ul"></i> All Bookings
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasAnyRole(['super_admin', 'subject_officer'])): ?>
            <!-- FLEET -->
            <li class="sidebar-section-label">Fleet</li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>modules/vehicles/list.php"
                   class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'list.php' && strpos($_SERVER['PHP_SELF'], 'vehicles') !== false ? 'active' : '' ?>">
                    <i class="bi bi-car-front-fill"></i> Vehicles
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>modules/drivers/list.php"
                   class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'list.php' && strpos($_SERVER['PHP_SELF'], 'drivers') !== false ? 'active' : '' ?>">
                    <i class="bi bi-person-badge-fill"></i> Drivers
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasRole('super_admin')): ?>
            <!-- ADMINISTRATION -->
            <li class="sidebar-section-label">Administration</li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>modules/offices/list.php"
                   class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'list.php' && strpos($_SERVER['PHP_SELF'], 'offices') !== false ? 'active' : '' ?>">
                    <i class="bi bi-building"></i> Offices
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>modules/users/list.php"
                   class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'list.php' && strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'active' : '' ?>">
                    <i class="bi bi-people-fill"></i> User Accounts
                </a>
            </li>
            <?php endif; ?>

            <!-- ACCOUNT -->
            <li class="sidebar-section-label">Account</li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>logout.php" class="nav-link text-danger-hover">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </a>
            </li>

        </ul>
    </nav>
</aside>
<!-- ═══ END SIDEBAR ════════════════════════════════════════════ -->

<div class="main-content">
<!-- ═══ TOP NAV ════════════════════════════════════════════════ -->
<header class="topnav">
    <button class="btn-sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
    </button>

    <span class="page-title"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : APP_NAME ?></span>

    <div class="user-info dropdown">
        <button class="d-flex align-items-center gap-2 bg-transparent border-0 p-0 cursor-pointer"
                data-bs-toggle="dropdown" aria-expanded="false">
            <div class="user-avatar"><?= $userInitial ?></div>
            <div class="user-details text-start d-none d-md-block">
                <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="user-role"><?= $roleLabel ?></div>
            </div>
            <i class="bi bi-chevron-down text-muted ms-1" style="font-size:.7rem;"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end mt-2 shadow-sm">
            <li><h6 class="dropdown-header">Signed in as <strong><?= htmlspecialchars($userName) ?></strong></h6></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php">
                <i class="bi bi-box-arrow-left me-2"></i>Logout
            </a></li>
        </ul>
    </div>
</header>
<!-- ═══ END TOP NAV ════════════════════════════════════════════ -->

<div class="page-content">
