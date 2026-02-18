#!/bin/bash
# Script para verificar tiempo real en VPS

echo "========================================"
echo "  VERIFICACIÓN DE TIEMPO REAL - VPS"
echo "========================================"
echo ""

echo "[1/5] Verificando Reverb..."
if pgrep -f "reverb:start" > /dev/null; then
    echo "  ✓ Reverb está corriendo"
    ps aux | grep reverb | grep -v grep
else
    echo "  ✗ Reverb no está corriendo"
fi
echo ""

echo "[2/5] Verificando puerto 8080..."
if netstat -tlnp | grep :8080 > /dev/null; then
    echo "  ✓ Puerto 8080 está escuchando"
    netstat -tlnp | grep :8080
else
    echo "  ✗ Puerto 8080 no está escuchando"
fi
echo ""

echo "[3/5] Verificando Nginx..."
if nginx -t > /dev/null 2>&1; then
    echo "  ✓ Configuración de Nginx es válida"
else
    echo "  ✗ Error en configuración de Nginx:"
    nginx -t
fi
echo ""

echo "[4/5] Verificando conexión WebSocket local..."
if curl -s -I "http://127.0.0.1:8080" | head -1 | grep 200 > /dev/null; then
    echo "  ✓ Reverb responde localmente"
else
    echo "  ✗ Reverb no responde localmente"
fi
echo ""

echo "[5/5] Verificando conexión WebSocket externa..."
if curl -s -I "https://sistemamundoindustrial.online/app/" | head -1 | grep 200 > /dev/null; then
    echo "  ✓ WebSocket accesible externamente"
else
    echo "  ✗ WebSocket no accesible externamente"
fi
echo ""

echo "========================================"
echo "  LOGS RECIENTES"
echo "========================================"
echo ""
echo "Reverb log (últimas 10 líneas):"
tail -10 ~/app/reverb.log 2>/dev/null || echo "No hay log de Reverb"
echo ""
echo "Laravel log (últimas 5 líneas):"
tail -5 ~/app/storage/logs/laravel.log 2>/dev/null || echo "No hay log de Laravel"
