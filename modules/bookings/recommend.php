<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin','subject_officer','office_admin']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, v.office_id FROM bookings b JOIN vehicles v ON b.vehicle_id=v.id WHERE b.id=?");
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking || $booking['status'] !== 'pending') {
    header('Location: list.php');
    exit;
}

// Office isolation
if (!hasRole('super_admin') && $booking['office_id'] != currentOfficeId()) {
    http_response_code(403); die('Access denied.');
}

$pageTitle = 'Add Recommendation';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nirdesha = trim($_POST['nirdesha'] ?? '');
    if ($nirdesha === '') $errors[] = 'Recommendation text cannot be empty.';

    if (empty($errors)) {
        $pdo->prepare("UPDATE bookings SET nirdesha=?, nirdesha_by=?, nirdesha_at=NOW() WHERE id=?")
            ->execute([$nirdesha, currentUser()['id'], $id]);
        header('Location: view.php?id=' . $id . '&msg=' . urlencode('Recommendation added.'));
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-heading">Add Recommendation</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="list.php">Bookings</a></li>
        <li class="breadcrumb-item"><a href="view.php?id=<?= $id ?>">Booking #<?= $id ?></a></li>
        <li class="breadcrumb-item active">Recommend</li>
    </ol></nav>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-header"><h5>Subject Officer Recommendation (Nirdesha) — Booking #<?= $id ?></h5></div>
    <div class="card-body">
        <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        <form method="POST">
            <div class="mb-4">
                <label class="form-label fw-600">Recommendation / Nirdesha <span class="text-danger">*</span></label>
                <textarea name="nirdesha" class="form-control" rows="6" required
                          placeholder="Enter your recommendation or remarks regarding this booking request…"><?= htmlspecialchars($_POST['nirdesha'] ?? '') ?></textarea>
                <div class="form-text">This recommendation will appear on the printed booking form and be visible to the Office Admin for their approval decision.</div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Recommendation</button>
                <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
