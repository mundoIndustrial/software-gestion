#!/bin/bash
# Script para iniciar servicios en VPS

echo "Iniciando servicios para VPS..."

# 1. Limpiar cache
php artisan config:clear
php artisan cache:clear

# 2. Iniciar Reverb en background
nohup php artisan reverb:start > reverb.log 2>&1 &
echo "Reverb iniciado en background (PID: $!)"

# 3. Iniciar Queue Worker para eventos
nohup php artisan queue:work --tries=3 --sleep=1 > queue.log 2>&1 &
echo "Queue Worker iniciado en background (PID: $!)"

# 4. Iniciar servidor Laravel (opcional, si usas Nginx/Apache)
# nohup php artisan serve --host=0.0.0.0 --port=8000 > laravel.log 2>&1 &
# echo "Laravel Server iniciado en background (PID: $!)"

echo "Servicios iniciados. Revisa los logs:"
echo "  - Reverb: tail -f reverb.log"
echo "  - Queue:  tail -f queue.log"
echo "  - Laravel: tail -f storage/logs/laravel.log"
