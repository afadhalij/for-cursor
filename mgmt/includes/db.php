<?php
/**
 * Thin PDO singleton + helpers.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * @return PDO
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            if (APP_ENV === 'development') {
                die('DB connection failed: ' . htmlspecialchars($e->getMessage()));
            }
            die('Database is unavailable. Please contact the administrator.');
        }
    }
    return $pdo;
}

/** Fetch all rows */
function db_all(string $sql, array $params = []): array {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Fetch one row */
function db_one(string $sql, array $params = []): ?array {
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

/** Fetch one scalar value (first column of first row) */
function db_scalar(string $sql, array $params = []) {
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch(PDO::FETCH_NUM);
    return $row === false ? null : $row[0];
}

/** Execute a write; return affected row count */
function db_exec(string $sql, array $params = []): int {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/** Insert and return the new row's auto-increment id */
function db_insert(string $sql, array $params = []): int {
    $st = db()->prepare($sql);
    $st->execute($params);
    return (int) db()->lastInsertId();
}

/** Write to the audit_log table. Best-effort: swallow errors so audits never break writes. */
function audit_log(string $entity, ?int $entityId, string $action, ?array $diff = null): void {
    try {
        $actor = $_SESSION['user']['username'] ?? 'system';
        db_exec(
            "INSERT INTO audit_log (actor, entity, entity_id, action, diff)
             VALUES (?, ?, ?, ?, ?)",
            [$actor, $entity, $entityId, $action, $diff ? json_encode($diff, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null]
        );
    } catch (Throwable $e) { /* ignore */ }
}
