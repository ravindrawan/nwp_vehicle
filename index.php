<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';

// Already logged in → redirect to dashboard
if (!empty($_SESSION['user'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id'        => $user['id'],
                'full_name' => $user['full_name'],
                'username'  => $user['username'],
                'role'      => $user['role'],
                'office_id' => $user['office_id'],
            ];
            header('Location: ' . BASE_URL . 'dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        .login-input-group .input-group-text {
            background: #f8fafc;
            border-right: 0;
            border-radius: 8px 0 0 8px;
            color: #64748b;
        }
        .login-input-group .form-control {
            border-left: 0;
            border-radius: 0 8px 8px 0;
        }
        .login-input-group .form-control:focus {
            border-color: #d1d5db;
            box-shadow: none;
        }
        .login-input-group:focus-within .input-group-text,
        .login-input-group:focus-within .form-control {
            border-color: #1a56db;
        }
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <i class="bi bi-truck-front-fill"></i>
        </div>
        <h4 class="text-center fw-700 mb-1" style="font-weight:700;">NWPC Fleet System</h4>
        <p class="text-center text-muted mb-4" style="font-size:.85rem;">North Western Provincial Council<br>Vehicle Fleet &amp; Booking Management</p>

        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group login-input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" id="username"
                           class="form-control" placeholder="Enter your username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group login-input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password"
                           class="form-control" placeholder="Enter your password" required>
                    <button type="button" class="btn btn-outline-secondary border-start-0"
                            onclick="togglePass()" style="border-radius:0 8px 8px 0; border-left:0;">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <p class="text-center text-muted mt-4 mb-0" style="font-size:.75rem;">
            &copy; <?= APP_YEAR ?> North Western Provincial Council
        </p>
    </div>
</div>

<script>
function togglePass() {
    const p = document.getElementById('password');
    const i = document.getElementById('eyeIcon');
    if (p.type === 'password') {
        p.type = 'text';
        i.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        p.type = 'password';
        i.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>
</body>
</html>
