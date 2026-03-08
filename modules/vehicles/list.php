<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin','subject_officer']);

$pageTitle = 'Vehicles';
$isSuper = hasRole('super_admin');
$officeId = currentOfficeId();

$sql = $isSuper
    ? "SELECT v.*, o.name AS office_name, d.full_name AS driver_name
       FROM vehicles v
       JOIN offices o ON v.office_id = o.id
       LEFT JOIN drivers d ON v.driver_id = d.id
       ORDER BY v.reg_number"
    : "SELECT v.*, o.name AS office_name, d.full_name AS driver_name
       FROM vehicles v
       JOIN offices o ON v.office_id = o.id
       LEFT JOIN drivers d ON v.driver_id = d.id
       WHERE v.office_id = :oid
       ORDER BY v.reg_number";
$s = $pdo->prepare($sql);
if (!$isSuper) $s->bindValue(':oid', $officeId);
$s->execute();
$vehicles = $s->fetchAll();

$condCls = [
    'Good Running Condition' => 'condition-good',
    'Under Repairing'        => 'condition-repair',
    'Not Running Condition'  => 'condition-notrun',
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="page-heading">Vehicles</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Vehicles</li>
        </ol></nav>
    </div>
    <a href="add.php" class="btn btn-primary no-print"><i class="bi bi-plus-circle me-1"></i> Add Vehicle</a>
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
                        <th class="ps-3">Reg. No.</th>
                        <th>Type</th>
                        <th>Brand</th>
                        <th>Fuel</th>
                        <th>Seats</th>
                        <th>A/C</th>
                        <th>Condition</th>
                        <th>Driver</th>
                        <th>Office</th>
                        <th class="pe-3 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td class="ps-3 fw-600 vehicle-reg"><?= htmlspecialchars($v['reg_number']) ?></td>
                    <td><?= htmlspecialchars($v['type']) ?></td>
                    <td><?= htmlspecialchars($v['brand'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($v['fuel_type']) ?></td>
                    <td><?= $v['seating_capacity'] ?></td>
                    <td><?= $v['ac_available'] ? '<i class="bi bi-snow text-info"></i>' : '<i class="bi bi-x text-muted"></i>' ?></td>
                    <td>
                        <span class="condition-badge <?= $condCls[$v['condition_status']] ?? '' ?>">
                            <?php
                            $icons = ['Good Running Condition'=>'bi-check-circle-fill','Under Repairing'=>'bi-tools','Not Running Condition'=>'bi-x-circle-fill'];
                            $short = ['Good Running Condition'=>'Good','Under Repairing'=>'Repairing','Not Running Condition'=>'Not Running'];
                            ?>
                            <i class="bi <?= $icons[$v['condition_status']] ?? '' ?>"></i>
                            <?= $short[$v['condition_status']] ?? $v['condition_status'] ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($v['driver_name'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($v['office_name']) ?></td>
                    <td class="pe-3 no-print">
                        <a href="edit.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete(<?= $v['id'] ?>, '<?= htmlspecialchars(addslashes($v['reg_number'])) ?>')">
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
function confirmDelete(id, reg) {
    Swal.fire({
        title: 'Delete Vehicle?',
        html: `Remove <strong>${reg}</strong> from the fleet?`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, Delete'
    }).then(r => { if (r.isConfirmed) window.location.href = 'delete.php?id=' + id; });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
