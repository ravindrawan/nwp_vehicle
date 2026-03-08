<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
requireLogin();

$pageTitle = 'Search Vehicles';

// Get filter inputs
$filterType      = $_GET['type'] ?? '';
$filterOffice    = (int)($_GET['office_id'] ?? 0);
$filterCondition = $_GET['condition'] ?? '';
$filterDate      = $_GET['journey_date'] ?? '';
$filterAC        = isset($_GET['ac']) ? 1 : null;

$offices = $pdo->query("SELECT id, name FROM offices ORDER BY name")->fetchAll();
$vehicleTypes = ['Car','Van','SUV','Jeep','Double Cab','Single Cab','Bus','Lorry','Crew Cab','Three-Wheeler'];
$conditions   = ['Good Running Condition','Under Repairing','Not Running Condition'];

// ── Build query ──────────────────────────────────────────────
$where = ["1=1"];
$params = [];

if ($filterType) {
    $where[] = "v.type = :type";
    $params[':type'] = $filterType;
}
if ($filterOffice > 0) {
    $where[] = "v.office_id = :oid";
    $params[':oid'] = $filterOffice;
}
if ($filterCondition) {
    $where[] = "v.condition_status = :cond";
    $params[':cond'] = $filterCondition;
}
if ($filterAC !== null) {
    $where[] = "v.ac_available = :ac";
    $params[':ac'] = 1;
}
if ($filterDate) {
    // Exclude vehicles that already have an APPROVED booking on the selected date
    $where[] = "v.id NOT IN (
        SELECT vehicle_id FROM bookings
        WHERE journey_date = :jdate AND status = 'approved'
    )";
    $params[':jdate'] = $filterDate;
}

$sql = "SELECT v.*, o.name AS office_name, d.full_name AS driver_name
        FROM vehicles v
        JOIN offices o ON v.office_id = o.id
        LEFT JOIN drivers d ON v.driver_id = d.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY v.reg_number";

$s = $pdo->prepare($sql);
$s->execute($params);
$vehicles = $s->fetchAll();

$condCls = [
    'Good Running Condition' => 'condition-good',
    'Under Repairing'        => 'condition-repair',
    'Not Running Condition'  => 'condition-notrun',
];

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1 class="page-heading">Search Vehicles</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Search Vehicles</li>
    </ol></nav>
</div>

<!-- ── Filter Form ─────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-funnel-fill text-primary"></i><h6>Filter Options</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label">Vehicle Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($vehicleTypes as $t): ?>
                    <option value="<?= $t ?>" <?= $filterType===$t?'selected':'' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Office</label>
                <select name="office_id" class="form-select">
                    <option value="0">All Offices</option>
                    <?php foreach ($offices as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= $filterOffice==$o['id']?'selected':'' ?>><?= htmlspecialchars($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Condition</label>
                <select name="condition" class="form-select">
                    <option value="">Any</option>
                    <option value="Good Running Condition" <?= $filterCondition==='Good Running Condition'?'selected':'' ?>>Good</option>
                    <option value="Under Repairing"        <?= $filterCondition==='Under Repairing'?'selected':'' ?>>Repairing</option>
                    <option value="Not Running Condition"  <?= $filterCondition==='Not Running Condition'?'selected':'' ?>>Not Running</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Journey Date</label>
                <input type="date" name="journey_date" class="form-control"
                       value="<?= htmlspecialchars($filterDate) ?>"
                       min="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-6 col-md-1">
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="ac" id="filterAC" <?= isset($_GET['ac'])?'checked':'' ?>>
                    <label class="form-check-label" for="filterAC">A/C</label>
                </div>
            </div>
            <div class="col-6 col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

<!-- ── Results ─────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="mb-0 text-muted">
        <?= count($vehicles) ?> vehicle<?= count($vehicles) !== 1 ? 's' : '' ?> found
        <?= $filterDate ? ' available on <strong>' . date('d M Y', strtotime($filterDate)) . '</strong>' : '' ?>
    </h6>
    <?php if (!empty($_GET)): ?>
    <a href="search.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x me-1"></i>Clear Filters</a>
    <?php endif; ?>
</div>

<?php if (empty($vehicles)): ?>
<div class="text-center py-5">
    <i class="bi bi-car-front" style="font-size:3rem; color:#cbd5e1;"></i>
    <p class="mt-3 text-muted">No vehicles match your search criteria.</p>
    <a href="search.php" class="btn btn-outline-primary mt-1">Clear Filters</a>
</div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($vehicles as $v): ?>
    <div class="col-md-6 col-lg-4">
        <div class="vehicle-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="vehicle-reg"><?= htmlspecialchars($v['reg_number']) ?></div>
                    <div class="vehicle-meta"><?= htmlspecialchars($v['type']) ?> &mdash; <?= htmlspecialchars($v['brand'] ?: 'Unknown Brand') ?></div>
                </div>
                <span class="condition-badge <?= $condCls[$v['condition_status']] ?? '' ?>">
                    <?= $v['condition_status'] === 'Good Running Condition' ? 'Available' : htmlspecialchars($v['condition_status']) ?>
                </span>
            </div>

            <div class="row g-2 text-muted-sm my-2">
                <div class="col-6 d-flex align-items-center gap-1">
                    <i class="bi bi-fuel-pump"></i> <?= htmlspecialchars($v['fuel_type']) ?>
                </div>
                <div class="col-6 d-flex align-items-center gap-1">
                    <i class="bi bi-people"></i> <?= $v['seating_capacity'] ?> Seats
                </div>
                <div class="col-6 d-flex align-items-center gap-1">
                    <?php if ($v['ac_available']): ?>
                    <i class="bi bi-snow text-info"></i> A/C Available
                    <?php else: ?>
                    <i class="bi bi-x-circle text-muted"></i> No A/C
                    <?php endif; ?>
                </div>
                <div class="col-6 d-flex align-items-center gap-1">
                    <i class="bi bi-person-badge"></i>
                    <?= $v['driver_name'] ? htmlspecialchars($v['driver_name']) : '<em>No Driver</em>' ?>
                </div>
                <div class="col-12 d-flex align-items-center gap-1">
                    <i class="bi bi-building"></i> <?= htmlspecialchars($v['office_name']) ?>
                </div>
            </div>

            <?php if ($v['condition_status'] === 'Good Running Condition'): ?>
            <div class="mt-3">
                <?php
                $bookUrl = BASE_URL . 'modules/bookings/create.php?vehicle_id=' . $v['id'];
                if ($filterDate) $bookUrl .= '&journey_date=' . urlencode($filterDate);
                ?>
                <a href="<?= $bookUrl ?>" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-calendar-plus me-1"></i> Book This Vehicle
                </a>
            </div>
            <?php else: ?>
            <div class="mt-3">
                <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                    <i class="bi bi-slash-circle me-1"></i> Not Available for Booking
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
