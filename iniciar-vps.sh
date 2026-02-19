#!/bin/bash

echo "======================================="
echo "   Iniciando servicios Mundo Industrial"
echo "======================================="

# 1️⃣ Matar procesos anteriores (evita duplicados)
echo "Deteniendo procesos anteriores..."
pkill -f "reverb:start"
pkill -f "queue:work"

sleep 2

# 2️⃣ Limpiar cache correctamente
echo "Limpiando cache..."
php artisan optimize:clear

# 3️⃣ Iniciar Reverb en puerto 8081
echo "Iniciando Reverb en puerto 8081..."
nohup php artisan reverb:start --host=0.0.0.0 --port=8081 \
> storage/logs/reverb.log 2>&1 &

echo "Reverb iniciado (PID: $!)"

# 4️⃣ Iniciar Queue Worker
echo "Iniciando Queue Worker..."
nohup php artisan queue:work --tries=3 --sleep=1 --timeout=90 \
> storage/logs/queue.log 2>&1 &

echo "Queue Worker iniciado (PID: $!)"

echo ""
echo "✅ Servicios iniciados correctamente."
echo ""
echo "Logs disponibles en:"
echo "  tail -f storage/logs/reverb.log"
echo "  tail -f storage/logs/queue.log"
echo ""
