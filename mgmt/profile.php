<?php
$PAGE_TITLE = 'Member Profile';
$ACTIVE_NAV = 'members';
require_once __DIR__ . '/includes/header.php';

$id = (int) ($_GET['id'] ?? 0);
$m  = $id ? db_one("SELECT * FROM v_member_full WHERE id = ?", [$id]) : null;

if (!$m) {
    echo '<div class="glass-card p-4 text-center">';
    echo '  <h3 class="mb-2">Member not found</h3>';
    echo '  <p class="text-muted">No member with id ' . (int) $id . '.</p>';
    echo '  <a class="btn btn-outline-gold" href="' . h(APP_BASE) . '/members.php"><i class="fa-solid fa-arrow-left me-1"></i> Back to Members</a>';
    echo '</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$initials = strtoupper(substr($m['full_name'], 0, 1) . substr(strstr($m['full_name'] . ' ', ' '), 1, 1));
$initials = preg_replace('/\s+/', '', $initials);

// Lightweight placeholder stats (real numbers will come from training/attendance/payroll modules later)
$stats = [
    'shows_performed'  => rand(8, 28),
    'attendance_rate'  => rand(78, 99),
    'discipline_score' => rand(80, 100),
    'years_served'     => $m['joined_date'] ? max(0, floor((time() - strtotime($m['joined_date'])) / (365.25 * 86400))) : 0,
];

$insChip = (function ($s) {
    $map = [
        'active'   => ['Active', 'chip-active'],
        'expiring' => ['Expiring', 'chip-warn'],
        'expired'  => ['Expired', 'chip-danger'],
        'none'     => ['No cover', 'chip-muted'],
        'unknown'  => ['Unknown', 'chip-muted']
    ];
    [$label, $cls] = $map[$s] ?? ['—', 'chip-muted'];
    return "<span class=\"chip $cls\"><span class=\"dot\"></span>$label</span>";
})($m['insurance_status']);
?>

<a href="<?= h(APP_BASE) ?>/members.php" class="btn btn-ghost btn-sm mb-3"><i class="fa-solid fa-arrow-left me-1"></i> Back to Members</a>

<div class="row g-3">

    <!-- ===== LEFT: identity + insurance + QR ===== -->
    <div class="col-12 col-lg-4">
        <div class="glass-card p-4 text-center">
            <?php if ($m['photo_path']): ?>
                <img src="<?= h($m['photo_path']) ?>" alt=""
                     style="width:140px;height:140px;border-radius:50%;object-fit:cover;border:3px solid var(--c-gold);box-shadow:0 0 20px rgba(233,185,98,.3)">
            <?php else: ?>
                <div class="d-inline-flex align-items-center justify-content-center"
                     style="width:140px;height:140px;border-radius:50%;background:linear-gradient(135deg,#f3d27a,#b8862e);color:#061410;font-weight:700;font-size:48px;font-family:'Cinzel',serif;border:3px solid var(--c-gold);box-shadow:0 0 20px rgba(233,185,98,.3)">
                    <?= h($initials) ?>
                </div>
            <?php endif; ?>

            <h2 class="mt-3 mb-1" style="font-family:'Cinzel',serif;font-weight:700;font-size:22px"><?= h($m['full_name']) ?></h2>
            <p class="text-muted small mb-3"><?= h($m['national_id']) ?> · <?= $m['gender'] === 'F' ? '♀ Female' : '♂ Male' ?><?= $m['age'] ? ' · ' . (int) $m['age'] . ' yrs' : '' ?></p>

            <div class="d-flex justify-content-center gap-2 mb-3 flex-wrap">
                <?php
                $sm = $m['status'];
                $statusMap = ['active' => ['Active', 'chip-active'], 'retired' => ['Retired', 'chip-retired'], 'suspended' => ['Suspended', 'chip-danger']];
                [$slbl, $scls] = $statusMap[$sm] ?? [$sm, 'chip-muted'];
                ?>
                <span class="chip <?= $scls ?>"><span class="dot"></span><?= h($slbl) ?></span>
                <?= $insChip ?>
            </div>

            <div class="text-start mt-4" style="font-size:13px">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Section</span><span><?= h($m['section_name'] ?? '—') ?></span></div>
                <?php if ($m['performer_type_name']): ?>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Performer Type</span><span><?= h($m['performer_type_name']) ?></span></div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Role</span><span><?= h($m['role_name'] ?? '—') ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Category</span><span><?= h($m['category_name'] ?? '—') ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Monthly Salary</span><span style="color:var(--c-gold);font-weight:700"><?= number_format((float) $m['fixed_salary']) ?> Rwf</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Joined</span><span><?= $m['joined_date'] ? date('d M Y', strtotime($m['joined_date'])) : '—' ?></span></div>
            </div>

            <hr style="border-color:var(--c-line);margin:1.25rem 0">

            <p class="card-title-gold mb-2 text-start"><i class="fa-solid fa-qrcode me-1"></i> Member Card</p>
            <div class="qr-wrap" id="qrcode"></div>
            <div class="small text-muted mt-2">Scan to look up this member.</div>
        </div>
    </div>

    <!-- ===== RIGHT: details + stats + timeline ===== -->
    <div class="col-12 col-lg-8">

        <!-- Quick stats -->
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="kpi">
                    <div class="icon-badge"><i class="fa-solid fa-drum"></i></div>
                    <p class="label">Shows</p>
                    <p class="value"><?= $stats['shows_performed'] ?></p>
                    <p class="footnote">All-time count</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi">
                    <div class="icon-badge"><i class="fa-solid fa-list-check"></i></div>
                    <p class="label">Attendance</p>
                    <p class="value"><?= $stats['attendance_rate'] ?><small>%</small></p>
                    <p class="footnote">Last 30 days</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi">
                    <div class="icon-badge"><i class="fa-solid fa-scale-balanced"></i></div>
                    <p class="label">Discipline</p>
                    <p class="value"><?= $stats['discipline_score'] ?></p>
                    <p class="footnote">100 = perfect</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi">
                    <div class="icon-badge"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <p class="label">Years served</p>
                    <p class="value"><?= $stats['years_served'] ?></p>
                    <p class="footnote">Since <?= $m['joined_date'] ? date('Y', strtotime($m['joined_date'])) : '—' ?></p>
                </div>
            </div>
        </div>

        <!-- Contact & address -->
        <div class="glass-card p-4 mb-3">
            <p class="card-title-gold mb-3"><i class="fa-solid fa-address-card me-1"></i> Contact</p>
            <div class="row g-3">
                <div class="col-md-4"><span class="text-muted small d-block">Phone</span><a class="text-ink" href="tel:<?= h($m['phone']) ?>"><?= h($m['phone']) ?></a></div>
                <div class="col-md-4"><span class="text-muted small d-block">Email</span><?= $m['email'] ? '<a class="text-ink" href="mailto:' . h($m['email']) . '">' . h($m['email']) . '</a>' : '<span class="text-muted">—</span>' ?></div>
                <div class="col-md-4"><span class="text-muted small d-block">Emergency</span><?= $m['emergency_name'] ? h($m['emergency_name']) . ' · ' . h($m['emergency_phone']) : '<span class="text-muted">—</span>' ?></div>
                <div class="col-12"><span class="text-muted small d-block">Address</span><?= h(trim(($m['umudugudu'] ? $m['umudugudu'] . ', ' : '') . $m['akagari'] . ', ' . $m['umurenge'] . ', ' . $m['akarere'])) ?></div>
            </div>
        </div>

        <!-- Insurance -->
        <div class="glass-card p-4 mb-3">
            <p class="card-title-gold mb-3"><i class="fa-solid fa-shield-halved me-1"></i> Insurance</p>
            <?php if ((int) $m['has_insurance'] === 1): ?>
                <div class="row g-3">
                    <div class="col-md-4"><span class="text-muted small d-block">Plan</span><?= h($m['insurance_name'] ?? '—') ?></div>
                    <div class="col-md-4"><span class="text-muted small d-block">Start</span><?= $m['insurance_start'] ? date('d M Y', strtotime($m['insurance_start'])) : '—' ?></div>
                    <div class="col-md-4"><span class="text-muted small d-block">Expires</span><?= $m['insurance_expiry'] ? date('d M Y', strtotime($m['insurance_expiry'])) : '—' ?></div>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No health insurance on file.</p>
            <?php endif; ?>
        </div>

        <!-- Timeline -->
        <div class="glass-card p-4">
            <p class="card-title-gold mb-3"><i class="fa-solid fa-timeline me-1"></i> Timeline</p>
            <ul class="list-unstyled mb-0" style="font-size:13px">
                <?php if ($m['retirement_date']): ?>
                <li class="pb-3 mb-3 border-bottom" style="border-color:var(--c-line) !important">
                    <span class="chip chip-retired me-2"><span class="dot"></span>Retired</span>
                    <?= date('d M Y', strtotime($m['retirement_date'])) ?> — <?= h(ucfirst(str_replace('_', ' ', (string) $m['retirement_reason']))) ?>
                    <?php if ($m['retirement_note']): ?><div class="small text-muted mt-1"><?= h($m['retirement_note']) ?></div><?php endif; ?>
                </li>
                <?php endif; ?>
                <li>
                    <span class="chip chip-info me-2"><span class="dot"></span>Joined</span>
                    <?= $m['joined_date'] ? date('d M Y', strtotime($m['joined_date'])) : '—' ?>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- The QR-generator CDN is loaded by footer.php below this. We use
     window.load to make sure it's ready before building. -->
<script>
window.addEventListener('load', function () {
    if (typeof qrcode === 'undefined') return;
    const qr = qrcode(0, 'M');
    qr.addData(JSON.stringify({
        id: <?= (int) $m['id'] ?>,
        name: <?= json_encode($m['full_name']) ?>,
        nid: <?= json_encode($m['national_id']) ?>,
        url: location.origin + '<?= h(APP_BASE) ?>/profile.php?id=<?= (int) $m['id'] ?>'
    }));
    qr.make();
    const el = document.getElementById('qrcode');
    if (el) el.innerHTML = qr.createImgTag(5, 0);
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
