/* ===========================================================================
 * Inganzo Ngari Mgmt — Dashboard analytics
 * ======================================================================== */

(function () {
    'use strict';
    const { api, toast, formatRwf, escapeHtml } = window.app;

    Chart.defaults.color = 'rgba(245,230,200,.7)';
    Chart.defaults.borderColor = 'rgba(245,230,200,.08)';
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';

    const GOLD = '#e9b962', GOLD_BRIGHT = '#f3d27a', GOLD_DEEP = '#b8862e';
    const PERFORMER_COLORS = { Indende: '#9bd0f5', Abaterambabazi: '#ffb4ad', Inyamamare: '#7fd2a3' };

    function initials(name) {
        return (name || '?').split(/\s+/).slice(0, 2).map(s => s[0]).join('').toUpperCase();
    }
    function avatar(name, photoPath) {
        if (photoPath) return `<img src="${escapeHtml(photoPath)}" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:1px solid var(--c-line);flex-shrink:0">`;
        return `<span class="d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#f3d27a,#b8862e);color:#061410;font-weight:700;font-size:11px;flex-shrink:0">${escapeHtml(initials(name))}</span>`;
    }
    function rowItem(content) {
        return `<li class="d-flex align-items-center gap-2 py-2 border-bottom" style="border-color:var(--c-line) !important">${content}</li>`;
    }

    async function load() {
        try {
            const a = await api('analytics.php?op=overview');

            // KPI numbers
            document.querySelector('[data-kpi="active"]').textContent      = a.totals.active;
            document.querySelector('[data-kpi="indende"]').textContent     = a.totals.indende;
            document.querySelector('[data-kpi="abater"]').textContent      = a.totals.abaterambabazi;
            document.querySelector('[data-kpi="inyamamare"]').textContent  = a.totals.inyamamare;
            document.querySelector('[data-kpi="newThisMonth"]').textContent = (a.new_this_month > 0 ? '+ ' : '') + a.new_this_month + ' this month';
            document.querySelector('[data-kpi="breakdown"]').textContent   = `${a.totals.leaders} leaders · ${a.totals.indende + a.totals.abaterambabazi + a.totals.inyamamare} performers · ${a.totals.support} support`;

            // Category chart (bar)
            const catCtx = document.getElementById('chartCategory').getContext('2d');
            const gradGold = catCtx.createLinearGradient(0, 0, 0, 280);
            gradGold.addColorStop(0, 'rgba(243,210,122,.7)');
            gradGold.addColorStop(1, 'rgba(184,134,46,.2)');
            new Chart(catCtx, {
                type: 'bar',
                data: {
                    labels: a.by_category.map(c => c.name),
                    datasets: [{
                        data: a.by_category.map(c => c.member_count),
                        backgroundColor: gradGold,
                        borderColor: GOLD,
                        borderWidth: 1.5,
                        borderRadius: 6
                    }]
                },
                options: chartOpts(true, true)
            });
            document.getElementById('cat-total').textContent = `Total ${a.totals.active} members · payroll ${formatRwf(a.by_category.reduce((s, c) => s + Number(c.monthly_total || 0), 0))} Rwf/month`;

            // Performer chart (doughnut)
            const perfCtx = document.getElementById('chartPerformer').getContext('2d');
            new Chart(perfCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Indende', 'Abaterambabazi', 'Inyamamare'],
                    datasets: [{
                        data: [a.totals.indende, a.totals.abaterambabazi, a.totals.inyamamare],
                        backgroundColor: [PERFORMER_COLORS.Indende, PERFORMER_COLORS.Abaterambabazi, PERFORMER_COLORS.Inyamamare],
                        borderColor: 'rgba(6,20,16,1)',
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 } } },
                        tooltip: tooltipOpts()
                    }
                }
            });

            // Insurance alerts list
            const ins = document.getElementById('insAlerts');
            if (a.insurance_alerts.length === 0) {
                ins.innerHTML = '<li class="text-muted small py-2">No insurance alerts. Everyone is covered.</li>';
            } else {
                ins.innerHTML = a.insurance_alerts.map(m => {
                    const daysLeft = m.insurance_expiry ? Math.floor((new Date(m.insurance_expiry) - new Date()) / 86400000) : null;
                    const chip = m.insurance_status === 'expired'
                        ? `<span class="chip chip-danger" style="margin-left:auto"><span class="dot"></span>${Math.abs(daysLeft||0)}d ago</span>`
                        : `<span class="chip chip-warn"  style="margin-left:auto"><span class="dot"></span>in ${daysLeft||0}d</span>`;
                    return rowItem(`
                        ${avatar(m.full_name, m.photo_path)}
                        <a href="${APP.base}/profile.php?id=${m.id}" style="text-decoration:none;color:var(--c-ink);font-size:13px;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeHtml(m.full_name)}</a>
                        ${chip}`);
                }).join('');
            }

            // Upcoming birthdays
            const bd = document.getElementById('birthdays');
            if (a.upcoming_birthdays.length === 0) {
                bd.innerHTML = '<li class="text-muted small py-2">No birthdays in the data.</li>';
            } else {
                bd.innerHTML = a.upcoming_birthdays.map(m => {
                    const d = new Date(m.date_of_birth);
                    const md = d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
                    const tag = m.days_to_birthday == 0 ? 'TODAY' : `in ${m.days_to_birthday}d`;
                    return rowItem(`
                        ${avatar(m.full_name, m.photo_path)}
                        <a href="${APP.base}/profile.php?id=${m.id}" style="text-decoration:none;color:var(--c-ink);font-size:13px;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeHtml(m.full_name)}<div class="small text-muted">${md}</div></a>
                        <span class="chip ${m.days_to_birthday == 0 ? 'chip-warn' : 'chip-muted'}" style="margin-left:auto"><span class="dot"></span>${tag}</span>`);
                }).join('');
            }

            // New members
            const nm = document.getElementById('newMembers');
            if (a.new_members.length === 0) {
                nm.innerHTML = '<li class="text-muted small py-2">No members yet.</li>';
            } else {
                nm.innerHTML = a.new_members.map(m => rowItem(`
                    ${avatar(m.full_name, m.photo_path)}
                    <a href="${APP.base}/profile.php?id=${m.id}" style="text-decoration:none;color:var(--c-ink);font-size:13px;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeHtml(m.full_name)}<div class="small text-muted">${new Date(m.joined_date).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' })}</div></a>`)).join('');
            }

            // Roles distribution (horizontal bar)
            const filtered = a.by_role.filter(r => r.member_count > 0);
            const rolesCtx = document.getElementById('chartRoles').getContext('2d');
            new Chart(rolesCtx, {
                type: 'bar',
                data: {
                    labels: filtered.map(r => r.name),
                    datasets: [{
                        data: filtered.map(r => r.member_count),
                        backgroundColor: filtered.map((_, i) => i % 2 === 0 ? 'rgba(233,185,98,.65)' : 'rgba(243,210,122,.45)'),
                        borderColor: GOLD,
                        borderWidth: 1.5,
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: tooltipOpts() },
                    scales: {
                        x: { grid: { color: 'rgba(245,230,200,.05)' }, ticks: { precision: 0 } },
                        y: { grid: { display: false } }
                    }
                }
            });

        } catch (err) {
            toast('Failed to load dashboard: ' + err.message, 'error');
        }
    }

    function tooltipOpts() {
        return {
            backgroundColor: 'rgba(10,42,31,.95)',
            titleColor: '#f5e6c8',
            bodyColor: '#f5e6c8',
            borderColor: 'rgba(233,185,98,.4)',
            borderWidth: 1,
            padding: 10,
            cornerRadius: 8
        };
    }
    function chartOpts(showX, showY) {
        return {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: tooltipOpts() },
            scales: {
                x: { display: showX, grid: { display: false } },
                y: { display: showY, grid: { color: 'rgba(245,230,200,.05)' }, ticks: { precision: 0 } }
            }
        };
    }

    load();
})();
