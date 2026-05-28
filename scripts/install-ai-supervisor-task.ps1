$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$launcher = Join-Path $root "scripts\ai-supervisor-launcher.ps1"
$taskName = "Zestex-AI-Supervisor-Guard"
$bootTaskName = "Zestex-AI-Supervisor-Guard-Boot"
$startupDir = Join-Path $env:APPDATA "Microsoft\Windows\Start Menu\Programs\Startup"
$startupCmd = Join-Path $startupDir "zestex-ai-supervisor-guard.cmd"

if (-not (Test-Path $launcher)) {
    Write-Error "Launcher script missing: $launcher"
    exit 1
}

$escapedLauncher = $launcher.Replace('"', '\"')
$taskCmd = "powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$escapedLauncher`""
$startupLine = "@echo off`r`npowershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$launcher`"`r`n"

function Install-CurrentUserFallback {
    schtasks /Create /TN $taskName /SC MINUTE /MO 1 /TR $taskCmd /F | Out-Null
    schtasks /Run /TN $taskName | Out-Null

    if (-not (Test-Path $startupDir)) {
        New-Item -Path $startupDir -ItemType Directory -Force | Out-Null
    }
    Set-Content -Path $startupCmd -Value $startupLine -Encoding ASCII

    Write-Output "Installed user-level guard task: $taskName"
    Write-Output "Installed startup launcher: $startupCmd"
}

try {
    # Always-on guard: runs every minute as SYSTEM, even after logout/reboot.
    schtasks /Create /TN $taskName /SC MINUTE /MO 1 /TR $taskCmd /RU SYSTEM /RL HIGHEST /F | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "SYSTEM minute task create failed with code $LASTEXITCODE" }

    # Boot trigger: starts guard once at machine startup.
    schtasks /Create /TN $bootTaskName /SC ONSTART /TR $taskCmd /RU SYSTEM /RL HIGHEST /F | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "SYSTEM boot task create failed with code $LASTEXITCODE" }

    schtasks /Run /TN $taskName | Out-Null
    schtasks /Run /TN $bootTaskName | Out-Null
    Write-Output "Installed and started scheduled task: $taskName"
    Write-Output "Installed and started scheduled task: $bootTaskName"
    Write-Output "Tasks run as SYSTEM and auto-restart AI supervisor if it is down."
} catch {
    Write-Output ("SYSTEM-mode task install not available: " + $_.Exception.Message)
    Write-Output "Falling back to current-user persistent guard..."
    Install-CurrentUserFallback
}
