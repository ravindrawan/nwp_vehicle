<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin','office_admin']);

$pageTitle = 'Pending Approvals';
$isSuper  = hasRole('super_admin');
$officeId = currentOfficeId();

// ── Handle Approve / Reject actions ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $action    = $_POST['action'] ?? '';
    $reason    = trim($_POST['rejection_reason'] ?? '');

    if ($bookingId > 0 && in_array($action, ['approve','reject'], true)) {
        // Verify the booking belongs to this admin's office
        $verifySql = "SELECT b.id FROM bookings b
             JOIN vehicles v ON b.vehicle_id=v.id
             WHERE b.id = :bid AND b.status='pending'";
        if (!$isSuper) {
            $verifySql .= " AND v.office_id = :oid";
        }
        $verify = $pdo->prepare($verifySql);
        $verify->bindValue(':bid', $bookingId, PDO::PARAM_INT);
        if (!$isSuper) $verify->bindValue(':oid', $officeId, PDO::PARAM_INT);
        $verify->execute();

        if ($verify->fetch()) {
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            $pdo->prepare(
                "UPDATE bookings SET status=?, approved_by=?, approved_at=NOW(), rejection_reason=? WHERE id=?"
            )->execute([$newStatus, currentUser()['id'], $reason ?: null, $bookingId]);
        }
    }
    header('Location: pending.php?msg=' . urlencode($action === 'approve' ? 'Booking approved successfully.' : 'Booking rejected.'));
    exit;
}

// ── Load pending bookings ────────────────────────────────────
$sql = $isSuper
    ? "SELECT b.*, v.reg_number, v.type AS vtype, o.name AS office_name,
              u.full_name AS booker, nrd.full_name AS nirdesha_by_name
       FROM bookings b
       JOIN vehicles v ON b.vehicle_id = v.id
       JOIN offices o ON v.office_id = o.id
       JOIN users u ON b.booked_by_user_id = u.id
       LEFT JOIN users nrd ON b.nirdesha_by = nrd.id
       WHERE b.status = 'pending'
       ORDER BY b.created_at ASC"
    : "SELECT b.*, v.reg_number, v.type AS vtype, o.name AS office_name,
              u.full_name AS booker, nrd.full_name AS nirdesha_by_name
       FROM bookings b
       JOIN vehicles v ON b.vehicle_id = v.id
       JOIN offices o ON v.office_id = o.id
       JOIN users u ON b.booked_by_user_id = u.id
       LEFT JOIN users nrd ON b.nirdesha_by = nrd.id
       WHERE b.status = 'pending' AND v.office_id = :oid
       ORDER BY b.created_at ASC";

$s = $pdo->prepare($sql);
if (!$isSuper) $s->bindValue(':oid', $officeId);
$s->execute();
$pending = $s->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-heading">Pending Approvals</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Pending Approvals</li>
    </ol></nav>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success auto-dismiss"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<?php if (empty($pending)): ?>
<div class="text-center py-5">
    <i class="bi bi-check2-circle" style="font-size:3rem; color:#16a34a;"></i>
    <p class="mt-3 text-muted">No pending bookings at this time. All caught up!</p>
</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($pending as $b): ?>
<div class="col-lg-6">
<div class="card h-100" style="border-left: 4px solid var(--warning);">
    <div class="card-body">
        <!-- Header row -->
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="vehicle-reg fs-6"><?= htmlspecialchars($b['reg_number']) ?></div>
                <div class="text-muted-sm"><?= htmlspecialchars($b['vtype']) ?> &middot; <?= htmlspecialchars($b['office_name']) ?></div>
            </div>
            <span class="badge badge-pending">Pending</span>
        </div>

        <!-- Journey Info Grid -->
        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="text-muted-sm">Booker</div>
                <div class="fw-600"><?= htmlspecialchars($b['booker']) ?></div>
            </div>
            <div class="col-6">
                <div class="text-muted-sm">Journey Date</div>
                <div class="fw-600"><?= date('d M Y', strtotime($b['journey_date'])) ?></div>
            </div>
            <div class="col-6">
                <div class="text-muted-sm">Start Time</div>
                <div><?= date('H:i', strtotime($b['start_time'])) ?> → <?= date('H:i', strtotime($b['return_time'])) ?></div>
            </div>
            <div class="col-6">
                <div class="text-muted-sm">Distance</div>
                <div><?= $b['distance_km'] ? $b['distance_km'] . ' km' : '—' ?></div>
            </div>
            <div class="col-12">
                <div class="text-muted-sm">Route</div>
                <div><?= htmlspecialchars($b['start_location']) ?> → <?= htmlspecialchars($b['destinations']) ?></div>
            </div>
        </div>

        <!-- Nirdesha -->
        <?php if ($b['nirdesha']): ?>
        <div class="p-2 bg-light rounded mb-3 border-start border-3 border-primary ps-3">
            <div class="text-muted-sm fw-600 mb-1">Recommendation by <?= htmlspecialchars($b['nirdesha_by_name'] ?? 'Officer') ?>:</div>
            <div style="font-size:.85rem;"><?= nl2br(htmlspecialchars($b['nirdesha'])) ?></div>
        </div>
        <?php else: ?>
        <div class="p-2 bg-light rounded mb-3 border-start border-3 border-secondary ps-3">
            <div class="text-muted-sm"><i class="bi bi-info-circle me-1"></i>No recommendation added yet.</div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="d-flex gap-2 flex-wrap">
            <a href="view.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-eye me-1"></i> View Details
            </a>
            <button class="btn btn-sm btn-success"
                    onclick="doApprove(<?= $b['id'] ?>)">
                <i class="bi bi-check-circle me-1"></i> Approve
            </button>
            <button class="btn btn-sm btn-danger"
                    onclick="doReject(<?= $b['id'] ?>)">
                <i class="bi bi-x-circle me-1"></i> Reject
            </button>
            <a href="print.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                <i class="bi bi-printer me-1"></i> Print
            </a>
        </div>
    </div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Hidden form for approve/reject POST -->
<form id="actionForm" method="POST" style="display:none;">
    <input type="hidden" name="booking_id" id="formBookingId">
    <input type="hidden" name="action" id="formAction">
    <input type="hidden" name="rejection_reason" id="formReason">
</form>

<script>
function doApprove(id) {
    Swal.fire({
        title: 'Approve Booking?',
        text: 'The vehicle will be officially locked for this booking.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        confirmButtonText: 'Yes, Approve'
    }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('formBookingId').value = id;
            document.getElementById('formAction').value = 'approve';
            document.getElementById('formReason').value = '';
            document.getElementById('actionForm').submit();
        }
    });
}

function doReject(id) {
    Swal.fire({
        title: 'Reject Booking?',
        input: 'textarea',
        inputLabel: 'Rejection reason (optional)',
        inputPlaceholder: 'Enter a reason for rejection…',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Reject',
    }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('formBookingId').value = id;
            document.getElementById('formAction').value = 'reject';
            document.getElementById('formReason').value = r.value || '';
            document.getElementById('actionForm').submit();
        }
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
