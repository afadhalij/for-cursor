# Inganzo Ngari — Auto-host installer for Windows + XAMPP

These scripts install the site, admin panel, and demo dashboard into your
local XAMPP, **and** make Apache start automatically every time you turn
your PC on. After running once, the three URLs work for life without you
ever having to open the XAMPP Control Panel.

## URLs you'll have after install

| What | Where |
|---|---|
| Public coming-soon page | <http://localhost/inganzongari/> |
| Admin panel             | <http://localhost/inganzongari/admin/> |
| Demo dashboard          | <http://localhost/inganzongari/system-demo/> |

Three desktop shortcuts are created so you can launch any of them with one
double-click — handy for client demos.

## Prerequisites

- Windows 10 or 11
- **XAMPP installed at `C:\xampp`** (the default location). [Download XAMPP](https://www.apachefriends.org/)
- Internet connection (only when installing or updating)
- About 20 seconds of your time

## How to install (one of three ways — pick the easiest)

### Option A — Double-click (easiest)

1. Download both files in this folder to the same place on your PC:
   - `install.cmd`
   - `install-and-autohost.ps1`
2. **Right-click `install.cmd` → "Run as administrator"**.
3. Approve the UAC prompt. The installer runs, ~20 seconds.
4. When it finishes, the dashboard opens automatically in your browser.

### Option B — Run the PowerShell script directly

1. Right-click PowerShell → **Run as administrator**.
2. `cd` to where you saved `install-and-autohost.ps1`.
3. Run:
   ```powershell
   Set-ExecutionPolicy -Scope Process Bypass -Force
   .\install-and-autohost.ps1
   ```

### Option C — One-liner (no files to download)

Open **PowerShell as administrator** and paste this single line:

```powershell
$u='https://raw.githubusercontent.com/afadhalij/for-cursor/cursor/inganzo-ngari-coming-soon-1d51/scripts/install-and-autohost.ps1';$f="$env:TEMP\ing-install.ps1";Invoke-WebRequest $u -OutFile $f;Set-ExecutionPolicy -Scope Process Bypass -Force;& $f
```

That downloads and runs the installer with one command.

## What the installer does

1. Checks XAMPP is installed at `C:\xampp` (use `-XamppPath` if yours is elsewhere).
2. Downloads the latest project ZIP from GitHub.
3. **Backs up** your existing `content.json`, admin password, uploaded logo, and slide photos so updates don't wipe them.
4. Replaces `C:\xampp\htdocs\inganzongari\` with the latest files, then restores your preserved data.
5. Installs `Apache2.4` as a Windows service set to **Automatic** startup.
6. Starts Apache now.
7. Creates the three desktop shortcuts.
8. Opens the dashboard in your default browser.

## To update later

Just re-run the installer (any of options A/B/C above). It always pulls the
latest version, preserves your admin's data, and restarts Apache cleanly.

## To uninstall the auto-start

If you ever want to disable the auto-start service, open PowerShell as
administrator and run:

```powershell
Stop-Service Apache2.4
& "C:\xampp\apache\bin\httpd.exe" -k uninstall -n Apache2.4
```

You can still start XAMPP manually from the XAMPP Control Panel as before.

## Troubleshooting

**Apache won't start** (the installer reports "failed to start"):

- Open the XAMPP Control Panel, click *Apache → Logs → error.log*.
- Most common cause: another program is holding port 80. Disable
  *World Wide Web Publishing Service*, IIS, or Skype's incoming-call port.

**XAMPP isn't at `C:\xampp`:**

```powershell
.\install-and-autohost.ps1 -XamppPath "D:\my-xampp"
```

**Want a different branch:**

```powershell
.\install-and-autohost.ps1 -BranchName main
```
