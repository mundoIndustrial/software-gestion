#!/bin/bash

# TEST: Verificar que los cambios para captura de múltiples telas están implementados

echo "🧪 PRUEBA DE MÚLTIPLES TELAS EN COTIZACIÓN"
echo "=========================================="
echo ""

# 1. Verificar template-producto.blade.php
echo "✓ Verificando template-producto.blade.php..."
if grep -q 'data-tela-index' resources/views/components/template-producto.blade.php; then
    echo "  ✅ Encontrado: data-tela-index en template"
else
    echo "  ❌ FALTA: data-tela-index en template"
fi

if grep -q 'productos_friendly\[\]\[telas\]\[' resources/views/components/template-producto.blade.php; then
    echo "  ✅ Encontrado: estructura de telas indexadas"
else
    echo "  ❌ FALTA: estructura de telas indexadas"
fi

echo ""

# 2. Verificar productos.js
echo "✓ Verificando productos.js..."
if grep -q "telasSeleccionadas\[productoId\] = {" public/js/asesores/cotizaciones/productos.js; then
    echo "  ✅ Encontrado: estructura de objeto para telasSeleccionadas"
else
    echo "  ❌ FALTA: estructura de objeto para telasSeleccionadas"
fi

if grep -q "const telaIndex = filaTelaActual.getAttribute('data-tela-index')" public/js/asesores/cotizaciones/productos.js; then
    echo "  ✅ Encontrado: obtener telaIndex en agregarFotoTela"
else
    echo "  ❌ FALTA: obtener telaIndex"
fi

echo ""

# 3. Verificar FormModule.js
echo "✓ Verificando FormModule.js..."
if grep -q 'const tblasRows = card.querySelectorAll' public/js/asesores/cotizaciones/modules/FormModule.js; then
    echo "  ✅ Encontrado: procesamiento de múltiples filas de telas"
else
    echo "  ❌ FALTA: procesamiento de múltiples filas"
fi

echo ""

# 4. Verificar AsesoresController.php
echo "✓ Verificando AsesoresController.php..."
if grep -q "productosKey.'.*.telas.*.tela_id'" app/Http/Controllers/AsesoresController.php; then
    echo "  ✅ Encontrado: validación de múltiples telas"
else
    echo "  ❌ FALTA: validación de múltiples telas"
fi

echo ""

# 5. Verificar PedidoPrendaService.php
echo "✓ Verificando PedidoPrendaService.php..."
if grep -q 'private function obtenerPrimeraTela' app/Application/Services/PedidoPrendaService.php; then
    echo "  ✅ Encontrado: método obtenerPrimeraTela"
else
    echo "  ❌ FALTA: método obtenerPrimeraTela"
fi

echo ""
echo "=========================================="
echo "🎉 PRUEBA COMPLETADA"
echo ""
echo "📋 PASOS PARA PROBAR MANUALMENTE:"
echo "1. Ir a http://servermi:8000/asesores/pedidos/create"
echo "2. Agregar una prenda"
echo "3. Hacer clic en 'Agregar Tela' (debajo de la tabla de colores/telas)"
echo "4. Completar datos de 2-3 telas diferentes"
echo "5. Para cada tela, agregar fotos"
echo "6. Guardar el formulario"
echo "7. Verificar que todas las telas y fotos se guardaron correctamente"
echo ""
