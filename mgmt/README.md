# Inganzo Ngari Management System

Working PHP + MySQL implementation of the Members module — the first real
module of the management system whose static client preview you saw at
`/system-demo/`.

## What's included (v1)

- **5 tabs on the Members page**
  1. Active Members — list / add / edit / retire, with filters (section, performer type, role, category)
  2. Inganzo Nkuru — retired-member archive with reactivation
  3. Categories — payroll-linked categories with fixed salaries
  4. Roles — the 13 Kinyarwanda roles, scoped per section
  5. Sections — Leaders / Performers / Support Team overview
- **National-ID auto-fill** — gender (6th digit: 7=F, 8=M) and birth year (digits 2–5)
- **Health insurance** — none / active / expiring / expired, with automatic 30-day alerting
- **Member profile page** with photo, contact, insurance, timeline, and QR code member card
- **Dashboard analytics** — KPI cards (total / Indende / Abaterambabazi / Inyamamare), category bar chart, performer-type donut, insurance alerts, upcoming birthdays, new members, role distribution

## Tech

- PHP 8.1+ (PDO MySQL)
- MySQL 5.7+ / MariaDB 10.4+
- Bootstrap 5.3 (CDN), Font Awesome 6.5 (CDN), DataTables 2.1 (CDN), Chart.js 4.4 (CDN), jQuery 3.7 (CDN), qrcode-generator (CDN)
- No build step — just copy the folder, point Apache at it.

## Install (local — XAMPP)

1. Copy the `mgmt` folder into `C:\xampp\htdocs\inganzongari\` (or wherever you keep the site).
2. Start Apache + MySQL in the XAMPP Control Panel.
3. Open phpMyAdmin → create a new database called `inganzo_mgmt` (collation `utf8mb4_unicode_ci`).
4. Import in order:
   - `sql/schema.sql`
   - `sql/seed.sql`
5. Copy `includes/config.local.example.php` to `includes/config.local.php` and fill in your DB credentials. (XAMPP defaults to user `root` with no password.)
6. Visit `http://localhost/inganzongari/mgmt/` — you'll land on the login page.
7. Sign in: **admin / `InganzoN`** — change the password in *Settings* immediately.

## Install (live — cPanel)

1. Upload the `mgmt` folder into `public_html/`.
2. In cPanel → **MySQL Databases**: create database `xxx_mgmt`, create user, grant ALL privileges.
3. In cPanel → **phpMyAdmin**: select the new DB, Import → `sql/schema.sql`, then `sql/seed.sql`.
4. Edit `mgmt/includes/config.local.php` with the DB credentials cPanel showed you.
5. Visit `https://yourdomain/mgmt/` — sign in with **admin / `InganzoN`** and change it.

## File layout

```
mgmt/
├── index.php                 — Dashboard
├── members.php               — Members page (5 tabs)
├── profile.php               — Member profile page (with QR card)
├── login.php / logout.php    — Auth
├── includes/
│   ├── config.php            — Loads config.local.php if present
│   ├── config.local.example.php
│   ├── db.php                — PDO singleton + helpers
│   ├── auth.php              — Session auth + CSRF
│   ├── header.php / footer.php — Shared layout (sidebar + topbar)
├── api/
│   ├── _router.php           — Shared bootstrap for JSON endpoints
│   ├── members.php           — Member CRUD + retire + reactivate + photo upload
│   ├── categories.php        — Category CRUD
│   ├── roles.php             — Role CRUD
│   ├── sections.php          — Section + performer-type lists
│   └── analytics.php         — Dashboard data
├── assets/
│   ├── css/app.css           — Bootstrap-5 + dark green/gold theme
│   └── js/
│       ├── helpers.js        — AJAX wrapper, toast, NID parser, chips
│       ├── members.js        — Members-page logic (all 5 tabs)
│       └── dashboard.js      — Dashboard charts + lists
├── uploads/                  — Member photos (created on first upload)
└── sql/
    ├── schema.sql
    └── seed.sql
```

## What comes next

Now that the Members foundation is in place, future modules
(Attendance, Discipline, Shows, Payroll, Loans, Kuzigama, Welfare,
Logistics) plug into the same DB and the same theme.

## Default credentials

- Username: **admin**
- Password: **InganzoN**
- Change it after first login. The `users.must_change` flag triggers a
  banner until you do.
