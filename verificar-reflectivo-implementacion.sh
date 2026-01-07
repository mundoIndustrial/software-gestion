#!/bin/bash

# Script de verificación - Implementación Reflectivo Sin Cotización
# Este script verifica que todos los archivos necesarios estén en su lugar

echo "🔍 Verificando implementación de Reflectivo Sin Cotización..."
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Array de archivos a verificar
declare -a FILES=(
    "public/js/modulos/crear-pedido/gestor-reflectivo-sin-cotizacion.js"
    "public/js/modulos/crear-pedido/renderizador-reflectivo-sin-cotizacion.js"
    "public/js/modulos/crear-pedido/funciones-reflectivo-sin-cotizacion.js"
)

# Verificar cada archivo
MISSING=0
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗${NC} $file"
        MISSING=$((MISSING + 1))
    fi
done

echo ""
echo "📝 Verificando inclusión en blade..."

if grep -q "renderizador-reflectivo-sin-cotizacion" resources/views/asesores/pedidos/crear-desde-cotizacion-editable.blade.php; then
    echo -e "${GREEN}✓${NC} renderizador-reflectivo incluido"
else
    echo -e "${RED}✗${NC} renderizador-reflectivo NO incluido"
fi

if grep -q "funciones-reflectivo-sin-cotizacion" resources/views/asesores/pedidos/crear-desde-cotizacion-editable.blade.php; then
    echo -e "${GREEN}✓${NC} funciones-reflectivo incluidas"
else
    echo -e "${RED}✗${NC} funciones-reflectivo NO incluidas"
fi

echo ""
echo "🔧 Verificando función manejarCambiaTipoPedido..."

if grep -q "crearPedidoTipoReflectivoSinCotizacion" resources/views/asesores/pedidos/crear-desde-cotizacion-editable.blade.php; then
    echo -e "${GREEN}✓${NC} Manejador de reflectivo agregado"
else
    echo -e "${RED}✗${NC} Manejador de reflectivo NO agregado"
fi

echo ""

if [ $MISSING -eq 0 ]; then
    echo -e "${GREEN}✅ ¡Implementación completa! Todos los archivos están en su lugar.${NC}"
else
    echo -e "${RED}❌ Faltan $MISSING archivo(s). Por favor, revisar la implementación.${NC}"
fi

echo ""
echo "📋 Próximos pasos:"
echo "1. Limpiar caché del navegador"
echo "2. Navegar a 'Crear Pedido'"
echo "3. Seleccionar 'Nuevo Pedido'"
echo "4. Seleccionar tipo 'REFLECTIVO'"
echo "5. Completar el formulario"
echo "6. Crear el pedido"
echo ""
echo "✅ Verificación completada"
