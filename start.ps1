# Starts MySQL (once, shared with the other 4 sites in this workspace) and
# Dinsathi's own PHP dev server. Run it, then open the URL it prints —
# nothing to babysit.
#
# Usage:  powershell -ExecutionPolicy Bypass -File F:\Projects\dinsathi\start.ps1

$ErrorActionPreference = 'Stop'

$Php         = 'C:\xampp\php\php.exe'
$Mysqld      = 'C:\xampp\mysql\bin\mysqld.exe'
$MyIni       = 'C:\xampp\mysql\bin\my.ini'
$Root        = $PSScriptRoot
$Name        = 'dinsathi'
$Port        = 2030
$Fingerprint = 'দিনসাথী'

if (-not (Test-Path $Php)) { throw "PHP not found at $Php - edit the `$Php path at the top of this script." }
if (-not (Test-Path (Join-Path $Root '.env'))) { throw "$Root\.env is missing - copy .env.example to .env and fill it in first." }

# --- MySQL: one shared instance for every site's database -------------------
if (Get-Process mysqld -ErrorAction SilentlyContinue) {
    Write-Host "[mysql] already running" -ForegroundColor DarkGray
} else {
    Write-Host "[mysql] starting..." -ForegroundColor Cyan
    Start-Process $Mysqld -ArgumentList "--defaults-file=$MyIni", '--standalone' -WindowStyle Hidden
    Start-Sleep -Seconds 3
}

# --- This site's PHP dev server ----------------------------------------------
# A port being open doesn't mean it's THIS site - verify by content (the
# fingerprint), not just by socket state, before deciding to skip or warn.
$listening = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if ($listening) {
    $ownerPids = ($listening.OwningProcess | Select-Object -Unique) -join ','
    try {
        $probe = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/" -UseBasicParsing -TimeoutSec 5
        if ($probe.Content -match [regex]::Escape($Fingerprint)) {
            Write-Host "[$Name] already running on $Port (verified)" -ForegroundColor DarkGray
            Write-Host "Open: http://127.0.0.1:$Port/" -ForegroundColor Green
            exit 0
        } else {
            Write-Host "[$Name] CONFLICT: something else is answering on $Port (pid $ownerPids) - not $Name. Not starting a duplicate; free the port first." -ForegroundColor Red
            exit 1
        }
    } catch {
        Write-Host "[$Name] CONFLICT: port $Port is bound (pid $ownerPids) but not answering HTTP - not starting a duplicate." -ForegroundColor Red
        exit 1
    }
}

$logDir = Join-Path $Root 'storage\logs'
if (-not (Test-Path $logDir)) { New-Item -ItemType Directory -Path $logDir -Force | Out-Null }
$errLog = Join-Path $logDir 'php-dev-server.log'

Write-Host "[$Name] starting on http://127.0.0.1:$Port" -ForegroundColor Cyan
Start-Process $Php `
    -ArgumentList "-S 127.0.0.1:$Port -t public public/router-dev.php" `
    -WorkingDirectory $Root `
    -WindowStyle Hidden `
    -RedirectStandardError $errLog

Start-Sleep -Seconds 2
try {
    $resp = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/" -UseBasicParsing -TimeoutSec 5
    if ($resp.Content -match [regex]::Escape($Fingerprint)) {
        Write-Host "[$Name] up and verified - http://127.0.0.1:$Port/" -ForegroundColor Green
    } else {
        Write-Host "[$Name] responding on $Port but content doesn't match the expected app - check $errLog" -ForegroundColor Red
    }
} catch {
    Write-Host "[$Name] not responding yet - check $errLog" -ForegroundColor Red
}

Write-Host ""
Write-Host "To stop: Get-Process php | Where-Object { (Get-NetTCPConnection -OwningProcess `$_.Id -LocalPort $Port -ErrorAction SilentlyContinue) } | Stop-Process"
