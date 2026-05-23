<?php
/**
 * Shared page header: <head>, sidebar, topbar. Closing partial is footer.php.
 *
 * Pages set $PAGE_TITLE and $ACTIVE_NAV before requiring this file.
 */

require_once __DIR__ . '/auth.php';
require_login();

$PAGE_TITLE  = $PAGE_TITLE  ?? 'Dashboard';
$ACTIVE_NAV  = $ACTIVE_NAV  ?? 'dashboard';
$BASE        = APP_BASE;
$user        = user();
$initials    = strtoupper(substr($user['full_name'] ?? 'A', 0, 1) . substr(strstr(($user['full_name'] ?? 'A') . ' ', ' '), 1, 1));
$initials    = preg_replace('/\s+/', '', $initials);

// Build the count badges that show in the sidebar.
$nav_counts = [];
try {
    $nav_counts['members']    = (int) db_scalar("SELECT COUNT(*) FROM members WHERE status = 'active'");
    $nav_counts['nkuru']      = (int) db_scalar("SELECT COUNT(*) FROM members WHERE status = 'retired'");
    $nav_counts['categories'] = (int) db_scalar("SELECT COUNT(*) FROM categories WHERE status = 'active'");
} catch (Throwable $e) {
    $nav_counts = ['members' => 0, 'nkuru' => 0, 'categories' => 0];
}

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="robots" content="noindex,nofollow">
<title><?= h($PAGE_TITLE) ?> · <?= h(APP_NAME) ?> · Management</title>

<link rel="icon" type="image/webp" href="<?= h($BASE) ?>/../assets/logo.webp">

<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css" rel="stylesheet">
<link href="<?= h($BASE) ?>/assets/css/app.css?v=2" rel="stylesheet">

<script>
    window.APP = {
        base: <?= json_encode($BASE) ?>,
        csrf: <?= json_encode(csrf_token()) ?>,
        user: <?= json_encode([
            'username'   => $user['username']   ?? null,
            'full_name'  => $user['full_name']  ?? null,
            'role'       => $user['role']       ?? null,
        ]) ?>
    };
</script>
</head>
<body>

<div class="app-shell">

    <!-- ============================ SIDEBAR ============================ -->
    <aside class="sidebar" id="sidebar">
        <a href="<?= h($BASE) ?>/" class="brand">
            <img src="<?= h($BASE) ?>/../assets/logo.webp" alt="Inganzo Ngari">
            <div>
                <div class="b-name">Inganzo Ngari</div>
                <div class="b-sub">Mgmt System</div>
            </div>
        </a>

        <nav class="d-flex flex-column flex-grow-1 py-3">

            <div class="nav-group-label">Overview</div>
            <a class="nav-link <?= $ACTIVE_NAV === 'dashboard' ? 'active' : '' ?>" href="<?= h($BASE) ?>/">
                <i class="fa-solid fa-table-columns"></i> Dashboard
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'calendar' ? 'active' : '' ?>" href="<?= h($BASE) ?>/calendar.php">
                <i class="fa-solid fa-calendar-days"></i> Calendar
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'projects' ? 'active' : '' ?>" href="<?= h($BASE) ?>/projects.php">
                <i class="fa-solid fa-briefcase"></i> Projects
            </a>

            <div class="nav-group-label">People</div>
            <a class="nav-link <?= $ACTIVE_NAV === 'members' ? 'active' : '' ?>" href="<?= h($BASE) ?>/members.php">
                <i class="fa-solid fa-users"></i> Members
                <span class="nav-badge"><?= (int) $nav_counts['members'] ?></span>
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'recruits' ? 'active' : '' ?>" href="<?= h($BASE) ?>/recruits.php">
                <i class="fa-solid fa-user-plus"></i> Recruits
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'insurance' ? 'active' : '' ?>" href="<?= h($BASE) ?>/insurance.php">
                <i class="fa-solid fa-shield-halved"></i> Insurance
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'nkuru' ? 'active' : '' ?>" href="<?= h($BASE) ?>/members.php?tab=nkuru">
                <i class="fa-solid fa-clock-rotate-left"></i> Inganzo Nkuru
                <span class="nav-badge"><?= (int) $nav_counts['nkuru'] ?></span>
            </a>

            <div class="nav-group-label">Training &amp; Stage</div>
            <a class="nav-link <?= $ACTIVE_NAV === 'training' ? 'active' : '' ?>" href="<?= h($BASE) ?>/training.php">
                <i class="fa-solid fa-graduation-cap"></i> Training
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'attendance' ? 'active' : '' ?>" href="<?= h($BASE) ?>/attendance.php">
                <i class="fa-solid fa-list-check"></i> Attendance
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'discipline' ? 'active' : '' ?>" href="<?= h($BASE) ?>/discipline.php">
                <i class="fa-solid fa-scale-balanced"></i> Discipline
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'repertoire' ? 'active' : '' ?>" href="<?= h($BASE) ?>/repertoire.php">
                <i class="fa-solid fa-music"></i> Repertoire
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'shows' ? 'active' : '' ?>" href="<?= h($BASE) ?>/shows.php">
                <i class="fa-solid fa-drum"></i> Shows &amp; Bookings
            </a>

            <div class="nav-group-label">Finance</div>
            <a class="nav-link <?= $ACTIVE_NAV === 'payroll' ? 'active' : '' ?>" href="<?= h($BASE) ?>/payroll.php">
                <i class="fa-solid fa-wallet"></i> Payroll
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'loans' ? 'active' : '' ?>" href="<?= h($BASE) ?>/loans.php">
                <i class="fa-solid fa-hand-holding-dollar"></i> Loans
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'kuzigama' ? 'active' : '' ?>" href="<?= h($BASE) ?>/kuzigama.php">
                <i class="fa-solid fa-piggy-bank"></i> Kuzigama
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'welfare' ? 'active' : '' ?>" href="<?= h($BASE) ?>/welfare.php">
                <i class="fa-solid fa-heart-pulse"></i> Welfare Fund
            </a>

            <div class="nav-group-label">Configuration</div>
            <a class="nav-link <?= $ACTIVE_NAV === 'categories' ? 'active' : '' ?>" href="<?= h($BASE) ?>/members.php?tab=categories">
                <i class="fa-solid fa-layer-group"></i> Categories
                <span class="nav-badge"><?= (int) $nav_counts['categories'] ?></span>
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'roles' ? 'active' : '' ?>" href="<?= h($BASE) ?>/members.php?tab=roles">
                <i class="fa-solid fa-id-badge"></i> Roles
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'sections' ? 'active' : '' ?>" href="<?= h($BASE) ?>/members.php?tab=sections">
                <i class="fa-solid fa-sitemap"></i> Sections
            </a>
            <a class="nav-link <?= $ACTIVE_NAV === 'settings' ? 'active' : '' ?>" href="<?= h($BASE) ?>/settings.php">
                <i class="fa-solid fa-gear"></i> Settings
            </a>
        </nav>

        <div class="user-card">
            <div class="avatar"><?= h($initials) ?></div>
            <div class="flex-grow-1" style="min-width:0">
                <div class="text-truncate" style="font-size:13px; font-weight:600;"><?= h($user['full_name'] ?? '—') ?></div>
                <div style="font-size:11px; color:var(--c-muted);"><?= h(ucfirst($user['role'] ?? '—')) ?></div>
            </div>
            <a href="<?= h($BASE) ?>/logout.php" class="icon-btn" title="Sign out"
               style="width:32px;height:32px;border-radius:.5rem;color:var(--c-muted);">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
        </div>
    </aside>

    <!-- ============================ MAIN ============================ -->
    <div class="app-main">

        <header class="topbar">
            <button class="sidebar-toggle" type="button" onclick="document.getElementById('sidebar').classList.toggle('is-open')">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Search members, shows, documents…">
            </div>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="pill text-success d-none d-md-inline-flex" style="color:#7fd2a3 !important;">
                    <span class="dot"></span>Live · <?= (int) ($nav_counts['members']) ?> active
                </span>
                <div class="lang-switch">
                    <button class="active" type="button">EN</button>
                    <button type="button">RW</button>
                </div>
                <button class="icon-btn" title="Notifications">
                    <i class="fa-regular fa-bell"></i>
                </button>
                <div class="avatar" title="<?= h($user['full_name'] ?? '') ?>"><?= h($initials) ?></div>
            </div>
        </header>

        <main class="page-wrap flex-grow-1">
