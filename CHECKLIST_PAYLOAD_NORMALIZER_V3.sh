#!/usr/bin/env bash
# ============================================================================
# CHECKLIST - SOLUCIÓN PAYLOAD NORMALIZER v3
# ============================================================================

echo "╔════════════════════════════════════════════════════════════╗"
echo "║  CHECKLIST VERIFICACIÓN PAYLOAD NORMALIZER v3           ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ========================================================================
# 1. VERIFICAR QUE ARCHIVOS EXISTEN
# ========================================================================
echo -e "${YELLOW} PASO 1: Verificar archivos${NC}"

files_check=(
    "public/js/modulos/crear-pedido/procesos/services/payload-normalizer-v3-definitiva.js"
    "public/js/modulos/crear-pedido/procesos/services/validar-payload-normalizer-v3.js"
    "resources/views/asesores/pedidos/crear-pedido.blade.php"
    "resources/views/asesores/pedidos/edit.blade.php"
    "resources/views/asesores/pedidos/crear-pedido-desde-cotizacion.blade.php"
    "resources/views/asesores/pedidos/crear-pedido-nuevo.blade.php"
    "resources/views/asesores/pedidos/index.blade.php"
)

for file in "${files_check[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✅${NC} $file"
    else
        echo -e "${RED}${NC} $file (NO ENCONTRADO)"
    fi
done

echo ""

# ========================================================================
# 2. VERIFICAR QUE ARCHIVOS FUERON ELIMINADOS
# ========================================================================
echo -e "${YELLOW} PASO 2: Verificar que archivos conflictivos fueron eliminados${NC}"

if [ ! -f "public/js/modulos/crear-pedido/procesos/services/payload-normalizer-simple.js" ]; then
    echo -e "${GREEN}✅${NC} payload-normalizer-simple.js - ELIMINADO"
else
    echo -e "${RED}${NC} payload-normalizer-simple.js - AÚN EXISTE (debe eliminarse)"
fi

echo ""

# ========================================================================
# 3. VERIFICAR CONTENIDO DE payload-normalizer.js
# ========================================================================
echo -e "${YELLOW} PASO 3: Verificar payload-normalizer.js (debe ser placeholder)${NC}"

if grep -q "DEPRECATED" "public/js/modulos/crear-pedido/procesos/services/payload-normalizer.js"; then
    echo -e "${GREEN}✅${NC} payload-normalizer.js es un placeholder"
else
    echo -e "${RED}${NC} payload-normalizer.js aún contiene código antiguo"
fi

echo ""

# ========================================================================
# 4. VERIFICAR QUE base.blade.php NO TIENE CÓDIGO SUELTO
# ========================================================================
echo -e "${YELLOW} PASO 4: Verificar base.blade.php${NC}"

if grep -q "console.debug\|normalizePedido" "resources/views/layouts/base.blade.php"; then
    echo -e "${RED}${NC} base.blade.php aún contiene código suelto"
else
    echo -e "${GREEN}✅${NC} base.blade.php limpio"
fi

echo ""

# ========================================================================
# 5. VERIFICAR QUE BLADE TEMPLATES USAN v3-definitiva
# ========================================================================
echo -e "${YELLOW} PASO 5: Verificar que Blade templates usan payload-normalizer-v3-definitiva.js${NC}"

blade_files=(
    "resources/views/asesores/pedidos/crear-pedido.blade.php"
    "resources/views/asesores/pedidos/edit.blade.php"
    "resources/views/asesores/pedidos/crear-pedido-desde-cotizacion.blade.php"
    "resources/views/asesores/pedidos/crear-pedido-nuevo.blade.php"
    "resources/views/asesores/pedidos/index.blade.php"
)

for file in "${blade_files[@]}"; do
    if grep -q "payload-normalizer-v3-definitiva.js" "$file"; then
        echo -e "${GREEN}✅${NC} $file"
    else
        echo -e "${RED}${NC} $file (no usa v3-definitiva)"
    fi
done

echo ""

# ========================================================================
# 6. VERIFICAR CACHE BUSTING
# ========================================================================
echo -e "${YELLOW} PASO 6: Verificar cache busting en scripts${NC}"

for file in "${blade_files[@]}"; do
    if grep -q 'time()' "$file" | head -1; then
        echo -e "${GREEN}✅${NC} $file tiene cache busting"
    else
        echo -e "${YELLOW}${NC} $file podría no tener cache busting en todos los scripts"
    fi
done

echo ""

# ========================================================================
# 7. RESUMEN
# ========================================================================
echo "╔════════════════════════════════════════════════════════════╗"
echo "║  CHECKLIST COMPLETADO                                  ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "📋 Acciones recomendadas:"
echo "   1. Limpia el caché del navegador (Ctrl+Shift+Delete)"
echo "   2. Recarga la página con hard refresh (Ctrl+Shift+R)"
echo "   3. Abre la consola (F12)"
echo "   4. Ejecuta el script de validación:"
echo "      - Abre: validar-payload-normalizer-v3.js"
echo "      - Copia y pega su contenido en la consola"
echo "   5. Intenta crear un pedido"
echo ""
