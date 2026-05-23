<?php
$PAGE_TITLE = 'Members';
$ACTIVE_NAV = 'members';
$EXTRA_JS   = ['members.js'];
require_once __DIR__ . '/includes/header.php';

$activeTab = $_GET['tab'] ?? 'active';
$allowed   = ['active', 'nkuru', 'categories', 'roles', 'sections'];
if (!in_array($activeTab, $allowed, true)) $activeTab = 'active';

// Pull reference data on the server so the page is fully usable before AJAX fires
$sections        = db_all("SELECT * FROM sections ORDER BY sort_order, id");
$performer_types = db_all("SELECT * FROM performer_types ORDER BY sort_order, id");
$roles           = db_all("SELECT r.*, s.code AS section_code, s.name AS section_name FROM roles r JOIN sections s ON s.id = r.section_id ORDER BY s.sort_order, r.sort_order");
$categories      = db_all("SELECT * FROM categories ORDER BY sort_order, id");
?>

<!-- Page header -->
<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
    <div>
        <p class="card-title-gold mb-1">People</p>
        <h1 style="font-family:'Cinzel',serif; font-weight:700; font-size:28px; letter-spacing:.04em;">Members</h1>
        <p class="text-muted small mb-0">All registered members, retirees, payroll categories, roles and sections in one place.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-ghost btn-sm" href="<?= h(APP_BASE) ?>/import_template.php" title="Download CSV template">
            <i class="fa-solid fa-download me-1"></i> Template
        </a>
        <button class="btn btn-ghost btn-sm" data-bs-toggle="modal" data-bs-target="#importModal" id="btnImport">
            <i class="fa-solid fa-file-import me-1"></i> Import
        </button>
        <button class="btn btn-outline-gold btn-sm" data-bs-toggle="modal" data-bs-target="#memberModal" id="btnNewMember">
            <i class="fa-solid fa-user-plus me-1"></i> New Member
        </button>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="memberTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'active' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-active" data-tab="active">
            <i class="fa-solid fa-users"></i> <span class="lbl">Active Members</span>
            <span class="nav-badge" id="badge-active"><?= (int) db_scalar("SELECT COUNT(*) FROM members WHERE status='active'") ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'nkuru' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-nkuru" data-tab="nkuru">
            <i class="fa-solid fa-clock-rotate-left"></i> <span class="lbl">Inganzo Nkuru</span>
            <span class="nav-badge" id="badge-nkuru"><?= (int) db_scalar("SELECT COUNT(*) FROM members WHERE status='retired'") ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'categories' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-categories" data-tab="categories">
            <i class="fa-solid fa-layer-group"></i> <span class="lbl">Categories</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'roles' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-roles" data-tab="roles">
            <i class="fa-solid fa-id-badge"></i> <span class="lbl">Roles</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'sections' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-sections" data-tab="sections">
            <i class="fa-solid fa-sitemap"></i> <span class="lbl">Sections</span>
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- ============================ ACTIVE ============================ -->
    <div class="tab-pane fade <?= $activeTab === 'active' ? 'show active' : '' ?>" id="tab-active">
        <div class="glass-card p-3 p-md-4 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Section</label>
                    <select class="form-select form-select-sm" id="filterSection">
                        <option value="">All sections</option>
                        <?php foreach ($sections as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= h($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Performer Type</label>
                    <select class="form-select form-select-sm" id="filterPerformer">
                        <option value="">All</option>
                        <?php foreach ($performer_types as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= h($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Category</label>
                    <select class="form-select form-select-sm" id="filterCategory">
                        <option value="">All</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= h($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Role</label>
                    <select class="form-select form-select-sm" id="filterRole">
                        <option value="">All</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int) $r['id'] ?>"><?= h($r['name']) ?> (<?= h($r['section_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2 text-end">
                    <button class="btn btn-ghost btn-sm w-100" id="btnResetFilters"><i class="fa-solid fa-rotate-left me-1"></i> Reset</button>
                </div>
            </div>
        </div>

        <div class="glass-card p-3 p-md-4">
            <table class="table align-middle" id="tbl-members" style="width:100%">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Section</th>
                        <th>Type</th>
                        <th>Role</th>
                        <th>Category</th>
                        <th>Insurance</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- ============================ INGANZO NKURU ============================ -->
    <div class="tab-pane fade <?= $activeTab === 'nkuru' ? 'show active' : '' ?>" id="tab-nkuru">
        <div class="glass-card p-3 p-md-4 mb-3">
            <p class="card-title-gold mb-2"><i class="fa-solid fa-clock-rotate-left me-1"></i> Inganzo Nkuru — Retired Members</p>
            <p class="text-muted small mb-0">Archived performers who have retired from active duty. Reactivation moves a member back to the active roster.</p>
        </div>
        <div class="glass-card p-3 p-md-4">
            <table class="table align-middle" id="tbl-nkuru" style="width:100%">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Section</th>
                        <th>Retired</th>
                        <th>Reason</th>
                        <th>Years served</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- ============================ CATEGORIES ============================ -->
    <div class="tab-pane fade <?= $activeTab === 'categories' ? 'show active' : '' ?>" id="tab-categories">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <div>
                <p class="card-title-gold mb-1"><i class="fa-solid fa-layer-group me-1"></i> Payroll Categories</p>
                <p class="text-muted small mb-0">Each member belongs to one category. Payroll reads the fixed salary directly from here.</p>
            </div>
            <button class="btn btn-outline-gold btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal" id="btnNewCategory">
                <i class="fa-solid fa-plus me-1"></i> New Category
            </button>
        </div>
        <div class="glass-card p-3 p-md-4">
            <table class="table align-middle" id="tbl-categories" style="width:100%">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-end">Fixed Salary (Rwf)</th>
                        <th>Members</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- ============================ ROLES ============================ -->
    <div class="tab-pane fade <?= $activeTab === 'roles' ? 'show active' : '' ?>" id="tab-roles">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <div>
                <p class="card-title-gold mb-1"><i class="fa-solid fa-id-badge me-1"></i> Roles</p>
                <p class="text-muted small mb-0">Roles determine portal permissions, dashboard widgets, visibility and approvals.</p>
            </div>
            <button class="btn btn-outline-gold btn-sm" data-bs-toggle="modal" data-bs-target="#roleModal" id="btnNewRole">
                <i class="fa-solid fa-plus me-1"></i> New Role
            </button>
        </div>
        <div class="glass-card p-3 p-md-4">
            <table class="table align-middle" id="tbl-roles" style="width:100%">
                <thead>
                    <tr>
                        <th>Role (Kinyarwanda)</th>
                        <th>Section</th>
                        <th>Description</th>
                        <th>Members</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- ============================ SECTIONS ============================ -->
    <div class="tab-pane fade <?= $activeTab === 'sections' ? 'show active' : '' ?>" id="tab-sections">
        <div class="glass-card p-3 p-md-4 mb-3">
            <p class="card-title-gold mb-1"><i class="fa-solid fa-sitemap me-1"></i> Sections</p>
            <p class="text-muted small mb-0">Every member belongs to exactly one section. Performers further split into Indende, Abaterambabazi and Inyamamare.</p>
        </div>
        <div class="row g-3" id="sections-grid"></div>
    </div>
</div>

<!-- ============================================================
     Member Add/Edit Modal
     ============================================================ -->
<div class="modal fade" id="memberModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="memberModalTitle">Register New Member</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="memberForm">
                <input type="hidden" id="m_id">
                <div class="modal-body">

                    <!-- Personal -->
                    <p class="card-title-gold mb-3"><i class="fa-solid fa-user me-1"></i> Personal</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required" for="m_full_name">Full Name</label>
                            <input type="text" class="form-control" id="m_full_name" name="full_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="m_national_id">National ID</label>
                            <input type="text" class="form-control" id="m_national_id" name="national_id" maxlength="20"
                                   inputmode="numeric" placeholder="1YYYY7 / 1YYYY8 NNNNNNNNNS" required>
                            <div class="form-text">Gender + birth year auto-fill from the 6th and 2–5 digits.</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label required" for="m_gender">Gender</label>
                            <select class="form-select" id="m_gender" name="gender" required>
                                <option value="">—</option>
                                <option value="F">Female</option>
                                <option value="M">Male</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="m_dob">Date of Birth</label>
                            <input type="date" class="form-control" id="m_dob" name="date_of_birth">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required" for="m_phone">Phone</label>
                            <input type="tel" class="form-control" id="m_phone" name="phone" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="m_email">Email</label>
                            <input type="email" class="form-control" id="m_email" name="email">
                        </div>
                    </div>

                    <!-- Address -->
                    <p class="card-title-gold mb-3 mt-4"><i class="fa-solid fa-location-dot me-1"></i> Address</p>
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label required" for="m_akarere">Akarere</label><input type="text" class="form-control" id="m_akarere" name="akarere" required></div>
                        <div class="col-md-3"><label class="form-label required" for="m_umurenge">Umurenge</label><input type="text" class="form-control" id="m_umurenge" name="umurenge" required></div>
                        <div class="col-md-3"><label class="form-label required" for="m_akagari">Akagari</label><input type="text" class="form-control" id="m_akagari" name="akagari" required></div>
                        <div class="col-md-3"><label class="form-label" for="m_umudugudu">Umudugudu</label><input type="text" class="form-control" id="m_umudugudu" name="umudugudu"></div>
                    </div>

                    <!-- Emergency -->
                    <p class="card-title-gold mb-3 mt-4"><i class="fa-solid fa-bell-concierge me-1"></i> Emergency Contact</p>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="m_em_name">Name</label><input type="text" class="form-control" id="m_em_name" name="emergency_name"></div>
                        <div class="col-md-6"><label class="form-label" for="m_em_phone">Phone</label><input type="tel" class="form-control" id="m_em_phone" name="emergency_phone"></div>
                    </div>

                    <!-- Org -->
                    <p class="card-title-gold mb-3 mt-4"><i class="fa-solid fa-sitemap me-1"></i> Section, Role &amp; Category</p>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label required" for="m_section">Section</label>
                            <select class="form-select" id="m_section" name="section_id" required>
                                <option value="">—</option>
                                <?php foreach ($sections as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>" data-code="<?= h($s['code']) ?>"><?= h($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3" id="m_perf_wrap" style="display:none">
                            <label class="form-label required" for="m_performer_type">Performer Type</label>
                            <select class="form-select" id="m_performer_type" name="performer_type_id">
                                <option value="">—</option>
                                <?php foreach ($performer_types as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"><?= h($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="m_role">Role</label>
                            <select class="form-select" id="m_role" name="role_id">
                                <option value="">—</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required" for="m_category">Category</label>
                            <select class="form-select" id="m_category" name="category_id" required>
                                <option value="">—</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>"><?= h($c['name']) ?> — <?= number_format((float) $c['fixed_salary']) ?> Rwf</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Insurance -->
                    <p class="card-title-gold mb-3 mt-4"><i class="fa-solid fa-shield-halved me-1"></i> Health Insurance</p>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="m_has_ins">Insured?</label>
                            <select class="form-select" id="m_has_ins" name="has_insurance">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-4 ins-fields" style="display:none">
                            <label class="form-label" for="m_ins_name">Insurance Name</label>
                            <input type="text" class="form-control" id="m_ins_name" name="insurance_name" value="Mutuelle de Santé">
                        </div>
                        <div class="col-md-2 ins-fields" style="display:none">
                            <label class="form-label" for="m_ins_start">Start Date</label>
                            <input type="date" class="form-control" id="m_ins_start" name="insurance_start">
                        </div>
                        <div class="col-md-3 ins-fields" style="display:none">
                            <label class="form-label" for="m_ins_expiry">Expiration Date</label>
                            <input type="date" class="form-control" id="m_ins_expiry" name="insurance_expiry">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold"><i class="fa-solid fa-floppy-disk me-1"></i> Save Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     Category Modal
     ============================================================ -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="categoryForm">
                <input type="hidden" id="c_id">
                <div class="modal-header"><h5 class="modal-title" id="categoryModalTitle">New Category</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label required" for="c_name">Category Name</label><input type="text" class="form-control" id="c_name" required></div>
                    <div class="mb-3"><label class="form-label required" for="c_salary">Fixed Salary (Rwf)</label><input type="number" class="form-control" id="c_salary" min="0" step="1000" required></div>
                    <div class="mb-3"><label class="form-label" for="c_desc">Description</label><textarea class="form-control" id="c_desc" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label" for="c_status">Status</label>
                        <select class="form-select" id="c_status"><option value="active">Active</option><option value="archived">Archived</option></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold"><i class="fa-solid fa-floppy-disk me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     Role Modal
     ============================================================ -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="roleForm">
                <input type="hidden" id="r_id">
                <div class="modal-header"><h5 class="modal-title" id="roleModalTitle">New Role</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label required" for="r_name">Role (Kinyarwanda)</label><input type="text" class="form-control" id="r_name" required></div>
                    <div class="mb-3"><label class="form-label required" for="r_section">Section</label>
                        <select class="form-select" id="r_section" required>
                            <option value="">—</option>
                            <?php foreach ($sections as $s): ?>
                                <option value="<?= (int) $s['id'] ?>"><?= h($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label" for="r_desc">Description</label><textarea class="form-control" id="r_desc" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold"><i class="fa-solid fa-floppy-disk me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     Retire Modal
     ============================================================ -->
<div class="modal fade" id="retireModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="retireForm">
                <input type="hidden" id="rt_id">
                <div class="modal-header"><h5 class="modal-title">Move to Inganzo Nkuru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="text-muted small">This archives <b id="rt_name"></b> as a retired member. They can be reactivated later.</p>
                    <div class="mb-3"><label class="form-label" for="rt_date">Retirement Date</label><input type="date" class="form-control" id="rt_date" value="<?= date('Y-m-d') ?>"></div>
                    <div class="mb-3"><label class="form-label required" for="rt_reason">Reason</label>
                        <select class="form-select" id="rt_reason" required>
                            <option value="retired">Retired</option>
                            <option value="health">Health</option>
                            <option value="moved_abroad">Moved Abroad</option>
                            <option value="family">Family</option>
                            <option value="career_change">Career Change</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label" for="rt_note">Note</label><textarea class="form-control" id="rt_note" rows="2" placeholder="(optional)"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fa-solid fa-clock-rotate-left me-1"></i> Retire</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     Import from Excel / CSV
     ============================================================ -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-file-import me-1"></i> Import Members</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- STEP 1 — pick file -->
                <div id="imp-step-1">
                    <p class="text-muted small">
                        Pick an Excel file (<b>.xlsx</b>, <b>.xls</b>) or <b>.csv</b>. The file is parsed in your
                        browser — nothing is uploaded until you confirm the mapping.
                    </p>
                    <div class="d-flex gap-3 align-items-center mt-3">
                        <label class="btn btn-gold mb-0">
                            <i class="fa-solid fa-folder-open me-1"></i> Choose file…
                            <input type="file" id="imp-file" accept=".xlsx,.xls,.csv" hidden>
                        </label>
                        <a class="btn btn-ghost btn-sm" href="<?= h(APP_BASE) ?>/import_template.php">
                            <i class="fa-solid fa-download me-1"></i> Download template (CSV)
                        </a>
                        <span id="imp-filename" class="text-muted small"></span>
                    </div>

                    <hr class="my-4" style="border-color:var(--c-line)">

                    <p class="card-title-gold mb-2">Tips</p>
                    <ul class="text-muted small">
                        <li>Required columns (header names are flexible — auto-detected): <b>Full Name</b> and <b>National ID</b>.</li>
                        <li>Optional but recommended: <b>Phone</b>, <b>Gender</b>, <b>Date of Birth</b>, <b>Akarere / Umurenge / Akagari / Umudugudu</b>, <b>Emergency name &amp; phone</b>.</li>
                        <li>If <b>Gender</b> or <b>Date of Birth</b> is missing, the system reads them from the 6th and 2–5 digits of the National ID.</li>
                        <li>The defaults you pick below (Section, Performer Type, Category) are applied to any row that doesn't already have them.</li>
                    </ul>
                </div>

                <!-- STEP 2 — preview + mapping -->
                <div id="imp-step-2" style="display:none">

                    <p class="card-title-gold mb-2">Defaults for rows missing these fields</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label" for="imp-def-section">Section</label>
                            <select class="form-select form-select-sm" id="imp-def-section">
                                <?php foreach ($sections as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>" data-code="<?= h($s['code']) ?>" <?= $s['code'] === 'performers' ? 'selected' : '' ?>><?= h($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3" id="imp-def-perf-wrap">
                            <label class="form-label" for="imp-def-performer">Performer Type</label>
                            <select class="form-select form-select-sm" id="imp-def-performer">
                                <option value="">— None —</option>
                                <?php foreach ($performer_types as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"><?= h($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="imp-def-category">Category</label>
                            <select class="form-select form-select-sm" id="imp-def-category">
                                <?php foreach ($categories as $c): $umushya = ($c['name'] === 'Umushya'); ?>
                                    <option value="<?= (int) $c['id'] ?>" <?= $umushya ? 'selected' : '' ?>><?= h($c['name']) ?> — <?= number_format((float) $c['fixed_salary']) ?> Rwf</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="imp-def-akarere">Akarere (district)</label>
                            <input type="text" class="form-control form-control-sm" id="imp-def-akarere" placeholder="e.g. Gasabo">
                        </div>
                    </div>

                    <p class="card-title-gold mb-2">Column mapping</p>
                    <p class="text-muted small mb-2">
                        Match each column in your file to a member field. We've auto-detected the obvious ones —
                        please double-check.
                    </p>
                    <div class="table-responsive" style="max-height:200px;border:1px solid var(--c-line);border-radius:.65rem">
                        <table class="table table-sm m-0" id="imp-mapping-table">
                            <thead>
                                <tr>
                                    <th>Column in your file</th>
                                    <th>Map to</th>
                                    <th>Sample value</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <p class="card-title-gold mt-4 mb-2">Preview (first 5 rows after mapping)</p>
                    <div class="table-responsive" style="max-height:240px;border:1px solid var(--c-line);border-radius:.65rem">
                        <table class="table table-sm m-0" id="imp-preview-table">
                            <thead><tr><th>Row</th><th>Full Name</th><th>National ID</th><th>Gender</th><th>Phone</th><th>Status</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        <span id="imp-summary">…</span>
                    </p>
                </div>

                <!-- STEP 3 — result -->
                <div id="imp-step-3" style="display:none">
                    <div class="text-center py-4">
                        <i class="fa-solid fa-circle-check" style="font-size:48px;color:#7fd2a3"></i>
                        <h4 class="mt-3 mb-1" style="font-family:'Cinzel',serif;font-weight:700">Import complete</h4>
                        <p id="imp-result-line" class="text-muted">…</p>
                    </div>
                    <div id="imp-errors-wrap" style="display:none">
                        <p class="card-title-gold mb-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Skipped rows</p>
                        <div class="table-responsive" style="max-height:300px;border:1px solid var(--c-line);border-radius:.65rem">
                            <table class="table table-sm m-0" id="imp-errors-table">
                                <thead><tr><th>Row</th><th>Full Name</th><th>Reason</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-ghost" id="imp-back" style="display:none">Back</button>
                <button type="button" class="btn btn-gold" id="imp-import" disabled>
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Import
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hand server-side reference data to the JS layer -->
<script>
    window.MGMT_REF = {
        sections:        <?= json_encode($sections, JSON_UNESCAPED_UNICODE) ?>,
        performer_types: <?= json_encode($performer_types, JSON_UNESCAPED_UNICODE) ?>,
        roles:           <?= json_encode($roles, JSON_UNESCAPED_UNICODE) ?>,
        categories:      <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>,
        activeTab:       <?= json_encode($activeTab) ?>
    };
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
