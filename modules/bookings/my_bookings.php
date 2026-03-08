<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

$pageTitle = 'My Bookings';
$userId = currentUser()['id'];

$bookings = $pdo->prepare(
    "SELECT b.*, v.reg_number, v.type AS vtype, o.name AS office_name
     FROM bookings b
     JOIN vehicles v ON b.vehicle_id = v.id
     JOIN offices o ON v.office_id = o.id
     WHERE b.booked_by_user_id = ?
     ORDER BY b.created_at DESC"
);
$bookings->execute([$userId]);
$bookings = $bookings->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="page-heading">My Bookings</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">My Bookings</li>
        </ol></nav>
    </div>
    <a href="<?= BASE_URL ?>search.php" class="btn btn-primary no-print">
        <i class="bi bi-plus-circle me-1"></i> New Booking
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success auto-dismiss"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table data-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Vehicle</th>
                        <th>Office</th>
                        <th>Journey Date</th>
                        <th>Route</th>
                        <th>Status</th>
                        <th class="pe-3 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($bookings)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No bookings yet. <a href="<?= BASE_URL ?>search.php">Search for a vehicle</a> to make your first booking.</td></tr>
                <?php else: ?>
                <?php foreach ($bookings as $i => $b):
                    $badgeMap = ['pending'=>'badge-pending','approved'=>'badge-approved','rejected'=>'badge-rejected'];
                    $cls = $badgeMap[$b['status']] ?? 'bg-secondary';
                ?>
                <tr>
                    <td class="ps-3"><?= $i+1 ?></td>
                    <td>
                        <div class="fw-600 vehicle-reg"><?= htmlspecialchars($b['reg_number']) ?></div>
                        <div class="text-muted-sm"><?= htmlspecialchars($b['vtype']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($b['office_name']) ?></td>
                    <td>
                        <?= date('d M Y', strtotime($b['journey_date'])) ?>
                        <div class="text-muted-sm"><?= date('H:i', strtotime($b['start_time'])) ?> – <?= date('H:i', strtotime($b['return_time'])) ?></div>
                    </td>
                    <td>
                        <div class="fw-600"><?= htmlspecialchars($b['start_location']) ?></div>
                        <div class="text-muted-sm"><i class="bi bi-arrow-right me-1"></i><?= htmlspecialchars($b['destinations']) ?></div>
                    </td>
                    <td><span class="badge <?= $cls ?>"><?= ucfirst($b['status']) ?></span></td>
                    <td class="pe-3 no-print">
                        <a href="view.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if ($b['status'] === 'pending'): ?>
                        <a href="<?= BASE_URL ?>modules/bookings/print.php?id=<?= $b['id'] ?>"
                           class="btn btn-sm btn-outline-secondary" target="_blank">
                            <i class="bi bi-printer"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
