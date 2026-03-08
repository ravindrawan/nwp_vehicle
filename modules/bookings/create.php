<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

$pageTitle = 'Book a Vehicle';
$errors = [];

// Load vehicle
$vehicleId = (int)($_GET['vehicle_id'] ?? 0);
if (!$vehicleId) { header('Location: ' . BASE_URL . 'search.php'); exit; }

$vStmt = $pdo->prepare(
    "SELECT v.*, o.name AS office_name, d.full_name AS driver_name
     FROM vehicles v
     JOIN offices o ON v.office_id = o.id
     LEFT JOIN drivers d ON v.driver_id = d.id
     WHERE v.id = ?"
);
$vStmt->execute([$vehicleId]);
$vehicle = $vStmt->fetch();
if (!$vehicle) { header('Location: ' . BASE_URL . 'search.php'); exit; }

$data = [
    'booker_name'    => currentUser()['full_name'],
    'journey_date'   => $_GET['journey_date'] ?? '',
    'start_location' => '',
    'destinations'   => '',
    'start_time'     => '',
    'return_time'    => '',
    'distance_km'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['booker_name']    = trim($_POST['booker_name'] ?? '');
    $data['journey_date']   = $_POST['journey_date'] ?? '';
    $data['start_location'] = trim($_POST['start_location'] ?? '');
    $data['destinations']   = trim($_POST['destinations'] ?? '');
    $data['start_time']     = $_POST['start_time'] ?? '';
    $data['return_time']    = $_POST['return_time'] ?? '';
    $data['distance_km']    = trim($_POST['distance_km'] ?? '');

    if ($data['booker_name'] === '')    $errors[] = 'Booker name is required.';
    if ($data['journey_date'] === '')   $errors[] = 'Journey date is required.';
    if ($data['start_location'] === '') $errors[] = 'Start location is required.';
    if ($data['destinations'] === '')   $errors[] = 'Destination(s) are required.';
    if ($data['start_time'] === '')     $errors[] = 'Start time is required.';
    if ($data['return_time'] === '')    $errors[] = 'Return time is required.';

    if (empty($errors) && $data['journey_date'] < date('Y-m-d')) {
        $errors[] = 'Journey date cannot be in the past.';
    }

    // ── Double-booking check ─────────────────────────────────
    if (empty($errors)) {
        $dblChk = $pdo->prepare(
            "SELECT id FROM bookings
             WHERE vehicle_id = ? AND journey_date = ? AND status IN ('pending','approved')"
        );
        $dblChk->execute([$vehicleId, $data['journey_date']]);
        if ($dblChk->fetch()) {
            $errors[] = 'This vehicle already has a pending or approved booking for the selected date. Please choose another date or vehicle.';
        }
    }

    if (empty($errors)) {
        $pdo->prepare(
            "INSERT INTO bookings (vehicle_id, booker_name, booked_by_user_id, journey_date,
             start_location, destinations, start_time, return_time, distance_km, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
        )->execute([
            $vehicleId,
            $data['booker_name'],
            currentUser()['id'],
            $data['journey_date'],
            $data['start_location'],
            $data['destinations'],
            $data['start_time'],
            $data['return_time'],
            $data['distance_km'] ?: null,
        ]);

        header('Location: my_bookings.php?msg=' . urlencode('Booking submitted successfully. Awaiting approval.'));
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-heading">Book a Vehicle</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>search.php">Search</a></li>
        <li class="breadcrumb-item active">Create Booking</li>
    </ol></nav>
</div>

<!-- Vehicle Summary Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div style="width:52px;height:52px;background:#eff6ff;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-car-front-fill text-primary" style="font-size:1.5rem;"></i>
            </div>
            <div>
                <div class="vehicle-reg fs-5"><?= htmlspecialchars($vehicle['reg_number']) ?></div>
                <div class="text-muted-sm">
                    <?= htmlspecialchars($vehicle['type']) ?> &middot;
                    <?= htmlspecialchars($vehicle['brand'] ?: 'Unknown Brand') ?> &middot;
                    <?= htmlspecialchars($vehicle['fuel_type']) ?> &middot;
                    <?= $vehicle['seating_capacity'] ?> seats
                    <?= $vehicle['ac_available'] ? ' &middot; <i class="bi bi-snow text-info"></i> A/C' : '' ?>
                </div>
                <div class="text-muted-sm"><i class="bi bi-building me-1"></i><?= htmlspecialchars($vehicle['office_name']) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card">
    <div class="card-header"><h5>Booking Details</h5></div>
    <div class="card-body">

        <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Booker's Name <span class="text-danger">*</span></label>
                    <input type="text" name="booker_name" class="form-control" required
                           value="<?= htmlspecialchars($data['booker_name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date of Journey <span class="text-danger">*</span></label>
                    <input type="date" name="journey_date" class="form-control" required
                           value="<?= htmlspecialchars($data['journey_date']) ?>"
                           min="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Starting Location <span class="text-danger">*</span></label>
                    <input type="text" name="start_location" class="form-control" required
                           value="<?= htmlspecialchars($data['start_location']) ?>" placeholder="e.g. Kurunegala">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Destination(s) <span class="text-danger">*</span></label>
                    <input type="text" name="destinations" class="form-control" required
                           value="<?= htmlspecialchars($data['destinations']) ?>" placeholder="e.g. Colombo, Kandy">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expected Start Time <span class="text-danger">*</span></label>
                    <input type="time" name="start_time" class="form-control" required
                           value="<?= htmlspecialchars($data['start_time']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expected Return Time <span class="text-danger">*</span></label>
                    <input type="time" name="return_time" class="form-control" required
                           value="<?= htmlspecialchars($data['return_time']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Approx. Distance (km)</label>
                    <input type="number" name="distance_km" class="form-control" min="0" step="0.1"
                           value="<?= htmlspecialchars($data['distance_km']) ?>" placeholder="e.g. 120">
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-calendar-plus me-1"></i> Submit Booking Request
                </button>
                <a href="<?= BASE_URL ?>search.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
