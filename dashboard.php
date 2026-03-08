<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
requireLogin();

$pageTitle = 'Dashboard';
$user = currentUser();

// ── Stats ────────────────────────────────────────────────────
$isSuper = hasRole('super_admin');
$officeId = currentOfficeId();

// Total Vehicles
$q = $isSuper
    ? "SELECT COUNT(*) FROM vehicles"
    : "SELECT COUNT(*) FROM vehicles WHERE office_id = :oid";
$s = $pdo->prepare($q);
if (!$isSuper) $s->bindValue(':oid', $officeId);
$s->execute();
$totalVehicles = $s->fetchColumn();

// Total Drivers
$q = $isSuper
    ? "SELECT COUNT(*) FROM drivers"
    : "SELECT COUNT(*) FROM drivers WHERE office_id = :oid";
$s = $pdo->prepare($q);
if (!$isSuper) $s->bindValue(':oid', $officeId);
$s->execute();
$totalDrivers = $s->fetchColumn();

// Pending Bookings
$q = $isSuper
    ? "SELECT COUNT(*) FROM bookings WHERE status = 'pending'"
    : "SELECT COUNT(*) FROM bookings b JOIN vehicles v ON b.vehicle_id = v.id WHERE b.status='pending' AND v.office_id = :oid";
$s = $pdo->prepare($q);
if (!$isSuper) $s->bindValue(':oid', $officeId);
$s->execute();
$pendingBookings = $s->fetchColumn();

// Approved Bookings
$q = $isSuper
    ? "SELECT COUNT(*) FROM bookings WHERE status = 'approved'"
    : "SELECT COUNT(*) FROM bookings b JOIN vehicles v ON b.vehicle_id = v.id WHERE b.status='approved' AND v.office_id = :oid";
$s = $pdo->prepare($q);
if (!$isSuper) $s->bindValue(':oid', $officeId);
$s->execute();
$approvedBookings = $s->fetchColumn();

// Total Offices (super_admin only)
$totalOffices = 0;
if ($isSuper) {
    $totalOffices = $pdo->query("SELECT COUNT(*) FROM offices")->fetchColumn();
}

// Recent bookings (last 10)
$recentSql = $isSuper
    ? "SELECT b.*, v.reg_number, v.type AS vtype, o.name AS office_name, u.full_name AS booker
       FROM bookings b
       JOIN vehicles v ON b.vehicle_id = v.id
       JOIN offices o ON v.office_id = o.id
       JOIN users u ON b.booked_by_user_id = u.id
       ORDER BY b.created_at DESC LIMIT 10"
    : "SELECT b.*, v.reg_number, v.type AS vtype, o.name AS office_name, u.full_name AS booker
       FROM bookings b
       JOIN vehicles v ON b.vehicle_id = v.id
       JOIN offices o ON v.office_id = o.id
       JOIN users u ON b.booked_by_user_id = u.id
       WHERE v.office_id = :oid
       ORDER BY b.created_at DESC LIMIT 10";
$rs = $pdo->prepare($recentSql);
if (!$isSuper) $rs->bindValue(':oid', $officeId);
$rs->execute();
$recentBookings = $rs->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1 class="page-heading">Dashboard</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">Home</li>
        </ol>
    </nav>
</div>

<!-- ── Stats Row ────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-car-front-fill"></i></div>
            <div>
                <div class="stat-value"><?= $totalVehicles ?></div>
                <div class="stat-label">Total Vehicles</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="stat-value"><?= $pendingBookings ?></div>
                <div class="stat-label">Pending Bookings</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-calendar-check-fill"></i></div>
            <div>
                <div class="stat-value"><?= $approvedBookings ?></div>
                <div class="stat-label">Approved Bookings</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <?php if ($isSuper): ?>
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-building"></i></div>
            <div>
                <div class="stat-value"><?= $totalOffices ?></div>
                <div class="stat-label">Total Offices</div>
            </div>
        </div>
        <?php else: ?>
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-person-badge-fill"></i></div>
            <div>
                <div class="stat-value"><?= $totalDrivers ?></div>
                <div class="stat-label">Total Drivers</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Quick Actions ─────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-lightning-fill text-warning"></i>
                <h6>Quick Actions</h6>
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL ?>search.php" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i> Search Vehicles
                </a>
                <a href="<?= BASE_URL ?>modules/bookings/my_bookings.php" class="btn btn-outline-secondary">
                    <i class="bi bi-calendar-check me-1"></i> My Bookings
                </a>
                <?php if (hasAnyRole(['super_admin', 'office_admin'])): ?>
                <a href="<?= BASE_URL ?>modules/bookings/pending.php" class="btn btn-outline-warning">
                    <i class="bi bi-clock-history me-1"></i> Pending Approvals
                    <?php if ($pendingBookings > 0): ?><span class="badge bg-warning text-dark ms-1"><?= $pendingBookings ?></span><?php endif; ?>
                </a>
                <?php endif; ?>
                <?php if (hasAnyRole(['super_admin', 'subject_officer'])): ?>
                <a href="<?= BASE_URL ?>modules/vehicles/add.php" class="btn btn-outline-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Vehicle
                </a>
                <a href="<?= BASE_URL ?>modules/drivers/add.php" class="btn btn-outline-primary">
                    <i class="bi bi-person-plus me-1"></i> Add Driver
                </a>
                <?php endif; ?>
                <?php if (hasRole('super_admin')): ?>
                <a href="<?= BASE_URL ?>modules/offices/add.php" class="btn btn-outline-secondary">
                    <i class="bi bi-building-add me-1"></i> Add Office
                </a>
                <a href="<?= BASE_URL ?>modules/users/add.php" class="btn btn-outline-secondary">
                    <i class="bi bi-person-plus me-1"></i> Add User
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Recent Bookings Table ─────────────────────────────────── -->
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-list-ul text-primary"></i>
        <h6>Recent Bookings</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Vehicle</th>
                        <th>Booker</th>
                        <th>Office</th>
                        <th>Journey Date</th>
                        <th>Status</th>
                        <th class="pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($recentBookings)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No bookings found.</td></tr>
                <?php else: ?>
                <?php foreach ($recentBookings as $bk): ?>
                <tr>
                    <td class="ps-3 text-muted"><?= $bk['id'] ?></td>
                    <td>
                        <div class="fw-600"><?= htmlspecialchars($bk['reg_number']) ?></div>
                        <div class="text-muted-sm"><?= htmlspecialchars($bk['vtype']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($bk['booker']) ?></td>
                    <td><?= htmlspecialchars($bk['office_name']) ?></td>
                    <td><?= date('d M Y', strtotime($bk['journey_date'])) ?></td>
                    <td>
                        <?php
                        $badgeMap = ['pending'=>'badge-pending','approved'=>'badge-approved','rejected'=>'badge-rejected'];
                        $cls = $badgeMap[$bk['status']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $cls ?>"><?= ucfirst($bk['status']) ?></span>
                    </td>
                    <td class="pe-3">
                        <a href="<?= BASE_URL ?>modules/bookings/view.php?id=<?= $bk['id'] ?>"
                           class="btn btn-sm btn-outline-primary">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
