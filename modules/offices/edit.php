<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin']);

$id = (int)($_GET['id'] ?? 0);
$office = $pdo->prepare("SELECT * FROM offices WHERE id = ?");
$office->execute([$id]);
$office = $office->fetch();

if (!$office) {
    header('Location: list.php?msg=' . urlencode('Office not found.'));
    exit;
}

$pageTitle = 'Edit Office';
$errors = [];
$data = $office;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['name']      = trim($_POST['name'] ?? '');
    $data['telephone'] = trim($_POST['telephone'] ?? '');
    $data['email']     = trim($_POST['email'] ?? '');
    $data['fax']       = trim($_POST['fax'] ?? '');

    if ($data['name'] === '') $errors[] = 'Office name is required.';
    if ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE offices SET name=?, telephone=?, email=?, fax=? WHERE id=?");
        $stmt->execute([$data['name'], $data['telephone'], $data['email'], $data['fax'], $id]);
        header('Location: list.php?msg=' . urlencode('Office updated successfully.'));
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-heading">Edit Office</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="list.php">Offices</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol></nav>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-header"><h5>Edit Office: <?= htmlspecialchars($office['name']) ?></h5></div>
    <div class="card-body">

        <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Office Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required
                       value="<?= htmlspecialchars($data['name']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Telephone Number</label>
                <input type="text" name="telephone" class="form-control"
                       value="<?= htmlspecialchars($data['telephone']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($data['email']) ?>">
            </div>
            <div class="mb-4">
                <label class="form-label">Fax Number</label>
                <input type="text" name="fax" class="form-control"
                       value="<?= htmlspecialchars($data['fax']) ?>">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Office</button>
                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
