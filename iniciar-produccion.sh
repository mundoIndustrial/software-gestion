#!/bin/bash

cd ~/app

echo ""
echo "========================================"
echo "  MUNDO INDUSTRIAL - PRODUCCION"
echo "========================================"
echo ""

HOSTNAME=$(hostname)

echo "[1/4] Detectando hostname..."
echo "      Hostname: $HOSTNAME"
echo ""

echo "[2/4] Configurando variables de entorno..."
echo "      - APP_ENV=production"
echo "      - REVERB_HOST=127.0.0.1"
echo "      - REVERB_PORT=8080"
echo "      - REVERB_SCHEME=http"
echo "      - Nginx hará proxy a wss://sistemamundoindustrial.online/app/"
echo ""

echo "[3/4] Compilando assets y configuración..."
# Cargar variables de entorno y compilar config
source ~/app/.env
npm run build
# Eliminar archivo hot de desarrollo (importante para producción)
rm -f ~/app/public/hot
# Regenerar config cache con variables de entorno explícitas (incluyendo SESSION)
APP_ENV=production DB_USERNAME=mundo DB_PASSWORD="${DB_PASSWORD}" SESSION_DRIVER=file SESSION_DOMAIN=sistemamundoindustrial.online php artisan config:clear
APP_ENV=production DB_USERNAME=mundo DB_PASSWORD="${DB_PASSWORD}" SESSION_DRIVER=file SESSION_DOMAIN=sistemamundoindustrial.online php artisan config:cache
php artisan route:cache
echo "      ✓ Assets y configuración compilados para produccion"
echo ""

echo "[4/4] Iniciando servicios..."
echo ""
echo "IMPORTANTE:"
echo "  - Asegurate que Nginx este corriendo"
echo "  - Reverb escucha en puerto 8080"
echo "  - Laravel usa php-fpm"
echo ""

echo "Iniciando Reverb (WebSocket Server) en background..."
nohup php artisan reverb:start --host=0.0.0.0 --port=8080 > reverb.log 2>&1 &
REVERB_PID=$!
echo "      PID de Reverb: $REVERB_PID"

# Verificar que Reverb se inició correctamente
sleep 2
if ps -p $REVERB_PID > /dev/null; then
    echo "      ✓ Reverb iniciado correctamente"
else
    echo "      ✗ Error al iniciar Reverb"
    exit 1
fi

echo ""
echo "========================================"
echo "  SERVIDOR DE PRODUCCION INICIADO"
echo "========================================"
echo ""
echo "URLs:"
echo " - https://sistemamundoindustrial.online"
echo " - WebSocket: wss://sistemamundoindustrial.online/app/"
echo ""
echo "Servicio Reverb:"
echo " - Escuchando en: 127.0.0.1:8080"
echo " - Proxy via Nginx a: /app/"
echo " - Acceso público: wss://sistemamundoindustrial.online/app/"
echo ""
echo "========================================"
echo ""
