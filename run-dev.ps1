Write-Host "Checking ports..." -ForegroundColor Cyan

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$mlApiPath = Join-Path $projectRoot "ml-api"
$laravelPath = Join-Path $projectRoot "laravel-app"
$venvPython = Join-Path $projectRoot ".venv/Scripts/python.exe"

$flaskPid = (Get-NetTCPConnection -LocalPort 5001 -ErrorAction SilentlyContinue | Select-Object -First 1).OwningProcess
if ($flaskPid) {
    Write-Host "Port 5001 already in use by PID $flaskPid" -ForegroundColor Yellow
}

$laravelPort = 8000
while (Get-NetTCPConnection -LocalPort $laravelPort -ErrorAction SilentlyContinue) {
    Write-Host "Port $laravelPort already in use. Trying next port..." -ForegroundColor Yellow
    $laravelPort++
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

if (-not $flaskPid) {
    Write-Host "Starting Flask API on port 5001..." -ForegroundColor Cyan
    if ($pythonExe -eq "py") {
        Start-Process -WindowStyle Hidden -WorkingDirectory $mlApiPath -FilePath "py" -ArgumentList "app.py"
    } else {
        Start-Process -WindowStyle Hidden -WorkingDirectory $mlApiPath -FilePath $pythonExe -ArgumentList "app.py"
    }
}

Write-Host "Starting Laravel on port $laravelPort..." -ForegroundColor Cyan
Start-Process -WindowStyle Hidden -WorkingDirectory $laravelPath -FilePath "php" -ArgumentList "artisan", "serve", "--port=$laravelPort"

Write-Host "Starting Laravel queue worker..." -ForegroundColor Cyan
Start-Process -WindowStyle Hidden -WorkingDirectory $laravelPath -FilePath "php" -ArgumentList "artisan", "queue:work", "--tries=1", "--timeout=600"

Write-Host "Services started. Open http://127.0.0.1:$laravelPort" -ForegroundColor Green
