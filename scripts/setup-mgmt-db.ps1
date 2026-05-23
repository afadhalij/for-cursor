<#
.SYNOPSIS
    One-shot setup for the Inganzo Ngari management system's MySQL database.
    Bypasses phpMyAdmin entirely — talks to mysql.exe directly using the XAMPP
    MariaDB instance.

.DESCRIPTION
    What it does:
      1. Finds XAMPP's mysql.exe.
      2. Tries to connect as `root` with no password (the XAMPP default).
         If that fails, prompts you for the password you set.
      3. Creates the `inganzo_mgmt` database.
      4. Imports `mgmt/sql/schema.sql` then `mgmt/sql/seed.sql`.
      5. Writes (or rewrites) `mgmt/includes/config.local.php` with the
         working credentials so the management site can connect.
      6. Opens http://localhost/inganzongari/mgmt/ in your browser.

    Safe to re-run. Re-running drops and recreates the schema, so any data
    you imported manually via the UI will be wiped — only do that on
    purpose.

.PARAMETER XamppPath
    Folder where XAMPP is installed. Defaults to C:\xampp.

.PARAMETER DbName
    Database name to create. Defaults to inganzo_mgmt.

.PARAMETER DbUser
    Database user (defaults to root).

.PARAMETER DbPass
    Database password. Omit to try empty first, then be prompted.

.EXAMPLE
    .\setup-mgmt-db.ps1
    # Prompts for password only if needed.

.EXAMPLE
    .\setup-mgmt-db.ps1 -DbPass "mysecret"
#>
[CmdletBinding()]
param(
    [string]$XamppPath = "C:\xampp",
    [string]$DbName    = "inganzo_mgmt",
    [string]$DbUser    = "root",
    [string]$DbPass    = ""
)

$ErrorActionPreference = "Stop"
$ProgressPreference    = "SilentlyContinue"

function Write-Step([string]$t) { Write-Host ""; Write-Host "==> $t" -ForegroundColor Yellow }
function Write-Ok  ([string]$t) { Write-Host "    OK: $t" -ForegroundColor Green }
function Write-Bad ([string]$t) { Write-Host "    !!  $t" -ForegroundColor Red }

# ----------------------------------------------------------------------------
# 1. Locate XAMPP files
# ----------------------------------------------------------------------------
Write-Step "Locating XAMPP"
$mysql = Join-Path $XamppPath "mysql\bin\mysql.exe"
if (-not (Test-Path $mysql)) {
    Write-Bad "Cannot find $mysql"
    Write-Host "Pass -XamppPath if XAMPP is installed somewhere else (e.g. -XamppPath 'D:\xampp')." -ForegroundColor DarkYellow
    exit 1
}
Write-Ok "mysql.exe at $mysql"

$htdocs     = Join-Path $XamppPath "htdocs"
$mgmtRoot   = Join-Path $htdocs "inganzongari\mgmt"
$schemaPath = Join-Path $mgmtRoot "sql\schema.sql"
$seedPath   = Join-Path $mgmtRoot "sql\seed.sql"
$configPath = Join-Path $mgmtRoot "includes\config.local.php"

foreach ($p in @($schemaPath, $seedPath)) {
    if (-not (Test-Path $p)) {
        Write-Bad "Missing $p"
        Write-Host "Run the main installer first: " -NoNewline
        Write-Host "scripts\install-and-autohost.ps1" -ForegroundColor Cyan
        exit 1
    }
}
Write-Ok "Found schema.sql and seed.sql"

# ----------------------------------------------------------------------------
# 2. Is MySQL actually running?
# ----------------------------------------------------------------------------
Write-Step "Checking MariaDB / MySQL is running"
$port = Test-NetConnection -ComputerName localhost -Port 3306 -InformationLevel Quiet -WarningAction SilentlyContinue
if (-not $port) {
    Write-Bad "Nothing is listening on port 3306."
    Write-Host "Open the XAMPP Control Panel and click 'Start' next to MySQL, then re-run." -ForegroundColor DarkYellow
    exit 1
}
Write-Ok "Something is listening on 3306"

# ----------------------------------------------------------------------------
# 3. Find a working password
# ----------------------------------------------------------------------------
function Test-MysqlConnect([string]$user, [string]$pass) {
    $env:MYSQL_PWD = $pass
    try {
        & $mysql --protocol=tcp -h 127.0.0.1 -u $user -N -e "SELECT 1;" 2>$null | Out-Null
        return $LASTEXITCODE -eq 0
    } finally {
        $env:MYSQL_PWD = $null
    }
}

Write-Step "Finding a working MySQL password"
$ok = $false
if (Test-MysqlConnect $DbUser $DbPass) {
    Write-Ok "Connected as $DbUser with the supplied password"
    $ok = $true
}
elseif (-not $DbPass -and (Test-MysqlConnect $DbUser "")) {
    $DbPass = ""
    Write-Ok "Connected as $DbUser with no password (XAMPP default)"
    $ok = $true
}

if (-not $ok) {
    Write-Host "    Could not connect with the default password." -ForegroundColor DarkYellow
    Write-Host "    Please enter the password you set for MariaDB root:" -ForegroundColor DarkYellow
    for ($try = 1; $try -le 3; $try++) {
        $sec = Read-Host -AsSecureString "    MySQL root password (try $try/3)"
        $DbPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
            [Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec))
        if (Test-MysqlConnect $DbUser $DbPass) {
            Write-Ok "Connected"
            $ok = $true
            break
        } else {
            Write-Bad "Wrong password."
        }
    }
}

if (-not $ok) {
    Write-Bad "Could not authenticate. Aborting."
    Write-Host ""
    Write-Host "If you have forgotten the MariaDB root password, the safest fix is to" -ForegroundColor DarkYellow
    Write-Host "stop MySQL from the XAMPP Control Panel and follow XAMPP's password reset" -ForegroundColor DarkYellow
    Write-Host "guide, then re-run this script." -ForegroundColor DarkYellow
    exit 1
}

# Helper to run a single SQL command
function Invoke-Sql([string]$sql, [string]$db = "") {
    $env:MYSQL_PWD = $DbPass
    try {
        $args = @('--protocol=tcp', '-h', '127.0.0.1', '-u', $DbUser, '-e', $sql)
        if ($db) { $args = @('--protocol=tcp', '-h', '127.0.0.1', '-u', $DbUser, $db, '-e', $sql) }
        & $mysql @args
        if ($LASTEXITCODE -ne 0) { throw "mysql exit $LASTEXITCODE" }
    } finally {
        $env:MYSQL_PWD = $null
    }
}

# Helper to import a .sql file
function Import-SqlFile([string]$path, [string]$db) {
    $env:MYSQL_PWD = $DbPass
    try {
        # Pipe the file's bytes into mysql's stdin
        Get-Content -Path $path -Raw -Encoding UTF8 | & $mysql --protocol=tcp -h 127.0.0.1 -u $DbUser --default-character-set=utf8mb4 $db
        if ($LASTEXITCODE -ne 0) { throw "Importing $path failed (mysql exit $LASTEXITCODE)" }
    } finally {
        $env:MYSQL_PWD = $null
    }
}

# ----------------------------------------------------------------------------
# 4. Create the database
# ----------------------------------------------------------------------------
Write-Step "Creating database '$DbName'"
Invoke-Sql "CREATE DATABASE IF NOT EXISTS ``$DbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
Write-Ok "Database ready"

# ----------------------------------------------------------------------------
# 5. Import schema + seed
# ----------------------------------------------------------------------------
Write-Step "Importing schema (schema.sql)"
Import-SqlFile $schemaPath $DbName
Write-Ok "Schema imported"

Write-Step "Importing seed data (seed.sql)"
Import-SqlFile $seedPath $DbName
Write-Ok "Seed imported"

# Quick row counts so you can see it worked
$env:MYSQL_PWD = $DbPass
$counts = & $mysql --protocol=tcp -h 127.0.0.1 -u $DbUser -N -B -e "
    SELECT 'sections',        COUNT(*) FROM $DbName.sections
    UNION SELECT 'roles',      COUNT(*) FROM $DbName.roles
    UNION SELECT 'categories', COUNT(*) FROM $DbName.categories
    UNION SELECT 'members',    COUNT(*) FROM $DbName.members
    UNION SELECT 'users',      COUNT(*) FROM $DbName.users;"
$env:MYSQL_PWD = $null
Write-Host ""
Write-Host "    Row counts after seed:" -ForegroundColor Cyan
$counts -split "`n" | ForEach-Object {
    if ($_.Trim()) {
        $parts = $_ -split "`t"
        Write-Host ("      {0,-12} {1}" -f $parts[0], $parts[1]) -ForegroundColor Cyan
    }
}

# ----------------------------------------------------------------------------
# 6. Write config.local.php
# ----------------------------------------------------------------------------
Write-Step "Writing $configPath"
$configDir = Split-Path $configPath -Parent
if (-not (Test-Path $configDir)) {
    New-Item -ItemType Directory -Force -Path $configDir | Out-Null
}

# PHP-escape the password (single-quoted string)
$phpPass = $DbPass -replace "\\", "\\\\" -replace "'", "\\'"

$configContent = @"
<?php
// Auto-generated by scripts\setup-mgmt-db.ps1 — safe to edit by hand later.
define('DB_HOST',  'localhost');
define('DB_PORT',  3306);
define('DB_NAME',  '$DbName');
define('DB_USER',  '$DbUser');
define('DB_PASS',  '$phpPass');
define('APP_BASE', '/inganzongari/mgmt');
define('APP_ENV',  'production');
"@
Set-Content -Path $configPath -Value $configContent -Encoding UTF8 -NoNewline
Write-Ok "Config saved"

# ----------------------------------------------------------------------------
# 7. Make sure uploads/ exists
# ----------------------------------------------------------------------------
$uploadsDir = Join-Path $mgmtRoot "uploads\members"
if (-not (Test-Path $uploadsDir)) {
    New-Item -ItemType Directory -Force -Path $uploadsDir | Out-Null
    Write-Ok "Created $uploadsDir"
}

# ----------------------------------------------------------------------------
# 8. Done — open browser
# ----------------------------------------------------------------------------
Write-Step "All done"
Write-Host ""
Write-Host "    Open:   http://localhost/inganzongari/mgmt/"            -ForegroundColor Cyan
Write-Host "    Login:  admin / InganzoN  (change immediately)"          -ForegroundColor Yellow
Write-Host ""
Write-Host "Then click 'Import' on the Members page and pick your Excel file." -ForegroundColor DarkGray

Start-Process "http://localhost/inganzongari/mgmt/"
