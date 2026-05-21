# -----------------------------------------------------------------------------
# Local credentials for deploy-cpanel-ftp.ps1
#
# HOW TO USE
#   1. Copy this file in the SAME folder and rename the copy to:
#        deploy-credentials.ps1     (without `.example`)
#   2. Replace the placeholders below with your real FTP credentials.
#   3. Save. The real file is already excluded by ../.gitignore, so it will
#      never be staged, committed, or pushed.
#   4. Never share the real file with anyone.
# -----------------------------------------------------------------------------

# FTP host (use the hostname or IP from your hosting provider).
$FtpHost = "inganzongari.rw"     # or "197.243.23.16" while DNS propagates

# cPanel account username (same one you use to sign into cPanel).
$FtpUser = "REPLACE_WITH_USERNAME"

# cPanel account password (PLAIN TEXT — keep this file private).
# If you'd rather not paste it here, see the SecureString section below.
$FtpPass = "REPLACE_WITH_PASSWORD"

# Remote folder to deploy into. For a primary domain this is `public_html`.
# For an add-on domain it would be e.g. `public_html/inganzongari.rw`.
$FtpRoot = "public_html"

# Use FTPS (FTP over TLS). Recommended ON.
$UseFtps = $true

# --- Optional: more secure password handling ---------------------------------
# Comment out $FtpPass above and use this block instead. The script will
# prompt you for the password each run instead of reading it from disk.
#
#   $FtpPass = $null
#   if (-not $env:DEPLOY_FTP_PASS) {
#       $sec = Read-Host "FTP password for $FtpUser@$FtpHost" -AsSecureString
#       $FtpPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
#           [Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec))
#   } else {
#       $FtpPass = $env:DEPLOY_FTP_PASS
#   }
