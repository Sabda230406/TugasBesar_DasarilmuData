Write-Host "Checking ports..." -ForegroundColor Cyan

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$mlApiPath = Join-Path $projectRoot "ml-api"
$laravelPath = Join-Path $projectRoot "laravel-app"
$venvPython = Join-Path $projectRoot ".venv/Scripts/python.exe"

$portOwnerPid = (Get-NetTCPConnection -LocalPort 5001 -ErrorAction SilentlyContinue | Select-Object -First 1).OwningProcess
if ($portOwnerPid) {
    Write-Host "Port 5001 already in use by PID $portOwnerPid" -ForegroundColor Yellow
}

$portOwnerPid = (Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue | Select-Object -First 1).OwningProcess
if ($portOwnerPid) {
    Write-Host "Port 8000 already in use by PID $portOwnerPid" -ForegroundColor Yellow
}

$pythonExe = $null
if (Test-Path $venvPython) {
    $pythonExe = $venvPython
} elseif (Get-Command python -ErrorAction SilentlyContinue) {
    $pythonExe = "python"
} elseif (Get-Command py -ErrorAction SilentlyContinue) {
    $pythonExe = "py"
}

if (-not $pythonExe) {
    Write-Host "Python was not found. Install Python or create .venv first." -ForegroundColor Red
    exit 1
}

Write-Host "Starting Flask API on port 5001..." -ForegroundColor Cyan
if ($pythonExe -eq "py") {
    Start-Process -NoNewWindow -WorkingDirectory $mlApiPath -FilePath "py" -ArgumentList "app.py"
} else {
    Start-Process -NoNewWindow -WorkingDirectory $mlApiPath -FilePath $pythonExe -ArgumentList "app.py"
}

Write-Host "Starting Laravel..." -ForegroundColor Cyan
Start-Process -NoNewWindow -WorkingDirectory $laravelPath -FilePath "php" -ArgumentList "artisan", "serve"

Write-Host "Both services started. Open http://127.0.0.1:8000" -ForegroundColor Green
