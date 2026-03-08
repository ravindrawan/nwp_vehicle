<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin','subject_officer']);

$id = (int)($_GET['id'] ?? 0);
$s  = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$s->execute([$id]);
$vehicle = $s->fetch();
if (!$vehicle) { header('Location: list.php'); exit; }

if (!hasRole('super_admin') && $vehicle['office_id'] != currentOfficeId()) {
    http_response_code(403); die('Access denied.');
}

$pageTitle = 'Edit Vehicle';
$errors = [];
$data = $vehicle;
$offices = $pdo->query("SELECT id, name FROM offices ORDER BY name")->fetchAll();
$isSuper = hasRole('super_admin');

if (!$isSuper) {
    $driversStmt = $pdo->prepare("SELECT id, full_name FROM drivers WHERE office_id = ? ORDER BY full_name");
    $driversStmt->execute([$vehicle['office_id']]);
    $drivers = $driversStmt->fetchAll();
} else {
    $drivers = $pdo->query("SELECT id, full_name FROM drivers ORDER BY full_name")->fetchAll();
}

$vehicleTypes = ['Car','Van','SUV','Jeep','Double Cab','Single Cab','Bus','Lorry','Crew Cab','Three-Wheeler'];
$fuelTypes    = ['Petrol','Diesel','EV','Hybrid'];
$conditions   = ['Good Running Condition','Under Repairing','Not Running Condition'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['reg_number']       = strtoupper(trim($_POST['reg_number'] ?? ''));
    $data['type']             = $_POST['type'] ?? $vehicle['type'];
    $data['brand']            = trim($_POST['brand'] ?? '');
    $data['fuel_type']        = $_POST['fuel_type'] ?? $vehicle['fuel_type'];
    $data['seating_capacity'] = (int)($_POST['seating_capacity'] ?? 4);
    $data['condition_status'] = $_POST['condition_status'] ?? $vehicle['condition_status'];
    $data['ac_available']     = isset($_POST['ac_available']) ? 1 : 0;
    $data['driver_id']        = $_POST['driver_id'] ?: null;
    $data['office_id']        = $isSuper ? (int)($_POST['office_id'] ?? $vehicle['office_id']) : $vehicle['office_id'];

    if ($data['reg_number'] === '')      $errors[] = 'Registration number is required.';
    if ($data['seating_capacity'] < 1)  $errors[] = 'Seating capacity must be at least 1.';

    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT id FROM vehicles WHERE reg_number = ? AND id != ?");
        $chk->execute([$data['reg_number'], $id]);
        if ($chk->fetch()) $errors[] = 'Another vehicle with this registration number exists.';
    }

    if (empty($errors)) {
        $pdo->prepare("UPDATE vehicles SET reg_number=?,type=?,brand=?,fuel_type=?,seating_capacity=?,
                       condition_status=?,ac_available=?,driver_id=?,office_id=? WHERE id=?")
            ->execute([$data['reg_number'],$data['type'],$data['brand'],$data['fuel_type'],
                       $data['seating_capacity'],$data['condition_status'],$data['ac_available'],
                       $data['driver_id'],$data['office_id'],$id]);
        header('Location: list.php?msg=' . urlencode('Vehicle updated successfully.'));
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-heading">Edit Vehicle</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="list.php">Vehicles</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol></nav>
</div>

<div class="row justify-content-center">
<div class="col-lg-9">
<div class="card">
    <div class="card-header"><h5>Edit: <?= htmlspecialchars($vehicle['reg_number']) ?></h5></div>
    <div class="card-body">
        <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Registration No. <span class="text-danger">*</span></label>
                    <input type="text" name="reg_number" class="form-control" required
                           value="<?= htmlspecialchars($data['reg_number']) ?>" style="text-transform:uppercase;">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vehicle Type</label>
                    <select name="type" class="form-select">
                        <?php foreach ($vehicleTypes as $t): ?>
                        <option value="<?= $t ?>" <?= $data['type']===$t?'selected':'' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($data['brand']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fuel Type</label>
                    <select name="fuel_type" class="form-select">
                        <?php foreach ($fuelTypes as $f): ?>
                        <option value="<?= $f ?>" <?= $data['fuel_type']===$f?'selected':'' ?>><?= $f ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Seating Capacity</label>
                    <input type="number" name="seating_capacity" class="form-control" min="1" max="60" value="<?= (int)$data['seating_capacity'] ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Running Condition</label>
                    <select name="condition_status" class="form-select">
                        <?php foreach ($conditions as $c): ?>
                        <option value="<?= $c ?>" <?= $data['condition_status']===$c?'selected':'' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Assigned Driver</label>
                    <select name="driver_id" class="form-select select2">
                        <option value="">— No Driver —</option>
                        <?php foreach ($drivers as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $data['driver_id']==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Owning Office</label>
                    <?php if ($isSuper): ?>
                    <select name="office_id" class="form-select select2">
                        <?php foreach ($offices as $o): ?>
                        <option value="<?= $o['id'] ?>" <?= $data['office_id']==$o['id']?'selected':'' ?>><?= htmlspecialchars($o['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <?php $myO = array_filter($offices, fn($o) => $o['id'] == $data['office_id']); $myO = reset($myO); ?>
                    <input type="hidden" name="office_id" value="<?= $data['office_id'] ?>">
                    <input type="text" class="form-control" value="<?= htmlspecialchars($myO['name'] ?? '') ?>" readonly>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ac_available" id="acAvail" <?= $data['ac_available']?'checked':'' ?>>
                        <label class="form-check-label" for="acAvail">Air Conditioning (A/C) Available</label>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Vehicle</button>
                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
