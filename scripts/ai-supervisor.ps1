param(
    [string]$BindHost = "127.0.0.1",
    [int]$Port = 8000,
    [int]$SleepSeconds = 20,
    [int]$AutoCreateIntervalMinutes = 5
)

$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$php = "php"
$artisan = Join-Path $root "artisan"
$logFile = Join-Path $root "storage\logs\ai-supervisor.log"
$lockFile = Join-Path $root "storage\framework\ai-supervisor.pid"
$autoCreateTickFile = Join-Path $root "storage\framework\ai-supervisor-autocreate.tick"
$hotFile = Join-Path $root "public\hot"

function Write-Log {
    param([string]$Message)
    $line = "[{0}] {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $Message
    Add-Content -Path $logFile -Value $line
    Write-Output $line
}

function Ensure-Directories {
    $logDir = Split-Path -Parent $logFile
    $lockDir = Split-Path -Parent $lockFile
    if (-not (Test-Path $logDir)) {
        New-Item -Path $logDir -ItemType Directory -Force | Out-Null
    }
    if (-not (Test-Path $lockDir)) {
        New-Item -Path $lockDir -ItemType Directory -Force | Out-Null
    }
}

function Test-Port {
    param([string]$HostName, [int]$PortNumber)
    try {
        $result = Test-NetConnection -ComputerName $HostName -Port $PortNumber -WarningAction SilentlyContinue
        return [bool]$result.TcpTestSucceeded
    } catch {
        return $false
    }
}

function Resolve-HotUrl {
    if (-not (Test-Path $hotFile)) {
        return $null
    }

    try {
        $url = (Get-Content -Path $hotFile -ErrorAction Stop | Select-Object -First 1).Trim()
        if ([string]::IsNullOrWhiteSpace($url)) {
            return $null
        }
        return $url
    } catch {
        return $null
    }
}

function Remove-StaleHotFileIfNeeded {
    $hotUrl = Resolve-HotUrl
    if (-not $hotUrl) {
        return
    }

    try {
        $uri = [Uri]$hotUrl
        $hotHost = if ([string]::IsNullOrWhiteSpace($uri.Host)) { "127.0.0.1" } else { $uri.Host }
        $hotPort = if ($uri.Port -gt 0) { $uri.Port } else { 5173 }

        if (-not (Test-Port -HostName $hotHost -PortNumber $hotPort)) {
            Remove-Item -Path $hotFile -Force -ErrorAction SilentlyContinue
            if (-not (Test-Path $hotFile)) {
                Write-Log "Removed stale Vite hot file ($hotUrl) because dev server is not reachable."
            } else {
                Write-Log "Failed to remove stale Vite hot file ($hotUrl). Check file permissions."
            }
        }
    } catch {
        Remove-Item -Path $hotFile -Force -ErrorAction SilentlyContinue
        if (-not (Test-Path $hotFile)) {
            Write-Log "Removed invalid Vite hot file content."
        } else {
            Write-Log "Failed to remove invalid Vite hot file content. Check file permissions."
        }
    }
}

function Start-ServeIfNeeded {
    if (-not (Test-Port -HostName $BindHost -PortNumber $Port)) {
        Write-Log "Web server not reachable on $BindHost`:$Port. Starting artisan serve."
        Start-Process -FilePath $php -ArgumentList "$artisan serve --host=$BindHost --port=$Port" -WorkingDirectory $root -WindowStyle Hidden | Out-Null
        Start-Sleep -Seconds 2
    }
}

function Run-ScheduleTick {
    & $php $artisan schedule:run | Out-Null
}

function Run-QueueDrain {
    & $php $artisan queue:work --once --queue=default --sleep=1 --tries=1 --timeout=120 | Out-Null
}

function Run-AutoCreateFallbackIfDue {
    if ($AutoCreateIntervalMinutes -le 0) {
        return
    }

    $now = Get-Date
    $lastRun = $null

    if (Test-Path $autoCreateTickFile) {
        try {
            $raw = (Get-Content -Path $autoCreateTickFile -ErrorAction Stop | Select-Object -First 1).Trim()
            if (-not [string]::IsNullOrWhiteSpace($raw)) {
                $lastRun = [DateTime]::Parse($raw)
            }
        } catch {
            $lastRun = $null
        }
    }

    if ($lastRun -and (($now - $lastRun).TotalMinutes -lt $AutoCreateIntervalMinutes)) {
        return
    }

    try {
        & $php $artisan ai-agents:auto-create --count=1 | Out-Null
        Set-Content -Path $autoCreateTickFile -Value $now.ToString("o")
        Write-Log "Auto-create fallback tick executed."
    } catch {
        Write-Log ("Auto-create fallback failed: " + $_.Exception.Message)
    }
}

function Acquire-Lock {
    if (Test-Path $lockFile) {
        $existingPid = Get-Content -Path $lockFile -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($existingPid) {
            try {
                $null = Get-Process -Id ([int]$existingPid) -ErrorAction Stop
                throw "Supervisor already running with PID $existingPid"
            } catch {
                # stale lock, continue
            }
        }
    }

    Set-Content -Path $lockFile -Value $PID
}

function Release-Lock {
    if (Test-Path $lockFile) {
        Remove-Item -Path $lockFile -Force -ErrorAction SilentlyContinue
    }
}

Ensure-Directories
Acquire-Lock
Write-Log "AI supervisor started (PID=$PID, host=$BindHost, port=$Port)."

try {
    while ($true) {
        try {
            Remove-StaleHotFileIfNeeded
            Start-ServeIfNeeded
            Run-ScheduleTick
            Run-AutoCreateFallbackIfDue
            Run-QueueDrain
        } catch {
            Write-Log ("Loop error: " + $_.Exception.Message)
        }

        Start-Sleep -Seconds $SleepSeconds
    }
}
finally {
    Release-Lock
    Write-Log "AI supervisor stopped."
}
