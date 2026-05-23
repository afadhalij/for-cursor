<?php
require_once __DIR__ . '/_router.php';
$op = $_GET['op'] ?? 'list';

switch ($op) {
    case 'list':
        reply(['data' => db_all(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM members m WHERE m.section_id = s.id AND m.status='active') AS member_count,
                    (SELECT COUNT(*) FROM roles   r WHERE r.section_id = s.id) AS role_count
               FROM sections s
               ORDER BY s.sort_order, s.id"
        )]);

    case 'performer_types':
        reply(['data' => db_all("SELECT * FROM performer_types ORDER BY sort_order, id")]);
}
fail('unknown_op');
