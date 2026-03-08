<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin','subject_officer']);

$pageTitle = 'Drivers';
$isSuper = hasRole('super_admin');
$officeId = currentOfficeId();

$sql = $isSuper
    ? "SELECT d.*, o.name AS office_name FROM drivers d JOIN offices o ON d.office_id=o.id ORDER BY d.full_name"
    : "SELECT d.*, o.name AS office_name FROM drivers d JOIN offices o ON d.office_id=o.id WHERE d.office_id=:oid ORDER BY d.full_name";
$s = $pdo->prepare($sql);
if (!$isSuper) $s->bindValue(':oid', $officeId);
$s->execute();
$drivers = $s->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="page-heading">Drivers</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Drivers</li>
        </ol></nav>
    </div>
    <a href="add.php" class="btn btn-primary no-print">
        <i class="bi bi-person-plus me-1"></i> Add Driver
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success auto-dismiss"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table data-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Full Name</th>
                        <th>NIC</th>
                        <th>License No.</th>
                        <th>Contact</th>
                        <th>Office</th>
                        <th class="pe-3 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($drivers as $i => $d): ?>
                <tr>
                    <td class="ps-3"><?= $i+1 ?></td>
                    <td class="fw-600"><?= htmlspecialchars($d['full_name']) ?></td>
                    <td><?= htmlspecialchars($d['nic']) ?></td>
                    <td><?= htmlspecialchars($d['license_number']) ?></td>
                    <td><?= htmlspecialchars($d['contact_number'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($d['office_name']) ?></td>
                    <td class="pe-3 no-print">
                        <a href="edit.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['full_name'])) ?>')">
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
        title: 'Delete Driver?',
        html: `Remove <strong>${name}</strong> from the system?`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, Delete'
    }).then(r => { if (r.isConfirmed) window.location.href = 'delete.php?id=' + id; });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
