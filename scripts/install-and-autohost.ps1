<#
.SYNOPSIS
    Installs the Inganzo Ngari site + admin + demo dashboard into XAMPP's htdocs
    and configures Apache to start automatically every time Windows boots.

.DESCRIPTION
    Run from PowerShell as Administrator (so it can install the Apache Windows
    service). Idempotent: safe to re-run any time to update to the latest
    version from GitHub.

    What it does:
      1. Verifies XAMPP is installed at C:\xampp (or asks for its path).
      2. Downloads the latest project ZIP from GitHub.
      3. Replaces C:\xampp\htdocs\inganzongari\ with the new files.
      4. Installs XAMPP's Apache as a Windows service set to "Automatic".
      5. Starts Apache now (if not already running).
      6. Creates three desktop shortcuts:
           - "Inganzo Ngari Site"      -> http://localhost/inganzongari/
           - "Inganzo Ngari Admin"     -> http://localhost/inganzongari/admin/
           - "Inganzo Ngari Dashboard" -> http://localhost/inganzongari/system-demo/

    After running, just power your PC on and the three URLs will work — no
    XAMPP Control Panel needed.

.PARAMETER XamppPath
    Folder where XAMPP is installed. Defaults to C:\xampp.

.PARAMETER BranchName
    Git branch on the GitHub repo to install. Defaults to the latest stable.

.EXAMPLE
    Right-click PowerShell -> Run as administrator
    cd to the folder where this script lives, then:
        .\install-and-autohost.ps1

.NOTES
    Requires: Windows 10/11, XAMPP installed, internet connection.
#>
[CmdletBinding()]
param(
    [string]$XamppPath  = "C:\xampp",
    [string]$BranchName = "cursor/inganzo-ngari-coming-soon-1d51",
    [string]$RepoZipUrl = ""   # Optional override; otherwise built from $BranchName
)

$ErrorActionPreference = "Stop"
$ProgressPreference    = "SilentlyContinue"   # speeds up Invoke-WebRequest

# ------------------------------------------------------------------------------
# 1. Pretty printing
# ------------------------------------------------------------------------------
function Write-Step([string]$text) {
    Write-Host ""
    Write-Host "==> $text" -ForegroundColor Yellow
}
function Write-Ok([string]$text) {
    Write-Host "    OK: $text" -ForegroundColor Green
}
function Write-Skip([string]$text) {
    Write-Host "    SKIP: $text" -ForegroundColor DarkGray
}

# ------------------------------------------------------------------------------
# 2. Must be admin
# ------------------------------------------------------------------------------
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole(
    [Security.Principal.WindowsBuiltInRole]::Administrator
)
if (-not $isAdmin) {
    Write-Host "This script must run as Administrator (it installs a Windows service)." -ForegroundColor Red
    Write-Host "Right-click PowerShell -> Run as administrator, then re-run this script." -ForegroundColor Red
    exit 1
}

# ------------------------------------------------------------------------------
# 3. XAMPP sanity check
# ------------------------------------------------------------------------------
Write-Step "Checking XAMPP installation"
$apacheExe        = Join-Path $XamppPath "apache\bin\httpd.exe"
$apacheServiceBat = Join-Path $XamppPath "apache\bin\httpd.exe"   # installed via -k install
$htdocs           = Join-Path $XamppPath "htdocs"

if (-not (Test-Path $apacheExe)) {
    Write-Host "Could not find Apache at $apacheExe." -ForegroundColor Red
    Write-Host "Pass -XamppPath if XAMPP is installed somewhere else." -ForegroundColor Red
    exit 1
}
Write-Ok "Found XAMPP Apache at $apacheExe"

if (-not (Test-Path $htdocs)) {
    Write-Host "Could not find $htdocs. Is XAMPP installed correctly?" -ForegroundColor Red
    exit 1
}

# ------------------------------------------------------------------------------
# 4. Download the latest project
# ------------------------------------------------------------------------------
Write-Step "Downloading the latest version from GitHub"

if ([string]::IsNullOrEmpty($RepoZipUrl)) {
    $RepoZipUrl = "https://github.com/afadhalij/for-cursor/archive/refs/heads/$BranchName.zip"
}

$tmpZip     = Join-Path $env:TEMP "inganzo-ngari.zip"
$tmpExtract = Join-Path $env:TEMP "inganzo-ngari-extract"
$dest       = Join-Path $htdocs "inganzongari"

if (Test-Path $tmpZip)     { Remove-Item $tmpZip -Force }
if (Test-Path $tmpExtract) { Remove-Item $tmpExtract -Recurse -Force }

try {
    Invoke-WebRequest -Uri $RepoZipUrl -OutFile $tmpZip
} catch {
    Write-Host "Download failed: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Check your internet connection or the branch name '$BranchName'." -ForegroundColor Red
    exit 1
}
Write-Ok "Downloaded $(Get-Item $tmpZip | Select-Object -ExpandProperty Length) bytes"

Write-Step "Extracting"
Expand-Archive -Path $tmpZip -DestinationPath $tmpExtract -Force
$inner = Get-ChildItem -Path $tmpExtract | Select-Object -First 1
if (-not $inner) {
    Write-Host "Extract produced no folders." -ForegroundColor Red
    exit 1
}
Write-Ok "Extracted to $($inner.FullName)"

# ------------------------------------------------------------------------------
# 5. Replace the htdocs folder, but preserve uploaded admin content
# ------------------------------------------------------------------------------
Write-Step "Installing into $dest"

# Files the admin panel may have written that we should NOT overwrite.
$preserveRel = @(
    "content.json",
    "admin\credentials.json",
    "assets\logo.webp",
    "assets\slides"
)

$backupPath = ""
if (Test-Path $dest) {
    $backupPath = Join-Path $env:TEMP ("inganzongari-backup-{0:yyyyMMdd-HHmmss}" -f (Get-Date))
    New-Item -ItemType Directory -Force -Path $backupPath | Out-Null
    foreach ($rel in $preserveRel) {
        $src = Join-Path $dest $rel
        if (Test-Path $src) {
            $target = Join-Path $backupPath $rel
            New-Item -ItemType Directory -Force -Path (Split-Path $target -Parent) | Out-Null
            Copy-Item -Path $src -Destination $target -Recurse -Force
        }
    }
    Write-Ok "Backed up existing user data to $backupPath"
    Remove-Item $dest -Recurse -Force
}

New-Item -ItemType Directory -Force -Path $dest | Out-Null
Copy-Item -Path (Join-Path $inner.FullName "*") -Destination $dest -Recurse -Force
Write-Ok "Latest files copied to $dest"

# Restore preserved user data on top of the fresh install.
if ($backupPath -and (Test-Path $backupPath)) {
    foreach ($rel in $preserveRel) {
        $b = Join-Path $backupPath $rel
        if (Test-Path $b) {
            $t = Join-Path $dest $rel
            New-Item -ItemType Directory -Force -Path (Split-Path $t -Parent) | Out-Null
            Copy-Item -Path $b -Destination $t -Recurse -Force
        }
    }
    Write-Ok "Restored preserved files: $($preserveRel -join ', ')"
}

# ------------------------------------------------------------------------------
# 6. Install Apache as an auto-start Windows service
# ------------------------------------------------------------------------------
Write-Step "Installing Apache as an auto-start Windows service"

$svcName = "Apache2.4"

$svc = Get-Service -Name $svcName -ErrorAction SilentlyContinue
if ($svc) {
    Write-Skip "Apache service '$svcName' already exists"
} else {
    & $apacheExe -k install -n $svcName | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Apache -k install reported exit code $LASTEXITCODE. Continuing..." -ForegroundColor DarkYellow
    }
    Write-Ok "Installed service '$svcName'"
}

# Set startup to Automatic (covers both fresh installs and pre-existing manual installs).
try {
    Set-Service -Name $svcName -StartupType Automatic
    Write-Ok "Service startup type set to Automatic (will start on every boot)"
} catch {
    Write-Host "Could not set service startup type: $($_.Exception.Message)" -ForegroundColor DarkYellow
}

# Start the service now if it's not running.
$svc = Get-Service -Name $svcName
if ($svc.Status -ne 'Running') {
    try {
        Start-Service -Name $svcName
        Write-Ok "Apache started"
    } catch {
        Write-Host "Apache failed to start: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "Check that port 80 isn't held by another program (Skype, IIS, World Wide Web Publishing Service)." -ForegroundColor Red
    }
} else {
    Write-Skip "Apache was already running"
}

# ------------------------------------------------------------------------------
# 7. Desktop shortcuts
# ------------------------------------------------------------------------------
Write-Step "Creating desktop shortcuts"

$desktop  = [Environment]::GetFolderPath("Desktop")
$shortcuts = @(
    @{ Name = "Inganzo Ngari Site";       Url = "http://localhost/inganzongari/" },
    @{ Name = "Inganzo Ngari Admin";      Url = "http://localhost/inganzongari/admin/" },
    @{ Name = "Inganzo Ngari Dashboard";  Url = "http://localhost/inganzongari/system-demo/" }
)

$ws = New-Object -ComObject WScript.Shell
foreach ($s in $shortcuts) {
    $lnkPath = Join-Path $desktop ("{0}.url" -f $s.Name)
    Set-Content -Path $lnkPath -Encoding ASCII -Value @"
[InternetShortcut]
URL=$($s.Url)
"@
    Write-Ok "Shortcut: $($s.Name) -> $($s.Url)"
}

# ------------------------------------------------------------------------------
# 8. Done
# ------------------------------------------------------------------------------
Write-Step "All done"
Write-Host "Your three URLs (work from this PC any time after a reboot):" -ForegroundColor Cyan
Write-Host "  Site       http://localhost/inganzongari/"            -ForegroundColor Cyan
Write-Host "  Admin      http://localhost/inganzongari/admin/"      -ForegroundColor Cyan
Write-Host "  Dashboard  http://localhost/inganzongari/system-demo/" -ForegroundColor Cyan
Write-Host ""
Write-Host "Sign in at the Admin URL with the password set during install." -ForegroundColor Yellow
Write-Host "If you do not have it, ask the project owner."                  -ForegroundColor Yellow
Write-Host ""
Write-Host "To update later, re-run this script. It preserves your admin's"      -ForegroundColor DarkGray
Write-Host "uploaded logo, slides, content.json, and password hash on every run." -ForegroundColor DarkGray

# Open the dashboard automatically so you can show the client right away.
Start-Process "http://localhost/inganzongari/system-demo/"
