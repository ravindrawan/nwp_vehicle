<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin']);

$pageTitle = 'Offices';

$offices = $pdo->query("SELECT * FROM offices ORDER BY name")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="page-heading">Offices</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Offices</li>
        </ol></nav>
    </div>
    <a href="add.php" class="btn btn-primary no-print">
        <i class="bi bi-plus-circle me-1"></i> Add Office
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success auto-dismiss d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill"></i>
        <?= htmlspecialchars($_GET['msg']) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table data-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Office Name</th>
                        <th>Telephone</th>
                        <th>Email</th>
                        <th>Fax</th>
                        <th class="pe-3 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($offices as $i => $o): ?>
                <tr>
                    <td class="ps-3"><?= $i+1 ?></td>
                    <td class="fw-600"><?= htmlspecialchars($o['name']) ?></td>
                    <td><?= htmlspecialchars($o['telephone'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($o['email'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($o['fax'] ?: '—') ?></td>
                    <td class="pe-3 no-print">
                        <a href="edit.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete(<?= $o['id'] ?>, '<?= htmlspecialchars(addslashes($o['name'])) ?>')">
                            <i class="bi bi-trash"></i>
                        </button>
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
        title: 'Delete Office?',
        html: `Are you sure you want to delete <strong>${name}</strong>?<br><small class="text-danger">This will also delete all related vehicles and drivers.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            window.location.href = 'delete.php?id=' + id;
        }
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
