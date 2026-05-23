<?php
/**
 * Streams a CSV template the admin can fill in and re-upload via the Members
 * Import modal. Header names match what the auto-mapper looks for.
 */
require_once __DIR__ . '/includes/auth.php';
require_login();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="inganzo-members-template.csv"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');

// BOM so Excel detects UTF-8 (Kinyarwanda has no special chars but be safe)
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
    'Full Name', 'National ID', 'Gender', 'Date of Birth', 'Phone', 'Email',
    'Akarere', 'Umurenge', 'Akagari', 'Umudugudu',
    'Emergency Name', 'Emergency Phone',
    'Section', 'Performer Type', 'Role', 'Category'
]);

// Two example rows
fputcsv($out, [
    'Jean Bosco Habiyaremye', '1198380123456781', 'M', '1983-04-12', '+250788111201', 'jbosco@example.rw',
    'Gasabo', 'Remera', 'Nyabisindu', 'Karambo',
    'Marie Uwimana', '+250788111301',
    'Performers', 'Indende', 'Umutoza mukuru', 'Category 1'
]);
fputcsv($out, [
    'Aline Uwase', '1199470234567823', '', '', '+250788111213', '',
    'Kicukiro', 'Niboye', 'Rukurazo', 'Iterambere',
    'Jean de Dieu Mugisha', '+250788111313',
    'Performers', 'Abaterambabazi', '', 'Stagiaire'
]);

fclose($out);
exit;
