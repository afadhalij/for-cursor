# Inganzo Ngari — Coming Soon

A one-page coming-soon site for the Rwandan cultural troupe **Inganzo Ngari**, with a built-in admin panel so you can edit every piece of content (text, dates, social links, logo, background photo) without touching code.

> *"Where tradition meets the stage."*

---

## What's in this project

| File / folder | Purpose |
|---|---|
| `index.html` | The public coming-soon page (markup, SEO/OG tags, fallback copy). |
| `styles.css` | All visual styling. |
| `script.js` | Loads `content.json` at runtime, runs the countdown, switches EN/RW/FR. |
| `content.json` | All editable site copy — read by the front-end, written by the admin panel. |
| `assets/logo.webp` | The site logo. |
| `assets/slides/*.webp` | The cross-fading hero background photos. |
| `admin/index.php` | Single-page admin: login, edit form, image uploads, password change. |
| `admin/credentials.json` | Hashed admin password (default `admin123` — **change immediately**). |
| `admin/.htaccess` | Blocks direct download of the credentials file. |

The public page uses **no build step** and **no PHP** — just three static files plus `content.json`. PHP is only needed for the admin panel.

---

## Install on XAMPP (Windows)

1. Make sure XAMPP is installed and Apache is running.
2. Drop the project into `C:\xampp\htdocs\inganzongari\` (or any subfolder of `htdocs`).
3. Open the public site at **http://localhost/inganzongari/**
4. Open the admin panel at **http://localhost/inganzongari/admin/**
5. Sign in with the default password **`admin123`** and immediately change it from the *Account* section at the bottom of the dashboard.

### Quick PowerShell installer

Paste this single line into PowerShell on your Windows PC:

```powershell
$z="$env:TEMP\ing.zip";$t="$env:TEMP\ing_x";$d="C:\xampp\htdocs\inganzongari";Invoke-WebRequest "https://github.com/afadhalij/for-cursor/archive/refs/heads/cursor/inganzo-ngari-coming-soon-1d51.zip" -OutFile $z;if(Test-Path $t){Remove-Item $t -Recurse -Force};if(Test-Path $d){Remove-Item $d -Recurse -Force};Expand-Archive $z $t -Force;$i=(Get-ChildItem $t)[0].FullName;New-Item -ItemType Directory -Force $d|Out-Null;Copy-Item "$i\*" $d -Recurse -Force;Start-Process "http://localhost/inganzongari/"
```

---

## Using the admin panel

Visit `…/admin/`, sign in, then edit any of these:

- **Launch & Contact** — countdown target date/time and contact email.
- **Social Links** — paste the full URL for Instagram, Facebook, YouTube, X.
- **Text & Translations** — eyebrow, tagline, subtitle, countdown labels and footer text in EN, RW, FR.
- **Logo** — upload a new top-left logo (PNG / JPG / WebP / GIF, up to 8 MB).
- **Background Slideshow** — manage the cross-fading hero photos. Tick or untick each slide to keep or remove it (removed files are deleted on save), tweak the time-per-slide and cross-fade duration, and upload one or more new slides at the bottom. Use landscape (16:9) photos at 1600×900 or larger for best results.
- **Account** — change the admin password (use 8+ characters).

Click **Save changes** — the public page picks up your edits on the next load (the front-end fetches `content.json` with a cache-busting query string, so a hard-refresh isn't needed).

---

## Local preview without PHP

If you just want to preview the public page (no admin), any static server works:

```bash
python3 -m http.server 8000
# then open http://localhost:8000
```

---

## Deploying to a non-PHP host

The public page (`index.html`, `styles.css`, `script.js`, `content.json`, `assets/`) deploys fine to GitHub Pages, Netlify, Vercel, etc. The `admin/` folder requires PHP, so on those hosts the admin panel won't work — just edit `content.json` directly and re-deploy.

---

## Security notes

- The admin password is stored as a bcrypt hash in `admin/credentials.json`.
- The `admin/.htaccess` file blocks direct download of `credentials.json`.
- Sessions are HttpOnly with SameSite=Lax, and every form is CSRF-protected.
- The default password (`admin123`) **must** be changed before going to production.
- For extra hardening on a public server, also restrict `admin/` by IP, HTTP basic auth, or by moving it behind a VPN.
