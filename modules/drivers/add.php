<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin','subject_officer']);

$pageTitle = 'Add Driver';
$errors = [];
$data = ['full_name'=>'','nic'=>'','license_number'=>'','contact_number'=>'','office_id'=>''];
$offices = $pdo->query("SELECT id, name FROM offices ORDER BY name")->fetchAll();

// Pre-select own office for subject officers
if (!hasRole('super_admin')) {
    $data['office_id'] = currentOfficeId();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['full_name']      = trim($_POST['full_name'] ?? '');
    $data['nic']            = trim($_POST['nic'] ?? '');
    $data['license_number'] = trim($_POST['license_number'] ?? '');
    $data['contact_number'] = trim($_POST['contact_number'] ?? '');
    $data['office_id']      = (int)($_POST['office_id'] ?? 0);

    if ($data['full_name'] === '')      $errors[] = 'Full name is required.';
    if ($data['nic'] === '')            $errors[] = 'NIC is required.';
    if ($data['license_number'] === '') $errors[] = 'License number is required.';
    if (!$data['office_id'])            $errors[] = 'Please select an office.';

    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT id FROM drivers WHERE nic = ?");
        $chk->execute([$data['nic']]);
        if ($chk->fetch()) $errors[] = 'A driver with this NIC already exists.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO drivers (full_name,nic,license_number,contact_number,office_id) VALUES (?,?,?,?,?)");
        $stmt->execute([$data['full_name'],$data['nic'],$data['license_number'],$data['contact_number'],$data['office_id']]);
        header('Location: list.php?msg=' . urlencode('Driver added successfully.'));
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-heading">Add Driver</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="list.php">Drivers</a></li>
        <li class="breadcrumb-item active">Add</li>
    </ol></nav>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-header"><h5>Driver Information</h5></div>
    <div class="card-body">
        <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        <form method="POST">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($data['full_name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">NIC Number <span class="text-danger">*</span></label>
                    <input type="text" name="nic" class="form-control" required value="<?= htmlspecialchars($data['nic']) ?>" placeholder="XXXXXXXXXV or XXXXXXXXXXXX">
                </div>
                <div class="col-md-6">
                    <label class="form-label">License Number <span class="text-danger">*</span></label>
                    <input type="text" name="license_number" class="form-control" required value="<?= htmlspecialchars($data['license_number']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($data['contact_number']) ?>" placeholder="07XXXXXXXX">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Assigned Office <span class="text-danger">*</span></label>
                    <?php if (hasRole('super_admin')): ?>
                    <select name="office_id" class="form-select select2" required>
                        <option value="">Select Office</option>
                        <?php foreach ($offices as $o): ?>
                        <option value="<?= $o['id'] ?>" <?= $data['office_id']==$o['id']?'selected':'' ?>>
                            <?= htmlspecialchars($o['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <?php
                    $myOffice = array_filter($offices, fn($o) => $o['id'] == $data['office_id']);
                    $myOffice = reset($myOffice);
                    ?>
                    <input type="hidden" name="office_id" value="<?= $data['office_id'] ?>">
                    <input type="text" class="form-control" value="<?= htmlspecialchars($myOffice['name'] ?? '') ?>" readonly>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Driver</button>
                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
