<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin','subject_officer','office_admin']);

$pageTitle = 'All Bookings';
$isSuper  = hasRole('super_admin');
$officeId = currentOfficeId();

$sql = $isSuper
    ? "SELECT b.*, v.reg_number, v.type AS vtype, o.name AS office_name,
              u.full_name AS booker, app.full_name AS approved_by_name
       FROM bookings b
       JOIN vehicles v ON b.vehicle_id = v.id
       JOIN offices o ON v.office_id = o.id
       JOIN users u ON b.booked_by_user_id = u.id
       LEFT JOIN users app ON b.approved_by = app.id
       ORDER BY b.created_at DESC"
    : "SELECT b.*, v.reg_number, v.type AS vtype, o.name AS office_name,
              u.full_name AS booker, app.full_name AS approved_by_name
       FROM bookings b
       JOIN vehicles v ON b.vehicle_id = v.id
       JOIN offices o ON v.office_id = o.id
       JOIN users u ON b.booked_by_user_id = u.id
       LEFT JOIN users app ON b.approved_by = app.id
       WHERE v.office_id = :oid
       ORDER BY b.created_at DESC";

$s = $pdo->prepare($sql);
if (!$isSuper) $s->bindValue(':oid', $officeId);
$s->execute();
$bookings = $s->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-heading">All Bookings</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Bookings</li>
    </ol></nav>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table data-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Vehicle</th>
                        <th>Office</th>
                        <th>Booker</th>
                        <th>Journey Date</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th class="pe-3 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $i => $b):
                    $badgeMap = ['pending'=>'badge-pending','approved'=>'badge-approved','rejected'=>'badge-rejected'];
                    $cls = $badgeMap[$b['status']] ?? 'bg-secondary';
                ?>
                <tr>
                    <td class="ps-3"><?= $b['id'] ?></td>
                    <td>
                        <div class="fw-600 vehicle-reg"><?= htmlspecialchars($b['reg_number']) ?></div>
                        <div class="text-muted-sm"><?= htmlspecialchars($b['vtype']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($b['office_name']) ?></td>
                    <td><?= htmlspecialchars($b['booker']) ?></td>
                    <td><?= date('d M Y', strtotime($b['journey_date'])) ?></td>
                    <td><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                    <td><span class="badge <?= $cls ?>"><?= ucfirst($b['status']) ?></span></td>
                    <td class="pe-3 no-print">
                        <a href="view.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if (hasAnyRole(['super_admin','subject_officer']) && $b['status'] === 'pending' && !$b['nirdesha']): ?>
                        <a href="recommend.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="Add Recommendation">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <?php endif; ?>
                        <a href="print.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Print">
                            <i class="bi bi-printer"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
