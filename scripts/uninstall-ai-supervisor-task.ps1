$ErrorActionPreference = "Stop"

$taskName = "Zestex-AI-Supervisor-Guard"
$bootTaskName = "Zestex-AI-Supervisor-Guard-Boot"
$startupCmd = Join-Path $env:APPDATA "Microsoft\Windows\Start Menu\Programs\Startup\zestex-ai-supervisor-guard.cmd"

try {
    schtasks /Delete /TN $taskName /F | Out-Null
    Write-Output "Removed scheduled task: $taskName"
} catch {
    Write-Output "Scheduled task not found or already removed: $taskName"
}

try {
    schtasks /Delete /TN $bootTaskName /F | Out-Null
    Write-Output "Removed scheduled task: $bootTaskName"
} catch {
    Write-Output "Scheduled task not found or already removed: $bootTaskName"
}

if (Test-Path $startupCmd) {
    Remove-Item -Path $startupCmd -Force -ErrorAction SilentlyContinue
    Write-Output "Removed startup launcher: $startupCmd"
}
