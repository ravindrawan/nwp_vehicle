<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT b.*,
            v.reg_number, v.type AS vtype, v.brand, v.fuel_type, v.seating_capacity, v.ac_available,
            o.name AS office_name, o.telephone AS office_tel, o.email AS office_email,
            d.full_name AS driver_name, d.license_number, d.contact_number AS driver_contact,
            u.full_name AS booked_by_name,
            app.full_name AS approved_by_name,
            nrd.full_name AS nirdesha_by_name
     FROM bookings b
     JOIN vehicles v ON b.vehicle_id = v.id
     JOIN offices o ON v.office_id = o.id
     LEFT JOIN drivers d ON v.driver_id = d.id
     JOIN users u ON b.booked_by_user_id = u.id
     LEFT JOIN users app ON b.approved_by = app.id
     LEFT JOIN users nrd ON b.nirdesha_by = nrd.id
     WHERE b.id = ?"
);
$stmt->execute([$id]);
$b = $stmt->fetch();

if (!$b) {
    die('Booking not found.');
}

// Access control
if (hasRole('general_user') && $b['booked_by_user_id'] != currentUser()['id']) {
    http_response_code(403); die('Access denied.');
}

$statusLabel = ucfirst($b['status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Sheet #<?= $id ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: #1e293b; }

        .print-wrapper {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,.1);
        }

        .no-print { margin-bottom: 20px; }

        /* ── Document header ── */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 16px;
            border-bottom: 2px solid #1a56db;
            margin-bottom: 20px;
        }

        .org-title { font-size: 1rem; font-weight: 700; color: #1a56db; }
        .org-sub   { font-size: .8rem; color: #64748b; }

        .doc-title {
            font-size: 1.2rem;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #1e293b;
            margin: 16px 0 4px;
        }

        .doc-subtitle {
            text-align: center;
            font-size: .8rem;
            color: #64748b;
            margin-bottom: 20px;
        }

        .booking-no {
            font-size: .85rem;
            text-align: right;
        }

        /* ── Sections ── */
        .section-title {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            padding: 6px 10px;
            background: #f8fafc;
            border-left: 3px solid #1a56db;
            margin: 18px 0 10px;
        }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .info-row  { display: flex; gap: 8px; }
        .info-label { font-weight: 600; font-size: .82rem; min-width: 150px; color: #374151; }
        .info-value { font-size: .85rem; }

        .nirdesha-box {
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 14px;
            min-height: 80px;
            font-size: .875rem;
            color: #374151;
        }

        /* ── Signature section ── */
        .sig-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        .sig-box {
            text-align: center;
        }

        .sig-line {
            border-bottom: 1px solid #374151;
            height: 50px;
            margin-bottom: 6px;
        }

        .sig-label { font-size: .78rem; color: #64748b; }
        .sig-name  { font-size: .82rem; font-weight: 600; }

        /* ── Status banner ── */
        .status-banner {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 700;
        }

        .status-pending  { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }

        .footer-note {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: .72rem;
            color: #94a3b8;
            text-align: center;
        }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .print-wrapper {
                box-shadow: none;
                margin: 0;
                padding: 20px;
                border-radius: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="print-wrapper">
    <!-- Print button (hidden when printing) -->
    <div class="no-print d-flex justify-content-between align-items-center">
        <a href="view.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print / Save PDF
        </button>
    </div>

    <!-- Document Header -->
    <div class="doc-header">
        <div>
            <div class="org-title">North Western Provincial Council</div>
            <div class="org-sub">NWPC Vehicle Fleet Management System</div>
            <?php if ($b['office_name']): ?>
            <div class="org-sub"><?= htmlspecialchars($b['office_name']) ?></div>
            <?php endif; ?>
        </div>
        <div class="booking-no">
            <div><strong>Booking No:</strong> #<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></div>
            <div><strong>Submitted:</strong> <?= date('d M Y', strtotime($b['created_at'])) ?></div>
            <div class="mt-1">
                <span class="status-banner status-<?= $b['status'] ?>"><?= $statusLabel ?></span>
            </div>
        </div>
    </div>

    <div class="doc-title">Vehicle Booking Request Form</div>
    <div class="doc-subtitle">Vehicle Fleet &amp; Booking Management System — <?= APP_NAME ?></div>

    <!-- Vehicle Details -->
    <div class="section-title">Vehicle Details</div>
    <div class="info-grid">
        <div class="info-row"><span class="info-label">Registration No.</span><span class="info-value fw-bold"><?= htmlspecialchars($b['reg_number']) ?></span></div>
        <div class="info-row"><span class="info-label">Vehicle Type</span><span class="info-value"><?= htmlspecialchars($b['vtype']) ?></span></div>
        <div class="info-row"><span class="info-label">Brand</span><span class="info-value"><?= htmlspecialchars($b['brand'] ?: '—') ?></span></div>
        <div class="info-row"><span class="info-label">Fuel Type</span><span class="info-value"><?= htmlspecialchars($b['fuel_type']) ?></span></div>
        <div class="info-row"><span class="info-label">Seating Capacity</span><span class="info-value"><?= $b['seating_capacity'] ?> persons</span></div>
        <div class="info-row"><span class="info-label">Air Conditioning</span><span class="info-value"><?= $b['ac_available'] ? 'Yes' : 'No' ?></span></div>
        <div class="info-row"><span class="info-label">Assigned Driver</span><span class="info-value"><?= htmlspecialchars($b['driver_name'] ?: '—') ?></span></div>
        <div class="info-row"><span class="info-label">Driver License No.</span><span class="info-value"><?= htmlspecialchars($b['license_number'] ?? '—') ?></span></div>
    </div>

    <!-- Journey Details -->
    <div class="section-title">Journey Details</div>
    <div class="info-grid">
        <div class="info-row"><span class="info-label">Booker's Name</span><span class="info-value fw-bold"><?= htmlspecialchars($b['booker_name']) ?></span></div>
        <div class="info-row"><span class="info-label">Date of Journey</span><span class="info-value fw-bold"><?= date('l, d F Y', strtotime($b['journey_date'])) ?></span></div>
        <div class="info-row"><span class="info-label">Starting Location</span><span class="info-value"><?= htmlspecialchars($b['start_location']) ?></span></div>
        <div class="info-row"><span class="info-label">Destination(s)</span><span class="info-value"><?= htmlspecialchars($b['destinations']) ?></span></div>
        <div class="info-row"><span class="info-label">Start Time</span><span class="info-value"><?= date('H:i', strtotime($b['start_time'])) ?></span></div>
        <div class="info-row"><span class="info-label">Expected Return</span><span class="info-value"><?= date('H:i', strtotime($b['return_time'])) ?></span></div>
        <div class="info-row"><span class="info-label">Approx. Distance</span><span class="info-value"><?= $b['distance_km'] ? $b['distance_km'] . ' km' : '—' ?></span></div>
    </div>

    <!-- Subject Officer Recommendation -->
    <div class="section-title">Subject Officer Recommendation (නිර්දේශ / Nirdesha)</div>
    <div class="nirdesha-box">
        <?php if ($b['nirdesha']): ?>
            <?= nl2br(htmlspecialchars($b['nirdesha'])) ?>
            <div style="margin-top:8px;font-size:.75rem;color:#64748b;">
                — <?= htmlspecialchars($b['nirdesha_by_name'] ?? '') ?>
                <?= $b['nirdesha_at'] ? ' | ' . date('d M Y H:i', strtotime($b['nirdesha_at'])) : '' ?>
            </div>
        <?php else: ?>
            <span style="color:#94a3b8;font-style:italic;">No recommendation has been added.</span>
        <?php endif; ?>
    </div>

    <!-- Approval Info -->
    <?php if ($b['status'] !== 'pending'): ?>
    <div class="section-title">Approval / Rejection Decision</div>
    <div class="info-grid">
        <div class="info-row">
            <span class="info-label">Decision</span>
            <span class="info-value fw-bold <?= $b['status'] === 'approved' ? 'text-success' : 'text-danger' ?>">
                <?= $statusLabel ?>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Decided By</span>
            <span class="info-value"><?= htmlspecialchars($b['approved_by_name'] ?? '—') ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Decision Date</span>
            <span class="info-value"><?= $b['approved_at'] ? date('d M Y H:i', strtotime($b['approved_at'])) : '—' ?></span>
        </div>
        <?php if ($b['rejection_reason']): ?>
        <div class="info-row" style="grid-column: span 2;">
            <span class="info-label">Rejection Reason</span>
            <span class="info-value text-danger"><?= htmlspecialchars($b['rejection_reason']) ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Signature Area -->
    <div class="sig-grid">
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name"><?= htmlspecialchars($b['booker_name']) ?></div>
            <div class="sig-label">Booker's Signature &amp; Date</div>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name"><?= htmlspecialchars($b['nirdesha_by_name'] ?? 'Subject Officer') ?></div>
            <div class="sig-label">Subject Officer Signature &amp; Date</div>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name"><?= htmlspecialchars($b['approved_by_name'] ?? 'Office Admin') ?></div>
            <div class="sig-label">Head of Institute Signature &amp; Date</div>
        </div>
    </div>

    <div class="footer-note">
        This document was generated by the NWPC Vehicle Fleet Management System on <?= date('d M Y H:i') ?>.
        Booking #<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?>
    </div>
</div>

</body>
</html>
