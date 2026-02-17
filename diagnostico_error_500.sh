#!/bin/bash
# Script de diagnóstico rápido para el error 500

echo "🔍 DIAGNÓSTICO ERROR 500 - PEDIDOS CARTERA"
echo "=========================================="

echo ""
echo "📍 1. Revisando logs de Laravel (últimos 20 errores):"
tail -20 storage/logs/laravel.log | grep -E "(ERROR|Exception|500|CARTERA)" --color=always

echo ""
echo "📍 2. Buscando errores de CARTERA específicamente:"
grep -n "\[CARTERA\]" storage/logs/laravel.log | tail -10

echo ""
echo "📍 3. Verificando espacio en disco:"
df -h | head -5

echo ""
echo "📍 4. Estado de servicios:"
sudo systemctl status nginx --no-pager -l | head -10

echo ""
echo "📍 5. Logs de Nginx (últimos 10 errores):"
sudo tail -10 /var/log/nginx/error.log 2>/dev/null || echo "No se puede acceder a logs de nginx"

echo ""
echo "📍 6. Verificando permisos de storage:"
ls -la storage/logs/ | head -5

echo ""
echo "📍 7. Probando endpoint directamente:"
curl -X POST "http://localhost/api/cartera/pedidos/1/aprobar" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -w "\nHTTP Code: %{http_code}\n" \
     -s \
     -o /dev/null

echo ""
echo "=========================================="
echo "✅ Diagnóstico completado"
echo ""
echo "📝 Si ves errores en los logs de Laravel, esa es la causa"
echo "📝 Si el curl retorna 500, el problema está en la aplicación"
echo "📝 Si hay problemas de permisos, corre: sudo chown -R www-data:www-data storage"
