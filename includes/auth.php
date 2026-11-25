<?php
/**
 * ==============================================================
 * User Authentication & Session Security Helpers
 * ==============================================================
 */

require_once __DIR__ . '/../config/config.php';

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'name'  => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'customer'
    ];
}

function login_user(array $user): void {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = (string)$user['name'];
    $_SESSION['user_email'] = (string)$user['email'];
    $_SESSION['user_phone'] = (string)($user['phone'] ?? '');
    $_SESSION['user_role']  = (string)$user['role'];
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

function require_login(string $redirect = ''): void {
    if (!is_logged_in()) {
        $target = $redirect ?: BASE_URL . '/login.php';
        header("Location: " . $target);
        exit;
    }
}

function require_admin(string $redirect = ''): void {
    if (!is_admin()) {
        $target = $redirect ?: BASE_URL . '/admin/login.php';
        header("Location: " . $target);
        exit;
    }
}
