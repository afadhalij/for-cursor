<?php
/**
 * Session-based auth + CSRF helpers.
 *
 * Usage:
 *   require_once __DIR__ . '/auth.php';
 *   require_login();
 *
 * For AJAX endpoints, use require_login_json() instead — sends a JSON 401
 * if not logged in, instead of redirecting to the login page.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user']);
}

function require_login(): void {
    if (!is_logged_in()) {
        $next = $_SERVER['REQUEST_URI'] ?? APP_BASE . '/';
        header('Location: ' . APP_BASE . '/login.php?next=' . urlencode($next));
        exit;
    }
}

function require_login_json(): void {
    if (!is_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
}

function login_attempt(string $username, string $password): bool {
    $u = db_one("SELECT * FROM users WHERE username = ? LIMIT 1", [$username]);
    if (!$u) return false;
    if (!password_verify($password, (string) $u['password_hash'])) return false;
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'         => (int) $u['id'],
        'username'   => $u['username'],
        'full_name'  => $u['full_name'],
        'role'       => $u['role'],
        'must_change'=> (bool) $u['must_change'],
    ];
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
    try {
        db_exec("UPDATE users SET last_login = NOW() WHERE id = ?", [(int) $u['id']]);
    } catch (Throwable $e) { /* ignore */ }
    audit_log('auth', (int) $u['id'], 'login');
    return true;
}

function logout(): void {
    $uid = (int) ($_SESSION['user']['id'] ?? 0);
    audit_log('auth', $uid ?: null, 'logout');
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): bool {
    $sent = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $sent);
}

function require_csrf_json(): void {
    if (!csrf_check()) {
        http_response_code(419);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'csrf_token_mismatch']);
        exit;
    }
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
