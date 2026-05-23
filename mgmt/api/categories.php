<?php
require_once __DIR__ . '/_router.php';
$op = $_GET['op'] ?? 'list';

switch ($op) {
    case 'list':
        reply(['data' => db_all(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM members m WHERE m.category_id = c.id AND m.status='active') AS member_count
               FROM categories c
               ORDER BY c.sort_order, c.id"
        )]);

    case 'create': case 'update': {
        $b           = read_body();
        $name        = required($b, 'name');
        $salary      = (float) ($b['fixed_salary'] ?? 0);
        $description = nullable($b, 'description');
        $status      = ($b['status'] ?? 'active') === 'archived' ? 'archived' : 'active';

        if ($op === 'create') {
            try {
                $id = db_insert(
                    "INSERT INTO categories (name, fixed_salary, description, status, sort_order)
                     VALUES (?, ?, ?, ?, (SELECT IFNULL(MAX(s.sort_order),0)+1 FROM categories s))",
                    [$name, $salary, $description, $status]
                );
                audit_log('category', $id, 'create');
                reply(['ok' => true, 'id' => $id]);
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'name')) fail('name_taken', 409);
                throw $e;
            }
        } else {
            $id = (int) ($_GET['id'] ?? 0);
            db_exec(
                "UPDATE categories SET name=?, fixed_salary=?, description=?, status=? WHERE id=?",
                [$name, $salary, $description, $status, $id]
            );
            audit_log('category', $id, 'update');
            reply(['ok' => true]);
        }
    }

    case 'delete': {
        $id = (int) ($_GET['id'] ?? 0);
        $inUse = (int) db_scalar("SELECT COUNT(*) FROM members WHERE category_id = ?", [$id]);
        if ($inUse > 0) fail('in_use', 409, ['count' => $inUse]);
        db_exec("DELETE FROM categories WHERE id = ?", [$id]);
        audit_log('category', $id, 'delete');
        reply(['ok' => true]);
    }
}

fail('unknown_op');
