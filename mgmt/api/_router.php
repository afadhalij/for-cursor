<?php
/**
 * Shared bootstrap for all /api/*.php endpoints.
 * Returns JSON for everything; CSRF-checks any non-GET request.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login_json();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    require_csrf_json();
}

/** Return JSON and exit. */
function reply($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Fail with a JSON error. */
function fail(string $error, int $status = 400, array $extra = []): void {
    reply(['error' => $error] + $extra, $status);
}

/** Read JSON body (or fall back to POST) into an associative array. */
function read_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return $_POST;
    $j = json_decode($raw, true);
    return is_array($j) ? $j : $_POST;
}

/** Return a non-empty trimmed string from the input, or fail. */
function required(array $in, string $key, string $label = null): string {
    $v = isset($in[$key]) ? trim((string) $in[$key]) : '';
    if ($v === '') fail('missing_' . ($label ?? $key));
    return $v;
}

/** Return a trimmed string or null. */
function nullable(array $in, string $key): ?string {
    if (!array_key_exists($key, $in)) return null;
    $v = trim((string) $in[$key]);
    return $v === '' ? null : $v;
}

/** Return an int or null. */
function nullable_int(array $in, string $key): ?int {
    if (!array_key_exists($key, $in)) return null;
    $v = $in[$key];
    if ($v === '' || $v === null) return null;
    return (int) $v;
}
