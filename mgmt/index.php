<?php
$PAGE_TITLE = 'Dashboard';
$ACTIVE_NAV = 'dashboard';
$EXTRA_JS   = ['dashboard.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-3">
    <div>
        <p class="card-title-gold mb-1">Murakaza neza</p>
        <h1 style="font-family:'Cinzel',serif; font-weight:700; font-size:28px; letter-spacing:.04em;">Welcome, <?= h(user()['full_name'] ?? '') ?></h1>
        <p class="text-muted small mb-0">Members module overview — live counts and alerts.</p>
    </div>
    <div class="text-end">
        <p class="text-muted small mb-1 text-uppercase" style="letter-spacing:.18em;font-size:11px">Today</p>
        <p style="font-family:'Cinzel',serif;font-size:18px;margin:0"><?= date('l, j F Y') ?></p>
    </div>
</div>

<!-- KPI cards -->
<div class="row g-3 mb-4" id="kpi-row">
    <div class="col-6 col-xl-3">
        <div class="kpi">
            <div class="icon-badge"><i class="fa-solid fa-users"></i></div>
            <p class="label">Total Active Members</p>
            <p class="value" data-kpi="active">…</p>
            <span class="delta up" data-kpi="newThisMonth">+ this month</span>
            <p class="footnote" data-kpi="breakdown">Across all sections</p>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="kpi">
            <div class="icon-badge" style="background:rgba(44,126,184,.15);color:#9bd0f5"><i class="fa-solid fa-mars"></i></div>
            <p class="label">Indende</p>
            <p class="value" data-kpi="indende">…</p>
            <p class="footnote">Male dancers</p>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="kpi">
            <div class="icon-badge" style="background:rgba(192,57,43,.15);color:#ffb4ad"><i class="fa-solid fa-venus"></i></div>
            <p class="label">Abaterambabazi</p>
            <p class="value" data-kpi="abater">…</p>
            <p class="footnote">Female dancers</p>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="kpi">
            <div class="icon-badge" style="background:rgba(61,155,106,.15);color:#7fd2a3"><i class="fa-solid fa-drum"></i></div>
            <p class="label">Inyamamare</p>
            <p class="value" data-kpi="inyamamare">…</p>
            <p class="footnote">Singers + drummers</p>
        </div>
    </div>
</div>

<!-- Charts row -->
<div class="row g-3 mb-4">
    <div class="col-12 col-xl-6">
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between mb-3">
                <p class="card-title-gold m-0"><i class="fa-solid fa-layer-group me-1"></i> Members by Category</p>
                <span class="small text-muted" id="cat-total">—</span>
            </div>
            <div style="height:280px;position:relative"><canvas id="chartCategory"></canvas></div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between mb-3">
                <p class="card-title-gold m-0"><i class="fa-solid fa-people-group me-1"></i> Members by Performer Type</p>
                <span class="small text-muted">3 sub-groups</span>
            </div>
            <div style="height:280px;position:relative"><canvas id="chartPerformer"></canvas></div>
        </div>
    </div>
</div>

<!-- Insurance + Birthdays + New + Roles -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="glass-card p-4">
            <p class="card-title-gold mb-3"><i class="fa-solid fa-shield-halved me-1"></i> Insurance Alerts</p>
            <ul class="list-unstyled m-0" id="insAlerts"></ul>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="glass-card p-4">
            <p class="card-title-gold mb-3"><i class="fa-solid fa-cake-candles me-1"></i> Upcoming Birthdays</p>
            <ul class="list-unstyled m-0" id="birthdays"></ul>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="glass-card p-4">
            <p class="card-title-gold mb-3"><i class="fa-solid fa-user-plus me-1"></i> New Members</p>
            <ul class="list-unstyled m-0" id="newMembers"></ul>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between mb-3">
                <p class="card-title-gold m-0"><i class="fa-solid fa-id-badge me-1"></i> Role Distribution</p>
                <a href="<?= h(APP_BASE) ?>/members.php?tab=roles" class="small text-gold" style="color:var(--c-gold-bright);text-decoration:none">Manage roles <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
            <div style="height:300px;position:relative"><canvas id="chartRoles"></canvas></div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
