/* ===========================================================================
 * Inganzo Ngari Mgmt — Members page (5 tabs)
 * ======================================================================== */

(function () {
    'use strict';

    const REF = window.MGMT_REF || {};
    const { api, toast, formatRwf, bindNationalIdAutofill, confirmAction, insuranceChip, memberStatusChip, escapeHtml } = window.app;

    let dtMembers, dtNkuru, dtCategories, dtRoles;

    // -----------------------------------------------------------------------
    // ACTIVE MEMBERS TAB
    // -----------------------------------------------------------------------

    function memberRowHtml(m) {
        const initials = (m.full_name || '?').split(/\s+/).slice(0, 2).map(s => s[0]).join('').toUpperCase();
        const photo = m.photo_path
            ? `<img src="${escapeHtml(m.photo_path)}" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid var(--c-line)">`
            : `<span class="d-inline-flex align-items-center justify-content-center"
                  style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#f3d27a,#b8862e);color:#061410;font-weight:700;font-size:12px;flex-shrink:0">${escapeHtml(initials)}</span>`;
        return `
            <div class="d-flex align-items-center gap-2">
                ${photo}
                <div style="min-width:0">
                    <div style="font-weight:600;color:var(--c-ink)">${escapeHtml(m.full_name)}</div>
                    <div class="small text-muted">${escapeHtml(m.national_id || '')} · ${escapeHtml(m.gender === 'F' ? '♀ Female' : '♂ Male')}${m.age ? ' · ' + m.age + ' yrs' : ''}</div>
                </div>
            </div>`;
    }

    function actionsHtml(m) {
        return `
            <div class="d-flex gap-1 justify-content-end">
                <a class="btn btn-sm btn-ghost" href="${APP.base}/profile.php?id=${m.id}" title="View profile"><i class="fa-solid fa-eye"></i></a>
                <button class="btn btn-sm btn-ghost m-edit" data-id="${m.id}" title="Edit"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-sm btn-ghost m-retire" data-id="${m.id}" data-name="${escapeHtml(m.full_name)}" title="Retire"><i class="fa-solid fa-clock-rotate-left"></i></button>
            </div>`;
    }

    function loadActive() {
        const params = new URLSearchParams({ op: 'list', status: 'active' });
        ['section', 'performer', 'role', 'category'].forEach(k => {
            const el = document.getElementById('filter' + k.charAt(0).toUpperCase() + k.slice(1));
            if (el && el.value) {
                const apiKey = (k === 'section') ? 'section_id'
                             : (k === 'performer') ? 'performer_type'
                             : (k === 'role') ? 'role_id'
                             : 'category_id';
                params.set(apiKey, el.value);
            }
        });

        api('members.php?' + params.toString())
            .then(res => {
                if (dtMembers) dtMembers.destroy();
                document.querySelector('#tbl-members tbody').innerHTML = '';

                const rows = res.data.map(m => [
                    memberRowHtml(m),
                    escapeHtml(m.section_name || '—'),
                    escapeHtml(m.performer_type_name || '—'),
                    escapeHtml(m.role_name || '—'),
                    `<span title="${escapeHtml(m.category_name || '')}">${escapeHtml(m.category_name || '—')}<div class="small text-muted">${m.fixed_salary ? formatRwf(m.fixed_salary) + ' Rwf' : '—'}</div></span>`,
                    insuranceChip(m.insurance_status),
                    actionsHtml(m)
                ]);

                dtMembers = $('#tbl-members').DataTable({
                    data: rows,
                    pageLength: 15,
                    lengthMenu: [10, 15, 25, 50, 100],
                    order: [[0, 'asc']],
                    columnDefs: [{ targets: -1, orderable: false, className: 'text-end' }],
                    language: { search: 'Search:', lengthMenu: '_MENU_ per page', info: 'Showing _START_ to _END_ of _TOTAL_ members' }
                });

                document.getElementById('badge-active').textContent = res.data.length;
            })
            .catch(err => toast(err.message, 'error'));
    }

    // -----------------------------------------------------------------------
    // INGANZO NKURU TAB
    // -----------------------------------------------------------------------

    function reasonLabel(r) {
        const map = { retired: 'Retired', health: 'Health', moved_abroad: 'Moved abroad', family: 'Family', career_change: 'Career change', other: 'Other' };
        return map[r] || (r || '—');
    }

    function yearsServed(joined, retired) {
        if (!joined) return '—';
        const d1 = new Date(joined);
        const d2 = retired ? new Date(retired) : new Date();
        const y = (d2 - d1) / (1000 * 60 * 60 * 24 * 365.25);
        return y > 0 ? y.toFixed(1) : '—';
    }

    function loadNkuru() {
        api('members.php?op=list&status=retired')
            .then(res => {
                if (dtNkuru) dtNkuru.destroy();
                document.querySelector('#tbl-nkuru tbody').innerHTML = '';
                const rows = res.data.map(m => [
                    memberRowHtml(m),
                    escapeHtml(m.section_name || '—') + (m.performer_type_name ? ' · ' + escapeHtml(m.performer_type_name) : ''),
                    m.retirement_date ? new Date(m.retirement_date).toLocaleDateString('en-GB', { year: 'numeric', month: 'short', day: 'numeric' }) : '—',
                    `<div>${escapeHtml(reasonLabel(m.retirement_reason))}</div>${m.retirement_note ? '<div class="small text-muted">' + escapeHtml(m.retirement_note) + '</div>' : ''}`,
                    yearsServed(m.joined_date, m.retirement_date),
                    `<div class="d-flex gap-1 justify-content-end">
                        <a class="btn btn-sm btn-ghost" href="${APP.base}/profile.php?id=${m.id}" title="View"><i class="fa-solid fa-eye"></i></a>
                        <button class="btn btn-sm btn-outline-gold m-reactivate" data-id="${m.id}" data-name="${escapeHtml(m.full_name)}"><i class="fa-solid fa-arrow-rotate-left me-1"></i>Reactivate</button>
                    </div>`
                ]);
                dtNkuru = $('#tbl-nkuru').DataTable({
                    data: rows,
                    pageLength: 15,
                    order: [[2, 'desc']],
                    columnDefs: [{ targets: -1, orderable: false, className: 'text-end' }],
                    language: { search: 'Search:', info: 'Showing _START_ to _END_ of _TOTAL_ retired members' }
                });
                document.getElementById('badge-nkuru').textContent = res.data.length;
            })
            .catch(err => toast(err.message, 'error'));
    }

    // -----------------------------------------------------------------------
    // CATEGORIES TAB
    // -----------------------------------------------------------------------

    function loadCategories() {
        api('categories.php?op=list')
            .then(res => {
                if (dtCategories) dtCategories.destroy();
                document.querySelector('#tbl-categories tbody').innerHTML = '';
                const rows = res.data.map(c => [
                    `<div style="font-weight:600">${escapeHtml(c.name)}</div>`,
                    `<div class="text-end" style="font-family:Cinzel,serif;font-weight:700;color:${Number(c.fixed_salary)>0?'var(--c-gold)':'var(--c-muted)'}">${formatRwf(c.fixed_salary)}</div>`,
                    `<span class="chip chip-info"><span class="dot"></span>${c.member_count} members</span>`,
                    escapeHtml(c.description || '—'),
                    `<span class="chip chip-${c.status === 'active' ? 'active' : 'muted'}"><span class="dot"></span>${c.status}</span>`,
                    `<div class="d-flex gap-1 justify-content-end">
                        <button class="btn btn-sm btn-ghost c-edit" data-id="${c.id}"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-ghost c-delete" data-id="${c.id}" data-name="${escapeHtml(c.name)}" data-count="${c.member_count}"><i class="fa-solid fa-trash"></i></button>
                    </div>`
                ]);
                dtCategories = $('#tbl-categories').DataTable({
                    data: rows, pageLength: 15, order: [[1, 'desc']],
                    columnDefs: [
                        { targets: 1, type: 'num-fmt', className: 'text-end' },
                        { targets: -1, orderable: false, className: 'text-end' }
                    ],
                    language: { search: 'Search:', info: 'Showing _START_ to _END_ of _TOTAL_ categories' }
                });
            })
            .catch(err => toast(err.message, 'error'));
    }

    // -----------------------------------------------------------------------
    // ROLES TAB
    // -----------------------------------------------------------------------

    function loadRoles() {
        api('roles.php?op=list')
            .then(res => {
                if (dtRoles) dtRoles.destroy();
                document.querySelector('#tbl-roles tbody').innerHTML = '';
                const rows = res.data.map(r => [
                    `<div style="font-weight:600;color:var(--c-ink)">${escapeHtml(r.name)}</div>`,
                    escapeHtml(r.section_name),
                    escapeHtml(r.description || '—'),
                    `<span class="chip chip-info"><span class="dot"></span>${r.member_count}</span>`,
                    `<div class="d-flex gap-1 justify-content-end">
                        <button class="btn btn-sm btn-ghost r-edit" data-id="${r.id}"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-ghost r-delete" data-id="${r.id}" data-name="${escapeHtml(r.name)}" data-count="${r.member_count}"><i class="fa-solid fa-trash"></i></button>
                    </div>`
                ]);
                dtRoles = $('#tbl-roles').DataTable({
                    data: rows, pageLength: 25, order: [[1, 'asc'], [0, 'asc']],
                    columnDefs: [{ targets: -1, orderable: false, className: 'text-end' }],
                    language: { search: 'Search:', info: 'Showing _START_ to _END_ of _TOTAL_ roles' }
                });
            })
            .catch(err => toast(err.message, 'error'));
    }

    // -----------------------------------------------------------------------
    // SECTIONS TAB
    // -----------------------------------------------------------------------

    function loadSections() {
        api('sections.php?op=list')
            .then(res => {
                const grid = document.getElementById('sections-grid');
                grid.innerHTML = '';
                res.data.forEach(s => {
                    const iconMap = { leaders: 'crown', performers: 'drum', support: 'people-roof' };
                    const icon = iconMap[s.code] || 'sitemap';
                    grid.insertAdjacentHTML('beforeend', `
                        <div class="col-md-6 col-xl-4">
                            <div class="glass-card p-4 h-100">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:rgba(233,185,98,.15);border:1px solid rgba(233,185,98,.3);color:var(--c-gold);font-size:20px"><i class="fa-solid fa-${icon}"></i></div>
                                    <div>
                                        <h5 style="font-family:'Cinzel',serif;font-weight:700;margin:0;font-size:18px">${escapeHtml(s.name)}</h5>
                                        <div class="small text-muted">${escapeHtml(s.description || '')}</div>
                                    </div>
                                </div>
                                <div class="row text-center g-0">
                                    <div class="col-6 border-end" style="border-color:var(--c-line) !important">
                                        <div style="font-family:Cinzel,serif;font-weight:700;font-size:28px;color:#fff">${s.member_count}</div>
                                        <div class="small text-muted text-uppercase" style="letter-spacing:.18em;font-size:10px">Members</div>
                                    </div>
                                    <div class="col-6">
                                        <div style="font-family:Cinzel,serif;font-weight:700;font-size:28px;color:var(--c-gold)">${s.role_count}</div>
                                        <div class="small text-muted text-uppercase" style="letter-spacing:.18em;font-size:10px">Roles</div>
                                    </div>
                                </div>
                            </div>
                        </div>`);
                });
            })
            .catch(err => toast(err.message, 'error'));
    }

    // -----------------------------------------------------------------------
    // MEMBER MODAL
    // -----------------------------------------------------------------------

    function refreshRoleOptions() {
        const sid = parseInt(document.getElementById('m_section').value, 10);
        const sel = document.getElementById('m_role');
        sel.innerHTML = '<option value="">—</option>';
        (REF.roles || [])
            .filter(r => !sid || parseInt(r.section_id, 10) === sid)
            .forEach(r => sel.insertAdjacentHTML('beforeend', `<option value="${r.id}">${escapeHtml(r.name)}</option>`));
    }

    function showHidePerformerType() {
        const sel = document.getElementById('m_section');
        const opt = sel.options[sel.selectedIndex];
        const code = opt ? opt.getAttribute('data-code') : null;
        document.getElementById('m_perf_wrap').style.display = code === 'performers' ? '' : 'none';
        if (code !== 'performers') document.getElementById('m_performer_type').value = '';
    }

    function showHideInsurance() {
        const on = document.getElementById('m_has_ins').value === '1';
        document.querySelectorAll('.ins-fields').forEach(el => el.style.display = on ? '' : 'none');
    }

    function openMemberModal(m) {
        document.getElementById('memberModalTitle').textContent = m ? 'Edit Member' : 'Register New Member';
        const f = document.getElementById('memberForm');
        f.reset();
        document.getElementById('m_id').value = m ? m.id : '';
        if (m) {
            const map = {
                m_full_name: 'full_name', m_national_id: 'national_id', m_gender: 'gender',
                m_dob: 'date_of_birth', m_phone: 'phone', m_email: 'email',
                m_akarere: 'akarere', m_umurenge: 'umurenge', m_akagari: 'akagari', m_umudugudu: 'umudugudu',
                m_em_name: 'emergency_name', m_em_phone: 'emergency_phone',
                m_section: 'section_id', m_performer_type: 'performer_type_id',
                m_role: 'role_id', m_category: 'category_id'
            };
            Object.entries(map).forEach(([id, key]) => {
                const el = document.getElementById(id);
                if (el && m[key] !== undefined && m[key] !== null) el.value = m[key];
            });
            document.getElementById('m_has_ins').value = m.has_insurance ? '1' : '0';
            if (m.has_insurance) {
                document.getElementById('m_ins_name').value   = m.insurance_name || 'Mutuelle de Santé';
                document.getElementById('m_ins_start').value  = m.insurance_start || '';
                document.getElementById('m_ins_expiry').value = m.insurance_expiry || '';
            }
        }
        showHidePerformerType();
        refreshRoleOptions();
        showHideInsurance();
        if (m) bindNationalIdAutofill('m_national_id', 'm_gender', 'm_dob', { allowGenderOverride: true });
    }

    document.getElementById('memberForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('m_id').value;
        const body = {
            full_name:        document.getElementById('m_full_name').value,
            national_id:      document.getElementById('m_national_id').value,
            gender:           document.getElementById('m_gender').value,
            date_of_birth:    document.getElementById('m_dob').value,
            phone:            document.getElementById('m_phone').value,
            email:            document.getElementById('m_email').value,
            akarere:          document.getElementById('m_akarere').value,
            umurenge:         document.getElementById('m_umurenge').value,
            akagari:          document.getElementById('m_akagari').value,
            umudugudu:        document.getElementById('m_umudugudu').value,
            emergency_name:   document.getElementById('m_em_name').value,
            emergency_phone:  document.getElementById('m_em_phone').value,
            section_id:       document.getElementById('m_section').value,
            performer_type_id:document.getElementById('m_performer_type').value || null,
            role_id:          document.getElementById('m_role').value || null,
            category_id:      document.getElementById('m_category').value,
            has_insurance:    document.getElementById('m_has_ins').value,
            insurance_name:   document.getElementById('m_ins_name').value,
            insurance_start:  document.getElementById('m_ins_start').value,
            insurance_expiry: document.getElementById('m_ins_expiry').value
        };
        try {
            await api(`members.php?op=${id ? 'update&id=' + id : 'create'}`, { method: 'POST', json: body });
            toast(id ? 'Member updated' : 'Member registered', 'success');
            bootstrap.Modal.getInstance(document.getElementById('memberModal')).hide();
            loadActive();
        } catch (err) {
            const msg = err.message === 'national_id_taken' ? 'A member with that National ID already exists.' :
                        err.message === 'missing_performer_type' ? 'Please pick a performer type.' :
                        err.message === 'role_section_mismatch' ? 'That role does not belong to the selected section.' :
                        'Save failed: ' + err.message;
            toast(msg, 'error');
        }
    });

    document.getElementById('btnNewMember').addEventListener('click', () => openMemberModal(null));
    document.getElementById('m_section').addEventListener('change', () => { showHidePerformerType(); refreshRoleOptions(); });
    document.getElementById('m_has_ins').addEventListener('change', showHideInsurance);

    // Wire National ID parser
    bindNationalIdAutofill('m_national_id', 'm_gender', 'm_dob', { allowGenderOverride: false });

    // -----------------------------------------------------------------------
    // CATEGORY MODAL
    // -----------------------------------------------------------------------

    function openCategoryModal(c) {
        document.getElementById('categoryModalTitle').textContent = c ? 'Edit Category' : 'New Category';
        document.getElementById('c_id').value     = c ? c.id : '';
        document.getElementById('c_name').value   = c ? c.name : '';
        document.getElementById('c_salary').value = c ? c.fixed_salary : 0;
        document.getElementById('c_desc').value   = c ? (c.description || '') : '';
        document.getElementById('c_status').value = c ? c.status : 'active';
    }

    document.getElementById('btnNewCategory').addEventListener('click', () => openCategoryModal(null));
    document.getElementById('categoryForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('c_id').value;
        const body = {
            name:         document.getElementById('c_name').value,
            fixed_salary: document.getElementById('c_salary').value,
            description:  document.getElementById('c_desc').value,
            status:       document.getElementById('c_status').value
        };
        try {
            await api(`categories.php?op=${id ? 'update&id=' + id : 'create'}`, { method: 'POST', json: body });
            toast('Category saved', 'success');
            bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
            loadCategories();
        } catch (err) {
            toast(err.message === 'name_taken' ? 'A category with that name already exists.' : 'Save failed: ' + err.message, 'error');
        }
    });

    // -----------------------------------------------------------------------
    // ROLE MODAL
    // -----------------------------------------------------------------------

    function openRoleModal(r) {
        document.getElementById('roleModalTitle').textContent = r ? 'Edit Role' : 'New Role';
        document.getElementById('r_id').value      = r ? r.id : '';
        document.getElementById('r_name').value    = r ? r.name : '';
        document.getElementById('r_section').value = r ? r.section_id : '';
        document.getElementById('r_desc').value    = r ? (r.description || '') : '';
    }

    document.getElementById('btnNewRole').addEventListener('click', () => openRoleModal(null));
    document.getElementById('roleForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('r_id').value;
        const body = {
            name:        document.getElementById('r_name').value,
            section_id:  document.getElementById('r_section').value,
            description: document.getElementById('r_desc').value
        };
        try {
            await api(`roles.php?op=${id ? 'update&id=' + id : 'create'}`, { method: 'POST', json: body });
            toast('Role saved', 'success');
            bootstrap.Modal.getInstance(document.getElementById('roleModal')).hide();
            loadRoles();
        } catch (err) {
            toast(err.message === 'name_taken_in_section' ? 'A role with that name already exists in that section.' : 'Save failed: ' + err.message, 'error');
        }
    });

    // -----------------------------------------------------------------------
    // RETIRE MODAL
    // -----------------------------------------------------------------------

    document.getElementById('retireForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('rt_id').value;
        try {
            await api(`members.php?op=retire&id=${id}`, {
                method: 'POST',
                json: {
                    date: document.getElementById('rt_date').value,
                    reason: document.getElementById('rt_reason').value,
                    note: document.getElementById('rt_note').value
                }
            });
            toast('Member moved to Inganzo Nkuru', 'success');
            bootstrap.Modal.getInstance(document.getElementById('retireModal')).hide();
            loadActive();
            loadNkuru();
        } catch (err) { toast('Retire failed: ' + err.message, 'error'); }
    });

    // -----------------------------------------------------------------------
    // EVENT DELEGATION (DataTables re-renders rows so we delegate on document)
    // -----------------------------------------------------------------------

    document.addEventListener('click', async (e) => {
        const t = e.target.closest('button, a');
        if (!t) return;

        // Edit member
        if (t.classList.contains('m-edit')) {
            const id = t.dataset.id;
            const res = await api(`members.php?op=get&id=${id}`).catch(err => toast(err.message, 'error'));
            if (!res) return;
            openMemberModal(res.data);
            new bootstrap.Modal(document.getElementById('memberModal')).show();
        }

        // Open retire modal
        if (t.classList.contains('m-retire')) {
            document.getElementById('rt_id').value   = t.dataset.id;
            document.getElementById('rt_name').textContent = t.dataset.name;
            new bootstrap.Modal(document.getElementById('retireModal')).show();
        }

        // Reactivate
        if (t.classList.contains('m-reactivate')) {
            const ok = await confirmAction(`Reactivate ${t.dataset.name} and bring them back to the active roster?`, { okText: 'Reactivate' });
            if (!ok) return;
            try {
                await api(`members.php?op=reactivate&id=${t.dataset.id}`, { method: 'POST', json: {} });
                toast('Member reactivated', 'success');
                loadActive(); loadNkuru();
            } catch (err) { toast(err.message, 'error'); }
        }

        // Edit category
        if (t.classList.contains('c-edit')) {
            const res = await api('categories.php?op=list');
            const c = res.data.find(x => +x.id === +t.dataset.id);
            if (c) {
                openCategoryModal(c);
                new bootstrap.Modal(document.getElementById('categoryModal')).show();
            }
        }

        // Delete category
        if (t.classList.contains('c-delete')) {
            const count = parseInt(t.dataset.count, 10) || 0;
            if (count > 0) { toast(`Cannot delete — ${count} members are still assigned to "${t.dataset.name}".`, 'error'); return; }
            const ok = await confirmAction(`Delete category "${t.dataset.name}"? This cannot be undone.`, { okText: 'Delete', danger: true });
            if (!ok) return;
            try {
                await api(`categories.php?op=delete&id=${t.dataset.id}`, { method: 'POST', json: {} });
                toast('Category deleted', 'success');
                loadCategories();
            } catch (err) { toast(err.message, 'error'); }
        }

        // Edit role
        if (t.classList.contains('r-edit')) {
            const res = await api('roles.php?op=list');
            const r = res.data.find(x => +x.id === +t.dataset.id);
            if (r) { openRoleModal(r); new bootstrap.Modal(document.getElementById('roleModal')).show(); }
        }

        // Delete role
        if (t.classList.contains('r-delete')) {
            const count = parseInt(t.dataset.count, 10) || 0;
            if (count > 0) { toast(`Cannot delete — ${count} members still hold the "${t.dataset.name}" role.`, 'error'); return; }
            const ok = await confirmAction(`Delete role "${t.dataset.name}"?`, { okText: 'Delete', danger: true });
            if (!ok) return;
            try {
                await api(`roles.php?op=delete&id=${t.dataset.id}`, { method: 'POST', json: {} });
                toast('Role deleted', 'success');
                loadRoles();
            } catch (err) { toast(err.message, 'error'); }
        }
    });

    // -----------------------------------------------------------------------
    // FILTERS
    // -----------------------------------------------------------------------

    ['filterSection', 'filterPerformer', 'filterRole', 'filterCategory'].forEach(id => {
        document.getElementById(id).addEventListener('change', loadActive);
    });
    document.getElementById('btnResetFilters').addEventListener('click', () => {
        ['filterSection', 'filterPerformer', 'filterRole', 'filterCategory'].forEach(id => document.getElementById(id).value = '');
        loadActive();
    });

    // -----------------------------------------------------------------------
    // TAB LAZY-LOAD
    // -----------------------------------------------------------------------

    const loaded = { active: false, nkuru: false, categories: false, roles: false, sections: false };
    function loadFor(tab) {
        if (loaded[tab]) return;
        loaded[tab] = true;
        if (tab === 'active')     loadActive();
        if (tab === 'nkuru')      loadNkuru();
        if (tab === 'categories') loadCategories();
        if (tab === 'roles')      loadRoles();
        if (tab === 'sections')   loadSections();
    }

    document.querySelectorAll('#memberTabs button').forEach(btn => {
        btn.addEventListener('shown.bs.tab', () => loadFor(btn.dataset.tab));
    });

    // Initial load for active tab (or whichever ?tab= the URL chose)
    loadFor(REF.activeTab || 'active');

    // =======================================================================
    // IMPORT FROM EXCEL / CSV
    // =======================================================================

    // Target fields the importer can map columns to.
    const IMPORT_FIELDS = [
        { value: '',                 label: '— Ignore —' },
        { value: 'full_name',        label: 'Full Name *' },
        { value: 'national_id',      label: 'National ID *' },
        { value: 'gender',           label: 'Gender (M/F)' },
        { value: 'date_of_birth',    label: 'Date of Birth' },
        { value: 'phone',            label: 'Phone' },
        { value: 'email',            label: 'Email' },
        { value: 'akarere',          label: 'Akarere (district)' },
        { value: 'umurenge',         label: 'Umurenge (sector)' },
        { value: 'akagari',          label: 'Akagari (cell)' },
        { value: 'umudugudu',        label: 'Umudugudu (village)' },
        { value: 'emergency_name',   label: 'Emergency Contact Name' },
        { value: 'emergency_phone',  label: 'Emergency Contact Phone' },
        { value: '_section',         label: 'Section (name or id)' },
        { value: '_performer_type',  label: 'Performer Type (name or id)' },
        { value: '_role',            label: 'Role (name or id)' },
        { value: '_category',        label: 'Category (name or id)' },
    ];

    // Header detection — fuzzy match (case-insensitive, accents stripped)
    const HEADER_PATTERNS = [
        [['full name','name','names','fullname','amazina','amazina yombi','izina','izina ryuzuye','nom','noms'], 'full_name'],
        [['national id','nationalid','nid','indangamuntu','indangamuntu nimero','id','identification','number id'], 'national_id'],
        [['gender','sex','igitsina','genre'], 'gender'],
        [['date of birth','dob','birth','itariki yamavuko','itariki yo kuvuka','date naissance','birthdate'], 'date_of_birth'],
        [['phone','phone number','telephone','telefone','tel','telefoni','nimero','nimero ya telefoni'], 'phone'],
        [['email','e-mail','mail','imeyili'], 'email'],
        [['akarere','district','akarere ke'], 'akarere'],
        [['umurenge','sector','sect'], 'umurenge'],
        [['akagari','cell','cellule'], 'akagari'],
        [['umudugudu','village'], 'umudugudu'],
        [['emergency','emergency name','contact','emergency contact','emergency contact name'], 'emergency_name'],
        [['emergency phone','emergency number','contact phone','contact number','emergency tel'], 'emergency_phone'],
        [['section','itsinda'], '_section'],
        [['performer type','type','performer','performertype','ubwoko'], '_performer_type'],
        [['role','inshingano','rol'], '_role'],
        [['category','icyiciro','cat'], '_category'],
    ];

    function norm(s) {
        return String(s || '').toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9 ]+/g, ' ')
            .replace(/\s+/g, ' ').trim();
    }

    function detectField(header) {
        const n = norm(header);
        for (const [keys, field] of HEADER_PATTERNS) {
            if (keys.some(k => n === k || n.startsWith(k + ' ') || n.endsWith(' ' + k) || n.includes(' ' + k + ' '))) {
                return field;
            }
        }
        // Looser secondary pass: substring match
        for (const [keys, field] of HEADER_PATTERNS) {
            if (keys.some(k => n.includes(k))) return field;
        }
        return '';
    }

    let importState = {
        headers: [],
        rows: [],            // raw arrays from SheetJS
        mapping: {},         // header_index -> field name
        sampleByHeader: []   // pretty value to show in mapping table
    };

    function resetImporter() {
        importState = { headers: [], rows: [], mapping: {}, sampleByHeader: [] };
        document.getElementById('imp-step-1').style.display = '';
        document.getElementById('imp-step-2').style.display = 'none';
        document.getElementById('imp-step-3').style.display = 'none';
        document.getElementById('imp-back').style.display   = 'none';
        document.getElementById('imp-import').disabled       = true;
        document.getElementById('imp-import').innerHTML      = '<i class="fa-solid fa-cloud-arrow-up me-1"></i> Import';
        document.getElementById('imp-filename').textContent = '';
        document.getElementById('imp-file').value           = '';
    }

    // Hide/show performer-type default based on section
    document.getElementById('imp-def-section').addEventListener('change', () => {
        const sel = document.getElementById('imp-def-section');
        const opt = sel.options[sel.selectedIndex];
        document.getElementById('imp-def-perf-wrap').style.display =
            opt && opt.dataset.code === 'performers' ? '' : 'none';
    });

    document.getElementById('importModal').addEventListener('show.bs.modal', resetImporter);

    document.getElementById('imp-file').addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        if (typeof XLSX === 'undefined') {
            toast('Excel library not yet loaded — reload the page and try again.', 'error');
            return;
        }
        document.getElementById('imp-filename').textContent = file.name;

        const reader = new FileReader();
        reader.onload = (evt) => {
            try {
                const data  = new Uint8Array(evt.target.result);
                const wb    = XLSX.read(data, { type: 'array', cellDates: true });
                const sheet = wb.Sheets[wb.SheetNames[0]];
                const rows  = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, defval: '' });
                if (rows.length < 2) { toast('File looks empty.', 'error'); return; }

                importState.headers = rows[0].map((h) => String(h || '').trim());
                importState.rows    = rows.slice(1).filter(r => r.some(c => String(c || '').trim() !== ''));

                // Auto-map
                importState.mapping = {};
                importState.headers.forEach((h, idx) => { importState.mapping[idx] = detectField(h); });

                // Pretty sample per header
                importState.sampleByHeader = importState.headers.map((_, idx) => {
                    const sample = importState.rows.slice(0, 3).map(r => String(r[idx] || '')).find(v => v) || '';
                    return sample.length > 40 ? sample.slice(0, 40) + '…' : sample;
                });

                renderMappingTable();
                renderPreview();

                document.getElementById('imp-step-1').style.display = 'none';
                document.getElementById('imp-step-2').style.display = '';
                document.getElementById('imp-back').style.display    = '';
                document.getElementById('imp-import').disabled       = false;
            } catch (err) {
                toast('Failed to read file: ' + err.message, 'error');
            }
        };
        reader.readAsArrayBuffer(file);
    });

    document.getElementById('imp-back').addEventListener('click', () => {
        document.getElementById('imp-step-1').style.display = '';
        document.getElementById('imp-step-2').style.display = 'none';
        document.getElementById('imp-back').style.display    = 'none';
        document.getElementById('imp-import').disabled       = true;
    });

    function renderMappingTable() {
        const tbody = document.querySelector('#imp-mapping-table tbody');
        tbody.innerHTML = '';
        importState.headers.forEach((h, idx) => {
            const tr = document.createElement('tr');
            const opts = IMPORT_FIELDS.map(f =>
                `<option value="${f.value}" ${f.value === importState.mapping[idx] ? 'selected' : ''}>${escapeHtml(f.label)}</option>`
            ).join('');
            tr.innerHTML = `
                <td style="font-weight:600">${escapeHtml(h)}</td>
                <td><select class="form-select form-select-sm imp-map" data-idx="${idx}">${opts}</select></td>
                <td class="text-muted small">${escapeHtml(importState.sampleByHeader[idx])}</td>`;
            tbody.appendChild(tr);
        });
        tbody.querySelectorAll('.imp-map').forEach(sel => {
            sel.addEventListener('change', () => {
                importState.mapping[+sel.dataset.idx] = sel.value;
                renderPreview();
            });
        });
    }

    function buildRow(raw) {
        const out = {};
        for (const [idx, field] of Object.entries(importState.mapping)) {
            if (!field) continue;
            const v = String(raw[+idx] ?? '').trim();
            if (v === '') continue;
            // Defer name->id translation for lookup fields to the next step
            out[field] = v;
        }
        return out;
    }

    function renderPreview() {
        const tbody = document.querySelector('#imp-preview-table tbody');
        tbody.innerHTML = '';
        let ok = 0, missing = 0;
        importState.rows.slice(0, 5).forEach((raw, i) => {
            const r = buildRow(raw);
            const issues = [];
            if (!r.full_name)   issues.push('full name');
            if (!r.national_id) issues.push('national id');
            const valid = issues.length === 0;
            if (valid) ok++; else missing++;
            tbody.insertAdjacentHTML('beforeend', `
                <tr>
                    <td>${i + 1}</td>
                    <td>${escapeHtml(r.full_name || '—')}</td>
                    <td>${escapeHtml(r.national_id || '—')}</td>
                    <td>${escapeHtml(r.gender || '—')}</td>
                    <td>${escapeHtml(r.phone || '—')}</td>
                    <td>${valid ? '<span class="chip chip-active"><span class="dot"></span>OK</span>'
                                : '<span class="chip chip-danger"><span class="dot"></span>missing ' + issues.join(', ') + '</span>'}</td>
                </tr>`);
        });

        // Full file stats
        let fileOk = 0, fileMissing = 0;
        importState.rows.forEach(raw => {
            const r = buildRow(raw);
            if (r.full_name && r.national_id) fileOk++; else fileMissing++;
        });
        document.getElementById('imp-summary').innerHTML =
            `<b>${importState.rows.length}</b> data rows · <span style="color:#7fd2a3">${fileOk} ready to import</span>` +
            (fileMissing > 0 ? ` · <span style="color:#ffb4ad">${fileMissing} missing required fields (will still try — placeholders applied)</span>` : '');
    }

    // Translate text labels for Section/Performer Type/Role/Category to IDs.
    function resolveLookups(rowObj) {
        const out = Object.assign({}, rowObj);
        const findByName = (list, name) => {
            if (!name) return null;
            const n = norm(name);
            const m = list.find(x => norm(x.name) === n || String(x.id) === String(name));
            return m ? +m.id : null;
        };
        if (out._section)        { out.section_id        = findByName(REF.sections,        out._section);        delete out._section; }
        if (out._performer_type) { out.performer_type_id = findByName(REF.performer_types, out._performer_type); delete out._performer_type; }
        if (out._role)           { out.role_id           = findByName(REF.roles,           out._role);           delete out._role; }
        if (out._category)       { out.category_id       = findByName(REF.categories,      out._category);       delete out._category; }
        // Normalise gender
        if (out.gender) {
            const g = norm(out.gender).charAt(0);
            out.gender = (g === 'f' || g === 'g') ? 'F' : (g === 'm' || g === 'h') ? 'M' : '';
        }
        return out;
    }

    document.getElementById('imp-import').addEventListener('click', async () => {
        const btn = document.getElementById('imp-import');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Importing…';

        const defaults = {
            section_id:        +document.getElementById('imp-def-section').value || null,
            performer_type_id: +document.getElementById('imp-def-performer').value || null,
            category_id:       +document.getElementById('imp-def-category').value || null,
            akarere:           document.getElementById('imp-def-akarere').value.trim() || null,
        };

        const rows = importState.rows.map(r => resolveLookups(buildRow(r)));

        try {
            const res = await api('members.php?op=import', { method: 'POST', json: { defaults, rows } });

            document.getElementById('imp-step-2').style.display = 'none';
            document.getElementById('imp-step-3').style.display = '';
            document.getElementById('imp-back').style.display = 'none';
            btn.style.display = 'none';

            document.getElementById('imp-result-line').innerHTML =
                `<b style="color:#7fd2a3">${res.imported}</b> imported, ` +
                `<b style="color:#ffb4ad">${res.skipped}</b> skipped, ` +
                `${res.total} total.`;

            if (res.errors && res.errors.length) {
                document.getElementById('imp-errors-wrap').style.display = '';
                document.querySelector('#imp-errors-table tbody').innerHTML = res.errors.map(e => `
                    <tr>
                        <td>${e.row_index}</td>
                        <td>${escapeHtml(e.full_name || '—')}</td>
                        <td><span class="chip chip-danger"><span class="dot"></span>${escapeHtml(e.message)}</span></td>
                    </tr>`).join('');
            }

            toast(`Imported ${res.imported} members`, 'success');
            // Refresh the active table behind the modal
            loaded.active = false; loadFor('active');
        } catch (err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up me-1"></i> Import';
            toast('Import failed: ' + err.message, 'error');
        }
    });

})();
