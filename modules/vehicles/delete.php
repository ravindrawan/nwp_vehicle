<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin','subject_officer']);
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) $pdo->prepare("DELETE FROM vehicles WHERE id = ?")->execute([$id]);
header('Location: list.php?msg=' . urlencode('Vehicle deleted successfully.'));
exit;
