$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$pidFile = Join-Path $root "storage\framework\ai-supervisor.pid"
$logFile = Join-Path $root "storage\logs\ai-supervisor-launcher.log"
$runner = Join-Path $root "scripts\ai-supervisor.ps1"

function Write-Log {
    param([string]$Message)
    $line = "[{0}] {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $Message
    Add-Content -Path $logFile -Value $line
}

function Is-SupervisorRunning {
    if (-not (Test-Path $pidFile)) {
        return $false
    }

    $existing = Get-Content -Path $pidFile -ErrorAction SilentlyContinue | Select-Object -First 1
    if (-not $existing) {
        return $false
    }

    try {
        $null = Get-Process -Id ([int]$existing) -ErrorAction Stop
        return $true
    } catch {
        return $false
    }
}

if (-not (Test-Path $runner)) {
    Write-Log "Supervisor runner not found: $runner"
    exit 1
}

if (Is-SupervisorRunning) {
    exit 0
}

try {
    if (Test-Path $pidFile) {
        Remove-Item -Path $pidFile -Force -ErrorAction SilentlyContinue
    }

    Start-Process -FilePath "powershell.exe" `
        -ArgumentList "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$runner`"" `
        -WorkingDirectory $root `
        -WindowStyle Hidden | Out-Null

    Write-Log "Supervisor started by launcher."
} catch {
    Write-Log ("Failed to start supervisor: " + $_.Exception.Message)
    exit 1
}

