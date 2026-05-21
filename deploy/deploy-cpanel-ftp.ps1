<#
.SYNOPSIS
    Upload the Inganzo Ngari coming-soon site to a cPanel host over FTP/FTPS.

.DESCRIPTION
    Reads your FTP credentials from `deploy-credentials.ps1` (which is
    git-ignored) and uploads the public-site files into the chosen remote
    folder.

    Only deploys what the live server needs. Skips:
      - .git, deploy/, scripts/ (developer-only)
      - any .zip, .log, .tmp, .DS_Store, Thumbs.db

    Idempotent — re-run any time to publish updates.

.EXAMPLE
    .\deploy\deploy-cpanel-ftp.ps1

.NOTES
    PowerShell 5+ (Windows 10/11 has this built in).
    Uses System.Net.FtpWebRequest. For larger sites or production work,
    consider WinSCP instead.
#>
[CmdletBinding()]
param(
    [string]$CredentialsFile = (Join-Path $PSScriptRoot "deploy-credentials.ps1")
)

$ErrorActionPreference = "Stop"

# ---------- Load credentials --------------------------------------------------
if (-not (Test-Path $CredentialsFile)) {
    Write-Host "Missing $CredentialsFile" -ForegroundColor Red
    Write-Host "Copy deploy-credentials.example.ps1 -> deploy-credentials.ps1," -ForegroundColor Red
    Write-Host "fill in your FTP host / user / password, then re-run." -ForegroundColor Red
    exit 1
}
. $CredentialsFile
foreach ($v in @('FtpHost','FtpUser','FtpPass','FtpRoot')) {
    if (-not (Get-Variable -Name $v -Scope Script -ErrorAction SilentlyContinue) -or
        [string]::IsNullOrWhiteSpace((Get-Variable -Name $v -Scope Script).Value)) {
        Write-Host "Missing or empty variable: `$$v in $CredentialsFile" -ForegroundColor Red
        exit 1
    }
}
if ($null -eq $UseFtps) { $UseFtps = $true }

# ---------- Resolve project root ---------------------------------------------
$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$ftpBase     = "ftp://{0}/{1}" -f $FtpHost, ($FtpRoot.TrimStart('/'))

Write-Host ""
Write-Host "Deploying from $ProjectRoot" -ForegroundColor Cyan
Write-Host "          to   $ftpBase     (FTPS=$UseFtps)" -ForegroundColor Cyan
Write-Host ""

$cred = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)

# ---------- File selection ----------------------------------------------------
$includeRoot = @(
    "index.html", "styles.css", "script.js", "README.md", "content.json"
)
$includeDirs = @("assets", "admin", "system-demo")
$excludeMatches = @(
    "*.zip", "*.log", "*.tmp", ".DS_Store", "Thumbs.db",
    "*.secret", ".env", ".env.local"
)

function Test-Excluded([string]$relPath) {
    foreach ($m in $excludeMatches) { if ($relPath -like $m) { return $true } }
    if ($relPath -like '*\.git*' -or $relPath -like '.git*') { return $true }
    return $false
}

# Build the list of files to send.
$files = @()
foreach ($f in $includeRoot) {
    $p = Join-Path $ProjectRoot $f
    if (Test-Path $p) {
        $files += [PSCustomObject]@{ Local = $p; Remote = $f }
    }
}
foreach ($d in $includeDirs) {
    $abs = Join-Path $ProjectRoot $d
    if (-not (Test-Path $abs)) { continue }
    Get-ChildItem -Path $abs -Recurse -File | ForEach-Object {
        $rel = $_.FullName.Substring($ProjectRoot.Length + 1) -replace '\\','/'
        if (-not (Test-Excluded $rel)) {
            $files += [PSCustomObject]@{ Local = $_.FullName; Remote = $rel }
        }
    }
}

# Ensure each remote directory exists; remember which we've already made.
$createdDirs = @{ "" = $true }
function Ensure-RemoteDir([string]$remoteDir) {
    if ([string]::IsNullOrEmpty($remoteDir) -or $createdDirs.ContainsKey($remoteDir)) { return }
    $parts = $remoteDir -split '/'
    $cur = ""
    foreach ($p in $parts) {
        if ([string]::IsNullOrEmpty($p)) { continue }
        $cur = if ($cur) { "$cur/$p" } else { $p }
        if ($createdDirs.ContainsKey($cur)) { continue }
        try {
            $req = [System.Net.FtpWebRequest]::Create("$ftpBase/$cur")
            $req.Method      = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
            $req.Credentials = $cred
            $req.EnableSsl   = [bool]$UseFtps
            $req.UsePassive  = $true
            $req.UseBinary   = $true
            $req.KeepAlive   = $false
            $resp = $req.GetResponse()
            $resp.Close()
        } catch [System.Net.WebException] {
            # 550 = already exists / no permission; treat as OK.
        }
        $createdDirs[$cur] = $true
    }
}

function Send-File([string]$localPath, [string]$remotePath) {
    Ensure-RemoteDir (Split-Path $remotePath -Parent)
    $req = [System.Net.FtpWebRequest]::Create("$ftpBase/$remotePath")
    $req.Method      = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $req.Credentials = $cred
    $req.EnableSsl   = [bool]$UseFtps
    $req.UsePassive  = $true
    $req.UseBinary   = $true
    $req.KeepAlive   = $false

    $bytes = [System.IO.File]::ReadAllBytes($localPath)
    $req.ContentLength = $bytes.Length
    $stream = $req.GetRequestStream()
    $stream.Write($bytes, 0, $bytes.Length)
    $stream.Close()
    $resp = $req.GetResponse()
    $resp.Close()
}

# ---------- Upload ------------------------------------------------------------
$total = $files.Count
$i = 0
$failed = @()
foreach ($f in $files) {
    $i++
    $pct = [int](($i / $total) * 100)
    Write-Progress -Activity "Uploading to $FtpHost" `
                   -Status ("{0}/{1} {2}" -f $i, $total, $f.Remote) `
                   -PercentComplete $pct
    try {
        Send-File $f.Local $f.Remote
        Write-Host (" [{0,3}/{1}] {2}" -f $i, $total, $f.Remote) -ForegroundColor DarkGray
    } catch {
        Write-Host (" [{0,3}/{1}] FAILED  {2}  -- {3}" -f $i, $total, $f.Remote, $_.Exception.Message) -ForegroundColor Red
        $failed += $f.Remote
    }
}
Write-Progress -Activity "Uploading to $FtpHost" -Completed

# ---------- Summary -----------------------------------------------------------
Write-Host ""
if ($failed.Count -eq 0) {
    Write-Host ("Deployed {0} files successfully." -f $total) -ForegroundColor Green
    Write-Host ""
    Write-Host "Visit your site:" -ForegroundColor Cyan
    Write-Host "  http://$FtpHost/"             -ForegroundColor Cyan
    Write-Host "  http://$FtpHost/admin/"       -ForegroundColor Cyan
    Write-Host "  http://$FtpHost/system-demo/" -ForegroundColor Cyan
} else {
    Write-Host ("Deployed with {0} failures out of {1}:" -f $failed.Count, $total) -ForegroundColor Yellow
    $failed | ForEach-Object { Write-Host "  - $_" -ForegroundColor Yellow }
    exit 1
}
