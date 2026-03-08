<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin']);

$pageTitle = 'User Accounts';
$users = $pdo->query(
    "SELECT u.*, o.name AS office_name
     FROM users u
     LEFT JOIN offices o ON u.office_id = o.id
     ORDER BY u.full_name"
)->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="page-heading">User Accounts</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Users</li>
        </ol></nav>
    </div>
    <a href="add.php" class="btn btn-primary no-print">
        <i class="bi bi-person-plus me-1"></i> Add User
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success auto-dismiss d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($_GET['msg']) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table data-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Office</th>
                        <th>Status</th>
                        <th class="pe-3 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $roleLabels = [
                    'super_admin'     => ['Super Admin',       'bg-primary'],
                    'office_admin'    => ['Office Admin',      'bg-info text-dark'],
                    'subject_officer' => ['Subject Officer',   'bg-secondary'],
                    'general_user'    => ['General User',      'bg-light text-dark'],
                ];
                foreach ($users as $i => $u):
                    [$rl, $rc] = $roleLabels[$u['role']] ?? [$u['role'], 'bg-secondary'];
                ?>
                <tr>
                    <td class="ps-3"><?= $i+1 ?></td>
                    <td class="fw-600"><?= htmlspecialchars($u['full_name']) ?></td>
                    <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                    <td><span class="badge <?= $rc ?>"><?= $rl ?></span></td>
                    <td><?= htmlspecialchars($u['office_name'] ?? '(All Offices)') ?></td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="pe-3 no-print">
                        <a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php if ($u['role'] !== 'super_admin'): ?>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['full_name'])) ?>')">
                            <i class="bi bi-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete User?',
        html: `Are you sure you want to delete <strong>${name}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Yes, Delete'
    }).then(r => { if (r.isConfirmed) window.location.href = 'delete.php?id=' + id; });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
