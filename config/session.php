<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require the user to be logged in.
 * Redirects to login page if not authenticated.
 */
function requireLogin(): void {
    if (empty($_SESSION['user'])) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

/**
 * Require the user to have one of the given roles.
 * Calls requireLogin() first, then checks role.
 */
function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        http_response_code(403);
        include __DIR__ . '/../includes/403.php';
        exit;
    }
}

/**
 * Returns the current logged-in user array, or null.
 */
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Checks if current user has a specific role.
 */
function hasRole(string $role): bool {
    return ($_SESSION['user']['role'] ?? '') === $role;
}

/**
 * Checks if current user has one of the given roles.
 */
function hasAnyRole(array $roles): bool {
    return in_array($_SESSION['user']['role'] ?? '', $roles, true);
}

/**
 * Returns the current user's office_id (or null for super_admin).
 */
function currentOfficeId(): ?int {
    return $_SESSION['user']['office_id'] ?? null;
}
