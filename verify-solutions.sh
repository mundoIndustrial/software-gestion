#!/bin/bash

# SCRIPT DE VERIFICACIÓN RÁPIDA - Soluciones Implementadas
# Verifica que todas las correcciones están en lugar correcto
# Uso: bash verify-solutions.sh

echo "======================================"
echo "VERIFICACIÓN RÁPIDA DE SOLUCIONES"
echo "======================================"
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

# Contador de verificaciones
PASS=0
FAIL=0

# VERIFICACIÓN 1: Método calcularCantidadTotalPrendas
echo "1️⃣  Verificando calcularCantidadTotalPrendas()..."
if grep -q "pedidos_procesos_prenda_tallas as pppt" "app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php"; then
    echo -e "${GREEN}✓ PASS${NC}: Query a tabla correcta"
    ((PASS++))
else
    echo -e "${RED}✗ FAIL${NC}: Query no está actualizada"
    ((FAIL++))
fi

if grep -q "procesos_prenda_detalle as ppd" "app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php"; then
    echo -e "${GREEN}✓ PASS${NC}: JOINs a tablas correctas"
    ((PASS++))
else
    echo -e "${RED}✗ FAIL${NC}: JOINs no encontrados"
    ((FAIL++))
fi

echo ""

# VERIFICACIÓN 2: Método editarEPPFormulario
echo "2️⃣  Verificando editarEPPFormulario()..."
if grep -q "editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes)" "public/js/modulos/crear-pedido/epp/services/epp-service.js"; then
    echo -e "${GREEN}✓ PASS${NC}: Firma correcta con todos los parámetros"
    ((PASS++))
else
    echo -e "${RED}✗ FAIL${NC}: Firma no tiene parámetros correctos"
    ((FAIL++))
fi

if grep -q "PARAMETROS COMPLETOS: id, nombre, codigo, categoria" "public/js/modulos/crear-pedido/epp/services/epp-service.js"; then
    echo -e "${GREEN}✓ PASS${NC}: Comentario de parámetros documentado"
    ((PASS++))
else
    echo -e "${YELLOW}⚠ WARN${NC}: Comentario no encontrado (no es crítico)"
fi

echo ""

# VERIFICACIÓN 3: Validación defensiva en obtenerDatosFactura
echo "3️⃣  Verificando validación defensiva en obtenerDatosFactura()..."
if grep -q "if (!\\$epp)" "app/Domain/Pedidos/Repositories/PedidoProduccionRepository.php"; then
    echo -e "${GREEN}✓ PASS${NC}: Guard defensivo para EPP null"
    ((PASS++))
else
    echo -e "${RED}✗ FAIL${NC}: Guard defensivo no encontrado"
    ((FAIL++))
fi

if grep -q "EPP sin relación válida, saltando" "app/Domain/Pedidos/Repositories/PedidoProduccionRepository.php"; then
    echo -e "${GREEN}✓ PASS${NC}: Logging de EPP sin relación"
    ((PASS++))
else
    echo -e "${RED}✗ FAIL${NC}: Logging no encontrado"
    ((FAIL++))
fi

echo ""

# VERIFICACIÓN 4: Sintaxis PHP
echo "4️⃣  Verificando sintaxis PHP..."
php -l "app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ PASS${NC}: CrearPedidoEditableController.php sin errores"
    ((PASS++))
else
    echo -e "${RED}✗ FAIL${NC}: CrearPedidoEditableController.php tiene errores"
    ((FAIL++))
fi

php -l "app/Domain/Pedidos/Repositories/PedidoProduccionRepository.php" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ PASS${NC}: PedidoProduccionRepository.php sin errores"
    ((PASS++))
else
    echo -e "${RED}✗ FAIL${NC}: PedidoProduccionRepository.php tiene errores"
    ((FAIL++))
fi

echo ""

# VERIFICACIÓN 5: Base de datos
echo "5️⃣  Verificando estructura de BD..."
echo -e "${YELLOW} Verificación manual requerida:${NC}"
echo "  - Ejecutar: SELECT COUNT(*) FROM pedidos_procesos_prenda_tallas;"
echo "  - Ejecutar: SELECT COUNT(*) FROM prenda_pedido_tallas;"
echo "  - Esperado: Primera > 0, Segunda = 0"

echo ""

# RESUMEN
echo "======================================"
echo "RESUMEN"
echo "======================================"
echo -e "${GREEN}✓ Pasadas: $PASS${NC}"
echo -e "${RED}✗ Fallidas: $FAIL${NC}"
echo ""

if [ $FAIL -eq 0 ]; then
    echo -e "${GREEN}🎉 TODAS LAS VERIFICACIONES PASARON${NC}"
    echo "Sistema está listo para testing"
    exit 0
else
    echo -e "${RED} ALGUNAS VERIFICACIONES FALLARON${NC}"
    echo "Revisar cambios antes de continuar"
    exit 1
fi
