#!/bin/bash

# Script: Optimizar EPP completamente
# Ejecutar: bash scripts/optimizar-epp.sh

echo "🚀 INICIANDO OPTIMIZACIÓN DE EPP..."
echo ""

echo "1️⃣  Ejecutando migración de índices..."
php artisan migrate --path=database/migrations/2026_01_26_optimize_epp_indexes.php
echo ""

echo "2️⃣  Limpiando caché anterior..."
php artisan epp:clear-cache
echo ""

echo "3️⃣  Verificando estado..."
php artisan epp:verificar-imagenes-ignorada
echo ""

echo "✅ OPTIMIZACIÓN COMPLETADA"
echo ""
echo "📊 Ahora las búsquedas deberían ser:"
echo "   • EPPs activos: < 1ms (caché)"
echo "   • Búsquedas: < 5ms (caché)"
echo "   • Por categoría: < 1ms (caché)"
echo ""
echo "💡 Tip: Para limpiar caché en futuro, ejecuta:"
echo "   php artisan epp:clear-cache"
