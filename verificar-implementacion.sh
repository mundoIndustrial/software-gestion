#!/bin/bash
# Script de Verificación: Guardado de Logo en Pedido Borrador

echo "🧪 INICIANDO VERIFICACIÓN DE IMPLEMENTACIÓN"
echo "============================================"
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Contador de checks
PASSED=0
FAILED=0

check() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ PASÓ${NC}: $1"
        ((PASSED++))
    else
        echo -e "${RED}❌ FALLÓ${NC}: $1"
        ((FAILED++))
    fi
}

# ============================================
# 1. VERIFICAR ARCHIVOS MODIFICADOS
# ============================================
echo -e "${YELLOW}1️⃣ VERIFICANDO ARCHIVOS MODIFICADOS${NC}"
echo ""

# Verificar pedidos-modal.js
echo "   Buscando función recopilarDatosLogo()..."
grep -q "function recopilarDatosLogo" public/js/asesores/pedidos-modal.js
check "recopilarDatosLogo() existe en pedidos-modal.js"

echo "   Buscando integración en guardarPedidoModal()..."
grep -q "datosLogo = recopilarDatosLogo()" public/js/asesores/pedidos-modal.js
check "guardarPedidoModal() llama a recopilarDatosLogo()"

echo "   Buscando append de logo en FormData..."
grep -q "formData.append('logo\[descripcion\]'" public/js/asesores/pedidos-modal.js
check "FormData incluye logo[descripcion]"

echo "   Buscando append de imágenes del logo..."
grep -q "formData.append(\`logo\[imagenes\]\[\]\`" public/js/asesores/pedidos-modal.js
check "FormData incluye logo[imagenes][]"

echo ""

# Verificar AsesoresController.php
echo "   Buscando import de PedidoLogoService..."
grep -q "use App\\\Application\\\Services\\\PedidoLogoService" app/Http/Controllers/AsesoresController.php
check "PedidoLogoService importado"

echo "   Buscando validaciones de logo..."
grep -q "'logo.descripcion'" app/Http/Controllers/AsesoresController.php
check "Validación de logo.descripcion existe"

echo "   Buscando lógica de guardado de logo..."
grep -q "guardarLogoEnPedido" app/Http/Controllers/AsesoresController.php
check "Llamada a guardarLogoEnPedido() existe"

echo "   Buscando almacenamiento de imágenes..."
grep -q "store('logos/pedidos'" app/Http/Controllers/AsesoresController.php
check "Imágenes se guardan en logos/pedidos"

echo ""

# ============================================
# 2. VERIFICAR CLASE EXISTENTE
# ============================================
echo -e "${YELLOW}2️⃣ VERIFICANDO CLASE PEDIDOLOGOSERVICE${NC}"
echo ""

test -f app/Application/Services/PedidoLogoService.php
check "PedidoLogoService.php existe"

grep -q "function guardarLogoEnPedido" app/Application/Services/PedidoLogoService.php
check "Método guardarLogoEnPedido() existe"

echo ""

# ============================================
# 3. VERIFICAR TABLAS EN BD
# ============================================
echo -e "${YELLOW}3️⃣ VERIFICANDO ESTRUCTURA DE BASE DE DATOS${NC}"
echo ""

# Si tienes acceso a MySQL, descomenta estas líneas:
# MYSQL_CMD="mysql -u root -p29522628 mundo_bd -e"

# $MYSQL_CMD "SHOW TABLES LIKE 'logo_ped';"
# check "Tabla logo_ped existe"

# $MYSQL_CMD "SHOW TABLES LIKE 'logo_fotos_ped';"
# check "Tabla logo_fotos_ped existe"

echo "   ⚠️ Nota: Para verificar tablas en BD, ejecutar:"
echo "   mysql -u root -p29522628 mundo_bd -e \"SHOW TABLES LIKE 'logo%';\""
echo ""

# ============================================
# 4. VERIFICAR SINTAXIS
# ============================================
echo -e "${YELLOW}4️⃣ VERIFICANDO SINTAXIS${NC}"
echo ""

# Verificar sintaxis PHP
echo "   Verificando sintaxis de AsesoresController.php..."
php -l app/Http/Controllers/AsesoresController.php > /dev/null 2>&1
check "Sintaxis PHP válida en AsesoresController.php"

echo "   Verificando sintaxis de PedidoLogoService.php..."
php -l app/Application/Services/PedidoLogoService.php > /dev/null 2>&1
check "Sintaxis PHP válida en PedidoLogoService.php"

echo ""

# ============================================
# 5. VERIFICAR MIGRACIONES
# ============================================
echo -e "${YELLOW}5️⃣ VERIFICANDO MIGRACIONES${NC}"
echo ""

test -f database/migrations/2025_12_14_create_logo_pedidos_tables.php
check "Migración de logo_pedidos existe"

grep -q "CREATE TABLE logo_ped" database/migrations/2025_12_14_create_logo_pedidos_tables.php
check "Migración define tabla logo_ped"

grep -q "CREATE TABLE logo_fotos_ped" database/migrations/2025_12_14_create_logo_pedidos_tables.php
check "Migración define tabla logo_fotos_ped"

echo ""

# ============================================
# 6. VERIFICAR DOCUMENTACIÓN
# ============================================
echo -e "${YELLOW}6️⃣ VERIFICANDO DOCUMENTACIÓN${NC}"
echo ""

test -f IMPLEMENTACION_LOGO_PEDIDO_BORRADOR.md
check "Documentación IMPLEMENTACION_LOGO_PEDIDO_BORRADOR.md existe"

test -f UBICACION_CAMBIOS_LOGO.md
check "Documentación UBICACION_CAMBIOS_LOGO.md existe"

test -f GUARDADO_LOGO_PEDIDO_BORRADOR.md
check "Documentación GUARDADO_LOGO_PEDIDO_BORRADOR.md existe"

echo ""

# ============================================
# 7. RESUMEN
# ============================================
echo -e "${YELLOW}📊 RESUMEN${NC}"
echo "============================================"
echo -e "✅ PASÓ: ${GREEN}$PASSED${NC}"
echo -e "❌ FALLÓ: ${RED}$FAILED${NC}"
echo "TOTAL: $((PASSED + FAILED))"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 TODAS LAS VERIFICACIONES PASARON${NC}"
    echo ""
    echo "Próximos pasos:"
    echo "1. Ejecutar tests manuales en el navegador"
    echo "2. Probar guardado de logo en borrador"
    echo "3. Verificar en BD que se guardó correctamente"
    exit 0
else
    echo -e "${RED}⚠️ ALGUNAS VERIFICACIONES FALLARON${NC}"
    echo "Revisa los errores arriba"
    exit 1
fi
