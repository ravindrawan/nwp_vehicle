<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { header('Location: list.php'); exit; }

$pageTitle = 'Edit User';
$errors = [];
$data = $user;
$offices = $pdo->query("SELECT id, name FROM offices ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['full_name']  = trim($_POST['full_name'] ?? '');
    $data['username']   = trim($_POST['username'] ?? '');
    $data['role']       = $_POST['role'] ?? $user['role'];
    $data['office_id']  = $_POST['office_id'] ?: null;
    $data['is_active']  = isset($_POST['is_active']) ? 1 : 0;
    $password           = $_POST['password'] ?? '';
    $confirm            = $_POST['confirm_password'] ?? '';

    if ($data['full_name'] === '') $errors[] = 'Full name is required.';
    if ($data['username'] === '')  $errors[] = 'Username is required.';
    if ($password !== '' && $password !== $confirm) $errors[] = 'Passwords do not match.';
    if ($password !== '' && strlen($password) < 6)  $errors[] = 'Password must be at least 6 characters.';

    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $chk->execute([$data['username'], $id]);
        if ($chk->fetch()) $errors[] = 'Username already taken.';
    }

    if (empty($errors)) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET full_name=?,username=?,password=?,role=?,office_id=?,is_active=? WHERE id=?")
                ->execute([$data['full_name'],$data['username'],$hash,$data['role'],$data['office_id'],$data['is_active'],$id]);
        } else {
            $pdo->prepare("UPDATE users SET full_name=?,username=?,role=?,office_id=?,is_active=? WHERE id=?")
                ->execute([$data['full_name'],$data['username'],$data['role'],$data['office_id'],$data['is_active'],$id]);
        }
        header('Location: list.php?msg=' . urlencode('User updated successfully.'));
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-heading">Edit User</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="list.php">Users</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol></nav>
</div>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card">
    <div class="card-header"><h5>Edit: <?= htmlspecialchars($user['full_name']) ?></h5></div>
    <div class="card-body">
        <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($data['full_name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($data['username']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                    <input type="password" name="password" class="form-control" minlength="6">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="super_admin"     <?= $data['role']==='super_admin'?'selected':'' ?>>Super Admin</option>
                        <option value="office_admin"    <?= $data['role']==='office_admin'?'selected':'' ?>>Office Admin</option>
                        <option value="subject_officer" <?= $data['role']==='subject_officer'?'selected':'' ?>>Subject Officer</option>
                        <option value="general_user"    <?= $data['role']==='general_user'?'selected':'' ?>>General User</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Assigned Office</label>
                    <select name="office_id" class="form-select select2">
                        <option value="">— All Offices —</option>
                        <?php foreach ($offices as $o): ?>
                        <option value="<?= $o['id'] ?>" <?= $data['office_id']==$o['id']?'selected':'' ?>>
                            <?= htmlspecialchars($o['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= $data['is_active']?'checked':'' ?>>
                        <label class="form-check-label" for="isActive">Account Active</label>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update User</button>
                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
