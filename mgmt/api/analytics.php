<?php
require_once __DIR__ . '/_router.php';

$op = $_GET['op'] ?? 'overview';

if ($op === 'overview') {
    $today = (new DateTimeImmutable('today', new DateTimeZone('Africa/Kigali')))->format('Y-m-d');

    $totals = [
        'active'        => (int) db_scalar("SELECT COUNT(*) FROM members WHERE status='active'"),
        'retired'       => (int) db_scalar("SELECT COUNT(*) FROM members WHERE status='retired'"),
        'indende'       => (int) db_scalar("SELECT COUNT(*) FROM members m JOIN performer_types p ON p.id = m.performer_type_id WHERE m.status='active' AND p.code='indende'"),
        'abaterambabazi'=> (int) db_scalar("SELECT COUNT(*) FROM members m JOIN performer_types p ON p.id = m.performer_type_id WHERE m.status='active' AND p.code='abaterambabazi'"),
        'inyamamare'    => (int) db_scalar("SELECT COUNT(*) FROM members m JOIN performer_types p ON p.id = m.performer_type_id WHERE m.status='active' AND p.code='inyamamare'"),
        'leaders'       => (int) db_scalar("SELECT COUNT(*) FROM members m JOIN sections s ON s.id = m.section_id WHERE m.status='active' AND s.code='leaders'"),
        'support'       => (int) db_scalar("SELECT COUNT(*) FROM members m JOIN sections s ON s.id = m.section_id WHERE m.status='active' AND s.code='support'"),
    ];

    $insurance = [
        'active'   => (int) db_scalar("SELECT COUNT(*) FROM v_member_full WHERE status='active' AND insurance_status='active'"),
        'expiring' => (int) db_scalar("SELECT COUNT(*) FROM v_member_full WHERE status='active' AND insurance_status='expiring'"),
        'expired'  => (int) db_scalar("SELECT COUNT(*) FROM v_member_full WHERE status='active' AND insurance_status='expired'"),
        'none'     => (int) db_scalar("SELECT COUNT(*) FROM v_member_full WHERE status='active' AND insurance_status='none'"),
    ];

    $newThisMonth = (int) db_scalar("SELECT COUNT(*) FROM members WHERE joined_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");

    $byCategory = db_all(
        "SELECT c.id, c.name, c.fixed_salary,
                COUNT(m.id) AS member_count,
                SUM(c.fixed_salary) AS monthly_total
           FROM categories c
           LEFT JOIN members m ON m.category_id = c.id AND m.status='active'
           GROUP BY c.id, c.name, c.fixed_salary
           ORDER BY c.sort_order, c.id"
    );

    $byRole = db_all(
        "SELECT r.name, s.name AS section_name, COUNT(m.id) AS member_count
           FROM roles r
           JOIN sections s ON s.id = r.section_id
           LEFT JOIN members m ON m.role_id = r.id AND m.status='active'
           GROUP BY r.id, r.name, s.name
           ORDER BY member_count DESC, r.name"
    );

    $upcomingBirthdays = db_all(
        "SELECT id, full_name, date_of_birth,
                DATE_FORMAT(date_of_birth, '%m-%d') AS md,
                photo_path,
                CASE
                    WHEN DATE_FORMAT(date_of_birth, '%m-%d') >= DATE_FORMAT(CURDATE(), '%m-%d')
                        THEN DATEDIFF(STR_TO_DATE(CONCAT(YEAR(CURDATE()),'-',DATE_FORMAT(date_of_birth,'%m-%d')), '%Y-%m-%d'), CURDATE())
                    ELSE DATEDIFF(STR_TO_DATE(CONCAT(YEAR(CURDATE())+1,'-',DATE_FORMAT(date_of_birth,'%m-%d')), '%Y-%m-%d'), CURDATE())
                END AS days_to_birthday
           FROM members
           WHERE status='active' AND date_of_birth IS NOT NULL
           ORDER BY days_to_birthday
           LIMIT 6"
    );

    $newMembers = db_all(
        "SELECT id, full_name, joined_date, photo_path
           FROM members
           WHERE status='active'
           ORDER BY joined_date DESC, id DESC
           LIMIT 6"
    );

    $insuranceAlerts = db_all(
        "SELECT id, full_name, insurance_name, insurance_expiry, insurance_status, photo_path
           FROM v_member_full
           WHERE status='active' AND insurance_status IN ('expired','expiring')
           ORDER BY FIELD(insurance_status,'expired','expiring'), insurance_expiry
           LIMIT 8"
    );

    reply([
        'totals'           => $totals,
        'insurance'        => $insurance,
        'new_this_month'   => $newThisMonth,
        'by_category'      => $byCategory,
        'by_role'          => $byRole,
        'upcoming_birthdays' => $upcomingBirthdays,
        'new_members'      => $newMembers,
        'insurance_alerts' => $insuranceAlerts,
        'generated_at'     => $today,
    ]);
}

fail('unknown_op');
