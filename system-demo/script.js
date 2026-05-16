/* ============================================================
 * Inganzo Ngari — Management System (Demo)
 * Mock data, role switcher, chart rendering, tiny interactions.
 * ============================================================ */

/* ---------- Sidebar nav config ---------- */
const NAV_GROUPS = [
  {
    label: "Overview",
    items: [
      { icon: "layout-dashboard", label: "Dashboard", active: true },
      { icon: "calendar-days",    label: "Calendar" },
      { icon: "cake",             label: "Birthdays" },
    ],
  },
  {
    label: "People",
    items: [
      { icon: "users",            label: "Members",   badge: "87" },
      { icon: "user-plus",        label: "Recruits",  badge: "5" },
      { icon: "shield-check",     label: "Insurance", badge: "!", badgeKind: "warn" },
      { icon: "history",          label: "Ex Inganzo (Nkuru)" },
    ],
  },
  {
    label: "Training & Stage",
    items: [
      { icon: "graduation-cap",   label: "Training Sessions" },
      { icon: "list-checks",      label: "Attendance" },
      { icon: "scale",            label: "Discipline" },
      { icon: "alert-triangle",   label: "Infractions Catalog" },
      { icon: "music-2",          label: "Repertoire" },
      { icon: "drum",             label: "Shows & Bookings", badge: "6" },
      { icon: "user-check",       label: "Performer Selection" },
    ],
  },
  {
    label: "Finance",
    items: [
      { icon: "wallet",           label: "Payroll", badge: "Due 1 Jun" },
      { icon: "hand-coins",       label: "Loans",   badge: "7" },
      { icon: "piggy-bank",       label: "Kuzigama" },
      { icon: "heart-handshake",  label: "Welfare Fund" },
      { icon: "receipt",          label: "Expenses" },
      { icon: "trending-up",      label: "Income" },
    ],
  },
  {
    label: "Logistics",
    items: [
      { icon: "package",          label: "Inventory" },
      { icon: "shopping-cart",    label: "Procurement" },
      { icon: "file-text",        label: "Documents" },
    ],
  },
  {
    label: "System",
    items: [
      { icon: "bar-chart-3",      label: "Reports" },
      { icon: "search",           label: "Audit Log" },
      { icon: "settings",         label: "Settings" },
    ],
  },
];

/* ---------- Role config ---------- */
const ROLES = [
  { id: "boss",          label: "Boss" },
  { id: "admin",         label: "Admin + Finance" },
  { id: "overall",       label: "Overall Trainer" },
  { id: "trainer",       label: "Trainer" },
  { id: "discipliner",   label: "Discipliner" },
  { id: "umuvugizi",     label: "Umuvugizi" },
  { id: "member",        label: "Member" },
];

/* ---------- KPI sets per role ---------- */
const KPI_BY_ROLE = {
  boss: [
    { icon: "users",         label: "Active Staff",        value: "87",  unit: "members", delta: "+3 this month",       deltaKind: "up", note: "61 dancers · 8 musicians · 18 support" },
    { icon: "trending-up",   label: "May Revenue",         value: "4.25", unit: "M Rwf",  delta: "+12% vs Apr",         deltaKind: "up", note: "8 paid shows · 1 sponsorship" },
    { icon: "drum",          label: "Upcoming Shows",      value: "6",   unit: "next 30 days", delta: "Next · Fri BK Arena", deltaKind: "flat", note: "Total billed Rwf 6.55 M" },
    { icon: "alert-circle",  label: "Pending Approvals",   value: "4",   unit: "items",   delta: "2 loans · 1 expense · 1 recruit", deltaKind: "flat", note: "Oldest waiting 2 days" },
  ],
  admin: [
    { icon: "wallet",        label: "Next Pay Run",        value: "1 Jun", unit: "in 16 days", delta: "Draft ready",      deltaKind: "up",   note: "Total est. Rwf 3.18 M" },
    { icon: "trending-up",   label: "May Revenue",         value: "4.25", unit: "M Rwf",  delta: "+12% vs Apr",         deltaKind: "up", note: "vs Rwf 3.79 M expenses" },
    { icon: "hand-coins",    label: "Outstanding Loans",   value: "1.85", unit: "M Rwf",  delta: "7 active loans",       deltaKind: "flat", note: "5 within 3× cap · 2 close to cap" },
    { icon: "alert-circle",  label: "Approvals Queue",     value: "4",   unit: "items",   delta: "+2 since yesterday",   deltaKind: "down", note: "Oldest waiting 2 days" },
  ],
  overall: [
    { icon: "drum",          label: "Upcoming Shows",      value: "6",   unit: "events",  delta: "3 need selection",     deltaKind: "warn", note: "Next deadline · Wed 20 May" },
    { icon: "user-check",    label: "Eligible Dancers",    value: "54",  unit: "of 61",   delta: "7 ineligible this wk", deltaKind: "flat", note: "Missed training or insurance" },
    { icon: "user-plus",     label: "Recruits in Eval.",   value: "5",   unit: "ready",   delta: "All complete 6mo",     deltaKind: "up",   note: "Schedule evaluation" },
    { icon: "list-checks",   label: "Group Attendance",    value: "91",  unit: "%",       delta: "+2 pts vs last week",  deltaKind: "up",   note: "Last 30 days" },
  ],
  trainer: [
    { icon: "users",         label: "My Group",            value: "18",  unit: "Indende", delta: "All active",           deltaKind: "up",   note: "3 Lead · 6 Adv · 7 Int · 2 Beg" },
    { icon: "list-checks",   label: "This Week Attendance",value: "92",  unit: "%",       delta: "+5 pts vs last wk",    deltaKind: "up",   note: "Tue 18/18 · Thu 15/18" },
    { icon: "user-plus",     label: "My Recruits",         value: "2",   unit: "probation", delta: "Eval in 28 days",    deltaKind: "flat", note: "P. Iradukunda · O. Manzi" },
    { icon: "drum",          label: "Selected for Shows",  value: "12",  unit: "dancers", delta: "BK Arena · Fri",       deltaKind: "up",   note: "from my group of 18" },
  ],
  discipliner: [
    { icon: "shield-alert",  label: "Insurance Expiring",  value: "5",   unit: "≤ 30 days", delta: "1 in 3 days",       deltaKind: "down", note: "0 expired · 5 alerts" },
    { icon: "alert-triangle",label: "Infractions (week)",  value: "11",  unit: "events",  delta: "−4 vs last wk",        deltaKind: "up",   note: "Mostly lateness" },
    { icon: "user-x",        label: "Auto-Suspended",      value: "1",   unit: "member",  delta: "Triggered Wed",        deltaKind: "down", note: "Eric T. · 4 missed in a row" },
    { icon: "list-checks",   label: "Attendance Gaps",     value: "7",   unit: "members", delta: "Inyamamare: 3",        deltaKind: "flat", note: "Inv. with Trainers" },
  ],
  umuvugizi: [
    { icon: "drum",          label: "Confirmed Shows",     value: "6",   unit: "events",  delta: "Rwf 6.55 M total",     deltaKind: "up",   note: "Next 30 days" },
    { icon: "inbox",         label: "New Inquiries",       value: "3",   unit: "leads",   delta: "from website",         deltaKind: "up",   note: "Awaiting your reply" },
    { icon: "user-check",    label: "Repeat Clients",      value: "12",  unit: "clients", delta: "+2 this year",         deltaKind: "up",   note: "55% of bookings" },
    { icon: "calendar-clock",label: "Avg Lead Time",       value: "14",  unit: "days",    delta: "Healthy",              deltaKind: "flat", note: "Target ≥ 10 days" },
  ],
  member: [
    { icon: "calendar-clock",label: "Next Training",       value: "Tue", unit: "7:00 PM", delta: "in 3 days",            deltaKind: "flat", note: "HQ · Studio A" },
    { icon: "drum",          label: "Selected for",        value: "Fri", unit: "BK Arena", delta: "Call time 5:30 PM",   deltaKind: "up",   note: "City of Kigali show" },
    { icon: "wallet",        label: "Est. Next Payslip",   value: "85",  unit: "K Rwf",   delta: "1 Jun",                deltaKind: "up",   note: "2 shows · 100% attendance" },
    { icon: "piggy-bank",    label: "My Kuzigama",         value: "240", unit: "K Rwf",   delta: "+5K this month",       deltaKind: "up",   note: "No active loan" },
  ],
};

/* ---------- Greeting per role ---------- */
const GREETING_BY_ROLE = {
  boss:        { eyebrow: "Murakaza neza",          title: "Welcome back, Boss",          sub: "Here's everything happening across Inganzo Ngari today." },
  admin:       { eyebrow: "Hello, Admin + Finance", title: "Pay run is in 16 days",       sub: "Your draft is ready and 4 items are awaiting your approval." },
  overall:     { eyebrow: "Hello, Overall Trainer", title: "3 shows still need a cast",   sub: "Pick performers for the upcoming shows from those eligible." },
  trainer:     { eyebrow: "Hello, Trainer",         title: "Indende · Group of 18",       sub: "92% attendance this week. 12 of your dancers are selected for Friday." },
  discipliner: { eyebrow: "Hello, Discipliner",     title: "5 insurance alerts open",     sub: "1 member is auto-suspended. 11 infractions logged this week." },
  umuvugizi:   { eyebrow: "Hello, Umuvugizi",       title: "3 new booking inquiries",     sub: "From the website. Reply, confirm or decline from the Shows module." },
  member:      { eyebrow: "Murakaza neza, Patrick", title: "You're on for Friday",        sub: "BK Arena · 5:30 PM call. Estimated payslip Rwf 85,000 on 1 June." },
};

/* ---------- Mock data ---------- */
const UPCOMING_SHOWS = [
  { date: { d: "22", m: "May" }, venue: "BK Arena",                   client: "City of Kigali",        fee: "1,200,000" },
  { date: { d: "30", m: "May" }, venue: "Kigali Convention Centre",   client: "MTN Rwanda",            fee: "800,000" },
  { date: { d: "06", m: "Jun" }, venue: "Camp Kigali",                client: "Inganzo Ngari (own)",   fee: "—" },
  { date: { d: "13", m: "Jun" }, venue: "Hotel Serena",               client: "BK Group · Wedding",    fee: "600,000" },
  { date: { d: "19", m: "Jun" }, venue: "Marriott Hotel",             client: "Diplomatic reception",  fee: "950,000" },
  { date: { d: "28", m: "Jun" }, venue: "Amahoro Stadium",            client: "National celebration",  fee: "2,000,000" },
];

const TOP_PERFORMERS = [
  { name: "Jean Bosco Habiyaremye", tags: "Indende · Lead",          count: 24, gold: true },
  { name: "Aline Uwase",            tags: "Abaterambabazi · Lead",   count: 22, gold: true },
  { name: "Patrick Niyonsenga",     tags: "Inyamamare · Drummer",    count: 21, gold: true },
  { name: "Diane Mukamana",         tags: "Abaterambabazi · Adv.",   count: 20 },
  { name: "Eric Twagirayezu",       tags: "Indende · Advanced",      count: 19 },
  { name: "Sandrine Ingabire",      tags: "Inyamamare · Singer",     count: 18 },
  { name: "Patrick Iradukunda",     tags: "Indende · Lead",          count: 17 },
  { name: "Claudine Umuhoza",       tags: "Abaterambabazi · Adv.",   count: 16 },
  { name: "Olivier Manzi",          tags: "Inyamamare · Drummer",    count: 15 },
  { name: "Yvette Mutoni",          tags: "Abaterambabazi · Int.",   count: 15 },
];

const INSURANCE_EXPIRING = [
  { name: "Aline Uwase",         days: 3,  kind: "danger" },
  { name: "Olivier Manzi",       days: 11, kind: "warn" },
  { name: "Patrick Niyonsenga",  days: 19, kind: "warn" },
  { name: "Diane Mukamana",      days: 22, kind: "warn" },
  { name: "Eric Twagirayezu",    days: 28, kind: "warn" },
];

const APPROVALS = [
  { type: "loan",    typeLabel: "Loan request",  who: "Patrick Iradukunda",       amount: "180,000 Rwf", when: "2 days ago" },
  { type: "expense", typeLabel: "Expense",       who: "Costume cleaning · Vendor", amount: "75,000 Rwf",  when: "Yesterday" },
  { type: "expense", typeLabel: "Expense",       who: "Sound system rental",      amount: "240,000 Rwf", when: "Yesterday" },
  { type: "recruit", typeLabel: "Recruit confirm", who: "Sandrine Ingabire (eval)", amount: "—",         when: "Today" },
];

const ACTIVITY = [
  { when: "14:32", html: "<b>Admin</b> updated payroll <i>May period 2</i> · 18 lines · Rwf 1.65 M <span class='tag'>PAYROLL</span>" },
  { when: "13:18", html: "<b>Discipliner</b> fined <b>Eric Twagirayezu</b> <span class='tag'>LATE-60 · −2,000</span>" },
  { when: "12:05", html: "<b>Overall Trainer</b> selected 18 performers for <b>BK Arena · Fri</b>" },
  { when: "11:42", html: "<b>Yvette Mutoni</b> uploaded new <i>Mutuelle</i> card <span class='tag'>DOC</span>" },
  { when: "10:15", html: "<b>Admin</b> marked Show <i>MTN Launch</i> as PAID · Rwf 4.25 M received" },
  { when: "09:50", html: "<b>Trainer Jean</b> marked attendance · 16 / 18 present" },
];

/* ---------- Renderers ---------- */
function svgIcon(name) {
  return `<i data-lucide="${name}"></i>`;
}

function renderSidebar() {
  const el = document.getElementById("sidebar-nav");
  el.innerHTML = NAV_GROUPS.map(group => `
    <div>
      <div class="nav-group-label">${group.label}</div>
      <div class="space-y-0.5">
        ${group.items.map(it => `
          <a class="nav-item ${it.active ? "is-active" : ""}" data-mod="${it.label}">
            ${svgIcon(it.icon)}
            <span class="truncate">${it.label}</span>
            ${it.badge
              ? `<span class="nav-badge${it.badgeKind === "warn" ? " !bg-err/20 !text-[#ffb4ad] !border-err/40" : ""}">${it.badge}</span>`
              : (it.active ? "" : `<span class="soon">v1</span>`)
            }
          </a>
        `).join("")}
      </div>
    </div>
  `).join("");
  // Click handler for sidebar items (cosmetic — toast only)
  el.querySelectorAll(".nav-item").forEach(a => {
    a.addEventListener("click", e => {
      e.preventDefault();
      if (a.classList.contains("is-active")) return;
      el.querySelectorAll(".nav-item").forEach(n => n.classList.remove("is-active"));
      // Don't actually navigate in the demo — just visually highlight + toast
      a.classList.add("is-active");
      const name = a.getAttribute("data-mod");
      toast(`${name} module will be built in v1`, "construction");
      // Snap back to Dashboard after a moment so the Boss view stays the focus.
      setTimeout(() => {
        el.querySelectorAll(".nav-item").forEach(n => n.classList.remove("is-active"));
        el.querySelector('[data-mod="Dashboard"]').classList.add("is-active");
        lucide.createIcons();
      }, 1300);
    });
  });
}

function renderRoleSwitch() {
  const el = document.getElementById("role-switch");
  el.innerHTML = ROLES.map(r => `<button class="role-pill ${r.id === "boss" ? "is-active" : ""}" data-role="${r.id}">${r.label}</button>`).join("");
  el.querySelectorAll(".role-pill").forEach(btn => {
    btn.addEventListener("click", () => {
      el.querySelectorAll(".role-pill").forEach(b => b.classList.remove("is-active"));
      btn.classList.add("is-active");
      applyRole(btn.getAttribute("data-role"));
    });
  });
}

function renderKPIs(roleId) {
  const grid = document.getElementById("kpi-grid");
  const kpis = KPI_BY_ROLE[roleId] || KPI_BY_ROLE.boss;
  grid.innerHTML = kpis.map(k => `
    <div class="kpi">
      <div class="icon">${svgIcon(k.icon)}</div>
      <p class="label">${k.label}</p>
      <p class="value">${k.value}<span class="unit">${k.unit}</span></p>
      <span class="delta ${k.deltaKind === 'down' ? 'down' : k.deltaKind === 'up' ? 'up' : 'flat'}">
        ${svgIcon(k.deltaKind === 'down' ? 'arrow-down-right' : k.deltaKind === 'up' ? 'arrow-up-right' : 'minus')}
        ${k.delta}
      </span>
      <p class="footnote">${k.note}</p>
    </div>
  `).join("");
}

function renderGreeting(roleId) {
  const g = GREETING_BY_ROLE[roleId] || GREETING_BY_ROLE.boss;
  document.getElementById("hello-eyebrow").textContent = g.eyebrow;
  document.getElementById("hello-title").textContent = g.title;
  document.getElementById("hello-sub").textContent = g.sub;
}

function renderUpcomingShows() {
  const ul = document.getElementById("shows-list");
  ul.innerHTML = UPCOMING_SHOWS.map(s => `
    <li class="show-row">
      <div class="date-chip">
        <div class="day">${s.date.d}</div>
        <div class="mon">${s.date.m}</div>
      </div>
      <div class="meta">
        <div class="venue">${s.venue}</div>
        <div class="client">${s.client}</div>
      </div>
      <div class="fee">${s.fee === "—" ? "—" : "Rwf " + s.fee}</div>
    </li>
  `).join("");
}

function renderTopPerformers() {
  const ul = document.getElementById("top-performers");
  ul.innerHTML = TOP_PERFORMERS.map((p, i) => {
    const initials = p.name.split(" ").slice(0, 2).map(n => n[0]).join("");
    return `
      <li class="perf-row ${p.gold ? "gold" : ""}">
        <div class="rank">${i + 1}</div>
        <div class="who">
          <span class="avatar">${initials}</span>
          <div class="name-meta">
            <div class="nm">${p.name}</div>
            <div class="tags">${p.tags}</div>
          </div>
        </div>
        <div class="count">${p.count}<span class="lbl">shows</span></div>
      </li>
    `;
  }).join("");
}

function renderInsurance() {
  const ul = document.getElementById("insurance-list");
  ul.innerHTML = INSURANCE_EXPIRING.map(i => {
    const initials = i.name.split(" ").slice(0, 2).map(n => n[0]).join("");
    return `
      <li class="ins-row">
        <div class="av">${initials}</div>
        <div class="ins-name">${i.name}</div>
        <div class="ins-days ${i.kind}">${i.days} day${i.days === 1 ? "" : "s"}</div>
      </li>
    `;
  }).join("");
}

function renderApprovals() {
  const tbody = document.getElementById("approvals-body");
  tbody.innerHTML = APPROVALS.map(a => `
    <tr>
      <td><span class="type-chip ${a.type}">${svgIcon(a.type === "loan" ? "hand-coins" : a.type === "expense" ? "receipt" : "user-plus")}${a.typeLabel}</span></td>
      <td class="text-ink-soft">${a.who}</td>
      <td class="text-ink font-medium">${a.amount}</td>
      <td class="text-muted text-xs">${a.when}</td>
      <td class="text-right">
        <button class="act-btn deny mr-2">Decline</button>
        <button class="act-btn">Approve</button>
      </td>
    </tr>
  `).join("");
  tbody.querySelectorAll(".act-btn").forEach(b => {
    b.addEventListener("click", () => {
      const isDeny = b.classList.contains("deny");
      toast(isDeny ? "Demo: decision would be recorded in v1" : "Demo: approval would be recorded in v1",
            isDeny ? "x" : "check");
    });
  });
}

function renderActivity() {
  const ul = document.getElementById("activity-list");
  ul.innerHTML = ACTIVITY.map(a => `
    <li class="act-row">
      <span class="when">${a.when}</span>
      <span class="dot"></span>
      <div class="body">${a.html}</div>
    </li>
  `).join("");
}

/* ---------- Charts ---------- */
let financeChart, attendanceChart;

function initCharts() {
  Chart.defaults.color = "rgba(245,230,200,0.7)";
  Chart.defaults.borderColor = "rgba(245,230,200,0.08)";
  Chart.defaults.font.family = "Inter, system-ui, sans-serif";

  const fctx = document.getElementById("finance-chart").getContext("2d");
  const goldGrad = fctx.createLinearGradient(0, 0, 0, 220);
  goldGrad.addColorStop(0, "rgba(233,185,98,0.45)");
  goldGrad.addColorStop(1, "rgba(233,185,98,0)");

  const blueGrad = fctx.createLinearGradient(0, 0, 0, 220);
  blueGrad.addColorStop(0, "rgba(44,126,184,0.4)");
  blueGrad.addColorStop(1, "rgba(44,126,184,0)");

  financeChart = new Chart(fctx, {
    type: "line",
    data: {
      labels: ["Dec", "Jan", "Feb", "Mar", "Apr", "May"],
      datasets: [
        {
          label: "Income",
          data: [3.2, 3.8, 4.1, 3.6, 3.79, 4.25],
          borderColor: "#e9b962",
          backgroundColor: goldGrad,
          fill: true,
          tension: 0.4,
          borderWidth: 2.5,
          pointRadius: 4,
          pointBackgroundColor: "#f3d27a",
          pointBorderColor: "#061410",
          pointBorderWidth: 2,
        },
        {
          label: "Expenses",
          data: [2.1, 2.4, 2.6, 2.3, 2.5, 2.7],
          borderColor: "#2c7eb8",
          backgroundColor: blueGrad,
          fill: true,
          tension: 0.4,
          borderWidth: 2,
          pointRadius: 3.5,
          pointBackgroundColor: "#9bd0f5",
          pointBorderColor: "#061410",
          pointBorderWidth: 2,
        },
      ],
    },
    options: chartOpts(true),
  });

  // Attendance trend — last 30 days
  const labels = Array.from({ length: 30 }, (_, i) => i + 1);
  const data = labels.map(d => {
    // realistic-looking attendance with a couple of dips
    const base = 92 + Math.sin(d / 4) * 4;
    const dip  = (d === 7 || d === 14 || d === 23) ? -8 : 0;
    return Math.max(72, Math.min(100, Math.round(base + dip + (Math.random() * 4 - 2))));
  });

  const actx = document.getElementById("attendance-chart").getContext("2d");
  const greenGrad = actx.createLinearGradient(0, 0, 0, 220);
  greenGrad.addColorStop(0, "rgba(61,155,106,0.45)");
  greenGrad.addColorStop(1, "rgba(61,155,106,0)");

  attendanceChart = new Chart(actx, {
    type: "line",
    data: {
      labels,
      datasets: [{
        label: "Attendance %",
        data,
        borderColor: "#7fd2a3",
        backgroundColor: greenGrad,
        fill: true,
        tension: 0.45,
        borderWidth: 2,
        pointRadius: 0,
        pointHoverRadius: 4,
      }],
    },
    options: {
      ...chartOpts(false),
      scales: {
        ...chartOpts(false).scales,
        y: { ...chartOpts(false).scales.y, min: 70, max: 100, ticks: { callback: v => v + "%" } },
      },
    },
  });
}

function chartOpts(showLegend) {
  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: "rgba(10,42,31,0.95)",
        titleColor: "#f5e6c8",
        bodyColor: "#f5e6c8",
        borderColor: "rgba(233,185,98,0.4)",
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8,
      },
    },
    interaction: { mode: "index", intersect: false },
    scales: {
      x: {
        grid: { display: false },
        ticks: { font: { size: 11 } },
      },
      y: {
        grid: { color: "rgba(245,230,200,0.05)", drawTicks: false },
        ticks: { font: { size: 11 } },
        border: { display: false },
      },
    },
  };
}

/* ---------- Role application ---------- */
function applyRole(roleId) {
  renderGreeting(roleId);
  renderKPIs(roleId);
  lucide.createIcons();
}

/* ---------- Toast ---------- */
function toast(message, icon = "info") {
  const c = document.getElementById("toast-container");
  const t = document.createElement("div");
  t.className = "toast";
  t.innerHTML = `${svgIcon(icon)}<span>${message}</span>`;
  c.appendChild(t);
  lucide.createIcons();
  setTimeout(() => t.remove(), 3000);
}

/* ---------- Init ---------- */
function setTodayDate() {
  try {
    const d = new Date();
    document.getElementById("today-date").textContent = new Intl.DateTimeFormat("en-GB", {
      weekday: "long", day: "numeric", month: "long", year: "numeric",
    }).format(d);
  } catch (_) {}
}

document.addEventListener("DOMContentLoaded", () => {
  setTodayDate();
  renderSidebar();
  renderRoleSwitch();
  applyRole("boss");
  renderUpcomingShows();
  renderTopPerformers();
  renderInsurance();
  renderApprovals();
  renderActivity();
  initCharts();
  lucide.createIcons();

  document.getElementById("demo-banner-close")?.addEventListener("click", () => {
    document.getElementById("demo-banner").remove();
    document.body.querySelector(".flex.min-h-screen").classList.remove("pt-9");
  });

  document.getElementById("notif-btn")?.addEventListener("click", () => {
    toast("4 new notifications · live feed in v1", "bell");
  });
});
