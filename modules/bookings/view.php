<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);

// Load booking with full details
$stmt = $pdo->prepare(
    "SELECT b.*,
            v.reg_number, v.type AS vtype, v.brand, v.fuel_type, v.seating_capacity, v.ac_available, v.office_id,
            o.name AS office_name, o.telephone AS office_tel, o.email AS office_email,
            d.full_name AS driver_name, d.contact_number AS driver_contact,
            u.full_name AS booked_by_name,
            app.full_name AS approved_by_name,
            nrd.full_name AS nirdesha_by_name
     FROM bookings b
     JOIN vehicles v ON b.vehicle_id = v.id
     JOIN offices o ON v.office_id = o.id
     LEFT JOIN drivers d ON v.driver_id = d.id
     JOIN users u ON b.booked_by_user_id = u.id
     LEFT JOIN users app ON b.approved_by = app.id
     LEFT JOIN users nrd ON b.nirdesha_by = nrd.id
     WHERE b.id = ?"
);
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: my_bookings.php');
    exit;
}

// Access control: general user can only see own bookings
if (hasRole('general_user') && $booking['booked_by_user_id'] != currentUser()['id']) {
    http_response_code(403); die('Access denied.');
}

$pageTitle = 'Booking Details #' . $id;
$badgeMap = ['pending'=>'badge-pending','approved'=>'badge-approved','rejected'=>'badge-rejected'];
$badgeCls = $badgeMap[$booking['status']] ?? 'bg-secondary';

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="page-heading">Booking #<?= $id ?></h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="my_bookings.php">My Bookings</a></li>
            <li class="breadcrumb-item active">View</li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2 no-print">
        <span class="badge <?= $badgeCls ?> fs-6"><?= ucfirst($booking['status']) ?></span>
        <a href="print.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="bi bi-printer me-1"></i> Print
        </a>
    </div>
</div>

<div class="row g-3">
    <!-- Vehicle Info -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><h6><i class="bi bi-car-front-fill me-2 text-primary"></i>Vehicle Details</h6></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted" style="width:45%">Registration</td><td class="fw-600 vehicle-reg"><?= htmlspecialchars($booking['reg_number']) ?></td></tr>
                    <tr><td class="text-muted">Type</td><td><?= htmlspecialchars($booking['vtype']) ?></td></tr>
                    <tr><td class="text-muted">Brand</td><td><?= htmlspecialchars($booking['brand'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted">Fuel</td><td><?= htmlspecialchars($booking['fuel_type']) ?></td></tr>
                    <tr><td class="text-muted">Seats</td><td><?= $booking['seating_capacity'] ?></td></tr>
                    <tr><td class="text-muted">A/C</td><td><?= $booking['ac_available'] ? 'Yes' : 'No' ?></td></tr>
                    <tr><td class="text-muted">Driver</td><td><?= htmlspecialchars($booking['driver_name'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted">Office</td><td><?= htmlspecialchars($booking['office_name']) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Booking Details -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><h6><i class="bi bi-calendar-event me-2 text-primary"></i>Booking Information</h6></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted" style="width:45%">Booker</td><td class="fw-600"><?= htmlspecialchars($booking['booker_name']) ?></td></tr>
                    <tr><td class="text-muted">Journey Date</td><td><?= date('d M Y', strtotime($booking['journey_date'])) ?></td></tr>
                    <tr><td class="text-muted">Start Time</td><td><?= date('H:i', strtotime($booking['start_time'])) ?></td></tr>
                    <tr><td class="text-muted">Return Time</td><td><?= date('H:i', strtotime($booking['return_time'])) ?></td></tr>
                    <tr><td class="text-muted">From</td><td><?= htmlspecialchars($booking['start_location']) ?></td></tr>
                    <tr><td class="text-muted">To</td><td><?= htmlspecialchars($booking['destinations']) ?></td></tr>
                    <tr><td class="text-muted">Distance</td><td><?= $booking['distance_km'] ? $booking['distance_km'] . ' km' : '—' ?></td></tr>
                    <tr><td class="text-muted">Booked On</td><td><?= date('d M Y H:i', strtotime($booking['created_at'])) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Nirdesha & Approval -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h6><i class="bi bi-clipboard-check me-2 text-primary"></i>Workflow Status</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-600">Subject Officer Recommendation (Nirdesha)</label>
                        <?php if ($booking['nirdesha']): ?>
                        <div class="p-3 bg-light rounded-3 border">
                            <?= nl2br(htmlspecialchars($booking['nirdesha'])) ?>
                        </div>
                        <div class="text-muted-sm mt-1">By <?= htmlspecialchars($booking['nirdesha_by_name'] ?? 'Unknown') ?>
                            <?= $booking['nirdesha_at'] ? ' on ' . date('d M Y H:i', strtotime($booking['nirdesha_at'])) : '' ?>
                        </div>
                        <?php else: ?>
                        <div class="text-muted">No recommendation added yet.</div>
                        <?php endif; ?>

                        <?php if (hasAnyRole(['super_admin','subject_officer']) && $booking['status'] === 'pending' && !$booking['nirdesha']): ?>
                        <div class="mt-2 no-print">
                            <a href="recommend.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i> Add Recommendation
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-600">Approval Decision</label>
                        <?php if ($booking['status'] === 'approved'): ?>
                        <div class="p-3 bg-light rounded-3 border border-success">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <strong>Approved</strong> by <?= htmlspecialchars($booking['approved_by_name'] ?? 'Unknown') ?>
                            <?= $booking['approved_at'] ? ' on ' . date('d M Y H:i', strtotime($booking['approved_at'])) : '' ?>
                        </div>
                        <?php elseif ($booking['status'] === 'rejected'): ?>
                        <div class="p-3 bg-light rounded-3 border border-danger">
                            <i class="bi bi-x-circle-fill text-danger me-2"></i>
                            <strong>Rejected</strong> by <?= htmlspecialchars($booking['approved_by_name'] ?? 'Unknown') ?>
                            <?= $booking['approved_at'] ? ' on ' . date('d M Y H:i', strtotime($booking['approved_at'])) : '' ?>
                            <?php if ($booking['rejection_reason']): ?>
                            <div class="mt-2 text-muted-sm">Reason: <?= htmlspecialchars($booking['rejection_reason']) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-muted">Awaiting approval from Office Admin.</div>
                        <?php if (hasAnyRole(['super_admin','office_admin'])): ?>
                        <div class="mt-2 no-print">
                            <a href="pending.php" class="btn btn-sm btn-outline-warning">Go to Pending Approvals</a>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
