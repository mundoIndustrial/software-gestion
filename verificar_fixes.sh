#!/bin/bash
# Script para verificar estado de los FIXES implementados
# Ejecutar: bash verificar_fixes.sh

echo ""
echo "════════════════════════════════════════════════════════════════════════════════════"
echo "✅ VERIFICACIÓN DE FIXES IMPLEMENTADOS (16/01/2026)"
echo "════════════════════════════════════════════════════════════════════════════════════"
echo ""

FIXES_OK=0
FIXES_TOTAL=5

# FIX #1
echo "FIX #1: Extracción color/tela desde telasAgregadas"
if grep -q "color: colorPrenda" public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js; then
    echo "  ✅ IMPLEMENTADO en gestion-items-pedido.js (líneas 902-903)"
    FIXES_OK=$((FIXES_OK + 1))
else
    echo "  ❌ NO ENCONTRADO"
fi
echo ""

# FIX #2
echo "FIX #2: Detección mejorada de File objects anidados"
if grep -q "else if (imgObj.file instanceof File)" public/js/modulos/crear-pedido/configuracion/api-pedidos-editable.js; then
    echo "  ✅ IMPLEMENTADO en api-pedidos-editable.js (líneas 195-240)"
    FIXES_OK=$((FIXES_OK + 1))
else
    echo "  ❌ NO ENCONTRADO"
fi
echo ""

# FIX #3
echo "FIX #3: Conversión mejorada a FormData"
if grep -q "const archivo = img instanceof File" public/js/modulos/crear-pedido/configuracion/api-pedidos-editable.js; then
    echo "  ✅ IMPLEMENTADO en api-pedidos-editable.js (líneas 334-336)"
    FIXES_OK=$((FIXES_OK + 1))
else
    echo "  ❌ NO ENCONTRADO"
fi
echo ""

# FIX #4
echo "FIX #4: Parsing JSON en FormData en backend"
if grep -q "json_decode(\$cantidadTalla, true)" app/Http/Controllers/Asesores/CrearPedidoEditableController.php; then
    echo "  ✅ IMPLEMENTADO en CrearPedidoEditableController.php (líneas 195-210)"
    FIXES_OK=$((FIXES_OK + 1))
else
    echo "  ❌ NO ENCONTRADO"
fi
echo ""

# FIX #5
echo "FIX #5: Eliminación de campos duplicados"
DUPLICADOS=$(grep -c "obs_manga\|obs_bolsillos\|obs_broche\|obs_reflectivo" public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js)
if [ $DUPLICADOS -lt 10 ]; then
    echo "  ✅ IMPLEMENTADO - campos duplicados removidos de gestion-items-pedido.js"
    FIXES_OK=$((FIXES_OK + 1))
else
    echo "  ⚠️ Aún hay referencias a campos duplicados"
fi
echo ""

echo "════════════════════════════════════════════════════════════════════════════════════"
echo "RESULTADO: $FIXES_OK/$FIXES_TOTAL fixes verificados"
echo "════════════════════════════════════════════════════════════════════════════════════"
echo ""

if [ $FIXES_OK -eq $FIXES_TOTAL ]; then
    echo "✅ TODOS LOS FIXES ESTÁN IMPLEMENTADOS"
    echo ""
    echo "Los nuevos pedidos deberían guardarse completos con:"
    echo "  ✓ color_id (no NULL)"
    echo "  ✓ tela_id (no NULL)"
    echo "  ✓ tallas_dama con valores"
    echo "  ✓ Imágenes guardadas"
    echo ""
else
    echo "⚠️ FALTAN IMPLEMENTAR ALGUNOS FIXES"
    echo ""
fi

echo "════════════════════════════════════════════════════════════════════════════════════"
echo ""
