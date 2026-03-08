<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
requireRole(['super_admin']);

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    // Never delete the currently logged-in user or last super admin
    $cur = currentUser();
    if ($cur['id'] != $id) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    }
}
header('Location: list.php?msg=' . urlencode('User deleted successfully.'));
exit;
