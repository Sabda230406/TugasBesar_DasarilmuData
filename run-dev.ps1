Write-Host "Checking ports..." -ForegroundColor Cyan


$portOwnerPid = (Get-NetTCPConnection -LocalPort 5001 -ErrorAction SilentlyContinue | Select-Object -First 1).OwningProcess
if ($portOwnerPid) {
    Write-Host "Port 5001 already in use by PID $portOwnerPid" -ForegroundColor Yellow
}

$portOwnerPid = (Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue | Select-Object -First 1).OwningProcess
if ($portOwnerPid) {
    Write-Host "Port 8000 already in use by PID $portOwnerPid" -ForegroundColor Yellow
}

Write-Host "Starting Flask API on port 5001..." -ForegroundColor Cyan
Start-Process -NoNewWindow -FilePath "c:/Users/Sabduyyy/TugasBesar_DasarilmuData/.venv/Scripts/python.exe" `
    -ArgumentList "c:/Users/Sabduyyy/TugasBesar_DasarilmuData/ml-api/app.py"

Write-Host "Starting Laravel..." -ForegroundColor Cyan
Start-Process -NoNewWindow -WorkingDirectory "c:/Users/Sabduyyy/TugasBesar_DasarilmuData/laravel-app" `
    -FilePath "php" -ArgumentList "artisan", "serve"

Write-Host "Both services started. Open http://127.0.0.1:8000" -ForegroundColor Green
