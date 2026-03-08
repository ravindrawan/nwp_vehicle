<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin']);

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $pdo->prepare("DELETE FROM offices WHERE id = ?")->execute([$id]);
}
header('Location: list.php?msg=' . urlencode('Office deleted successfully.'));
exit;
