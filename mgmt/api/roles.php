<?php
require_once __DIR__ . '/_router.php';
$op = $_GET['op'] ?? 'list';

switch ($op) {
    case 'list': {
        $where = []; $params = [];
        if (!empty($_GET['section_id'])) { $where[] = 'r.section_id = ?'; $params[] = (int) $_GET['section_id']; }
        $sql = "SELECT r.*, s.name AS section_name,
                       (SELECT COUNT(*) FROM members m WHERE m.role_id = r.id AND m.status='active') AS member_count
                  FROM roles r
                  JOIN sections s ON s.id = r.section_id"
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY r.section_id, r.sort_order, r.name';
        reply(['data' => db_all($sql, $params)]);
    }

    case 'create': case 'update': {
        $b           = read_body();
        $name        = required($b, 'name');
        $section_id  = (int) ($b['section_id'] ?? 0);
        if ($section_id <= 0) fail('missing_section_id');
        $description = nullable($b, 'description');

        if ($op === 'create') {
            try {
                $id = db_insert(
                    "INSERT INTO roles (name, section_id, description, sort_order)
                     VALUES (?, ?, ?, (SELECT IFNULL(MAX(s.sort_order),0)+1 FROM roles s WHERE s.section_id = ?))",
                    [$name, $section_id, $description, $section_id]
                );
                audit_log('role', $id, 'create');
                reply(['ok' => true, 'id' => $id]);
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'uniq_role_per_section')) fail('name_taken_in_section', 409);
                throw $e;
            }
        } else {
            $id = (int) ($_GET['id'] ?? 0);
            db_exec(
                "UPDATE roles SET name=?, section_id=?, description=? WHERE id=?",
                [$name, $section_id, $description, $id]
            );
            audit_log('role', $id, 'update');
            reply(['ok' => true]);
        }
    }

    case 'delete': {
        $id = (int) ($_GET['id'] ?? 0);
        $inUse = (int) db_scalar("SELECT COUNT(*) FROM members WHERE role_id = ?", [$id]);
        if ($inUse > 0) fail('in_use', 409, ['count' => $inUse]);
        db_exec("DELETE FROM roles WHERE id = ?", [$id]);
        audit_log('role', $id, 'delete');
        reply(['ok' => true]);
    }
}
fail('unknown_op');
