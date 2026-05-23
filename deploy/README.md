# Deploy to your cPanel server

You have three ways to push the site onto your shared-hosting cPanel
(`whm-market06`, account `inganzon`, domain `inganzongari.rw`). Pick the one
you're most comfortable with.

> Credentials policy: **none of the methods below require you to commit your
> hosting password to git or GitHub.** Method C reads them from a local file
> that is already in `.gitignore`.

| | What | Speed | Auto-updates? | Best for |
|--|------|-------|---------------|----------|
| **A** | Upload ZIP via cPanel File Manager (web UI) | 5 min | No | First-time deploy / one-off |
| **B** | Connect a Git repo in cPanel and pull on demand | 10 min setup, 30 s per update | Yes (button click) | Ongoing updates |
| **C** | PowerShell FTP uploader from your PC | 30 s per run | Yes (re-run the script) | Power users who edit locally |

---

## Method A — cPanel File Manager (recommended for first deploy)

1. Download `inganzo-ngari-coming-soon.zip` from the artifacts panel of this
   Cursor session (you already have it).
2. Open <http://197.243.23.16:2082/> in your browser and sign in.
3. Open **File Manager** → click the `public_html` folder.
4. Delete the default `index.html` / placeholder files inside `public_html`
   (use the cPanel "Select All" + "Delete" — keep `public_html` itself).
5. Click **Upload** (top toolbar) → choose `inganzo-ngari-coming-soon.zip` →
   wait for the green progress bar to finish → click *Go Back to /home/inganzon/public_html*.
6. Right-click the uploaded ZIP → **Extract** → set the path to
   `/home/inganzon/public_html` → click **Extract Files**.
7. Once extracted, delete the ZIP file from `public_html`.
8. *(Optional but recommended)* Delete the `scripts/` and `deploy/` folders
   inside `public_html` — they're Windows installers and deploy helpers,
   not needed on the server.
9. Visit <http://inganzongari.rw/> (or <http://197.243.23.16/~inganzon/>
   while DNS propagates). You should see the cinematic coming-soon page.

Then jump to the **Post-deploy checklist** at the bottom of this file.

---

## Method B — cPanel Git Version Control (auto-update on demand)

cPanel can clone the public GitHub repo directly. After the initial setup,
you just click "Update from Remote" any time I push a change — no FTP, no
uploads.

1. cPanel home → search **"Git Version Control"** → click it.
2. Click **Create** (top-right) → fill in:
   - **Clone a Repository:** ON
   - **Clone URL:** `https://github.com/afadhalij/for-cursor.git`
   - **Repository Path:** `/home/inganzon/repos/inganzo-ngari` *(this is the working clone, not `public_html`)*
   - **Repository Name:** `inganzo-ngari`
3. Click **Create**. Wait ~30 s for the clone.
4. In the same Git page → click your new repo → **Pull or Deploy** tab →
   change the **Branch** dropdown to
   `cursor/inganzo-ngari-coming-soon-1d51` → click **Update from Remote**.
5. cPanel will pull the latest. Now copy or symlink the site into
   `public_html`. Easiest: in File Manager, **move the contents** of
   `/home/inganzon/repos/inganzo-ngari/` into `/home/inganzon/public_html/`
   (or use a small `.cpanel.yml` deploy file — ask me if you want this).
6. Every time you want the latest version, return to Git Version Control →
   **Update from Remote** → done. (And then re-copy into `public_html` if
   you didn't set up `.cpanel.yml`.)

---

## Method C — PowerShell FTP uploader from your PC

A small PowerShell script (`deploy-cpanel-ftp.ps1`) that uploads the four
folders + four files to your `public_html` over FTP. **It reads your FTP
password from a local file that is already in `.gitignore`** — that file
never leaves your PC, never reaches git or GitHub.

### One-time setup (on your PC)

1. Make sure you've checked out this repo locally
   (`git clone …` or via the auto-host installer).
2. Open the folder `deploy\` on your PC.
3. Copy `deploy-credentials.example.ps1` to `deploy-credentials.ps1`
   (note: no `.example`).
4. Open `deploy-credentials.ps1` in Notepad and fill in your FTP host,
   username, and password (the ones the hosting provider gave you). Save.
5. `.gitignore` already excludes `deploy-credentials.ps1`, so it will
   never be staged, committed, or pushed.

### To deploy

Open PowerShell in the repo's root folder and run:

```powershell
.\deploy\deploy-cpanel-ftp.ps1
```

The script uploads only the files needed by the public site and prints a
running list of what it pushed. Re-run any time you want to publish updates.

### Switching to FTPS (encrypted)

cPanel supports FTPS (FTP over TLS). To use it, set `$UseFtps = $true` near
the top of `deploy-cpanel-ftp.ps1`. Without it, your FTP password travels
the network in plain text — fine for a one-off deploy on a trusted network,
not great long-term. For production use I recommend [WinSCP](https://winscp.net/)
with the saved-session feature instead.

---

## Post-deploy checklist (do these once, after your first successful deploy)

1. **Sign in to the admin panel** at `http://inganzongari.rw/admin/` (or
   the temporary IP URL) with the password set during install.
   *(If you do not have it, ask the project owner.)*
2. **Change the password immediately** in the *Account* section at the
   bottom of the admin dashboard. The default-password warning clears once
   the new password is saved.
3. **Enable HTTPS.** In cPanel: **SSL/TLS Status** (or **Let's Encrypt SSL**
   if installed) → tick `inganzongari.rw` and `www.inganzongari.rw` →
   **Run AutoSSL** / **Issue**. Then in **Domains**, turn on
   *Force HTTPS Redirect* for both. Within a few minutes the site is on
   `https://inganzongari.rw/`.
4. *(Optional but recommended)* **Restrict `/admin/` by IP or HTTP-Basic
   auth.** In cPanel: **Directory Privacy** → enable on `/admin/` → set a
   second username + password. Now the admin panel asks for *two* passwords
   in sequence — Basic Auth first, then the application login.
5. *(Optional)* **Add a `robots.txt`** that disallows `/admin/` so search
   engines don't index it. (The admin pages already carry
   `<meta name="robots" content="noindex,nofollow">`, so this is belt + braces.)

---

## What gets deployed vs ignored

| Folder / file | Deploy to server? | Why |
|---|:-:|---|
| `index.html`, `styles.css`, `script.js` | ✅ | Public coming-soon page |
| `content.json` | ✅ | Site content, admin-editable |
| `assets/` | ✅ | Logo + slideshow photos |
| `admin/` | ✅ | The admin panel (PHP) |
| `system-demo/` | ✅ | Client demo dashboard (`/system-demo/`) |
| `README.md` | optional | Harmless, can keep or delete |
| `scripts/` | ❌ | Windows installers — not for the server |
| `deploy/` | ❌ | Local deploy helpers — not for the server |
| `.git/` | ❌ | Source control internals |
| `.gitignore` | optional | Harmless |
