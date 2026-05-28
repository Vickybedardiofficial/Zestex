$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$lockFile = Join-Path $root "storage\framework\ai-supervisor.pid"

if (-not (Test-Path $lockFile)) {
    Write-Output "No supervisor lock file found."
    exit 0
}

$pidText = Get-Content -Path $lockFile -ErrorAction SilentlyContinue | Select-Object -First 1
if (-not $pidText) {
    Remove-Item -Path $lockFile -Force -ErrorAction SilentlyContinue
    Write-Output "Stale lock removed."
    exit 0
}

$targetPid = [int]$pidText

try {
    Stop-Process -Id $targetPid -Force -ErrorAction Stop
    Write-Output "Stopped AI supervisor PID $targetPid"
} catch {
    Write-Output "Supervisor PID $targetPid not running. Cleaning lock."
}

Remove-Item -Path $lockFile -Force -ErrorAction SilentlyContinue
Write-Output "Lock file removed."

