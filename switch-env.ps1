param([Parameter(Mandatory=$true)][ValidateSet('development','production')][string]$env)
if($env -eq 'development'){Copy-Item '.env.development' '.env' -Force; Write-Host 'DESARROLLO' -ForegroundColor Green}
else{Copy-Item '.env.production' '.env' -Force; Write-Host 'PRODUCCION' -ForegroundColor Green}
php artisan config:clear; php artisan cache:clear
Write-Host 'Listo!' -ForegroundColor Green
