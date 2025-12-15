#!/bin/bash

# 🧪 SCRIPT: Ejecutar todos los tests de cotizaciones
# Fecha: 14 de Diciembre de 2025
# Propósito: Suite completa de validación de cotizaciones

echo "╔════════════════════════════════════════════════════════════╗"
echo "║  🧪 SUITE COMPLETA DE TESTS - COTIZACIONES                ║"
echo "║  Total Tests: 26 | Total Cotizaciones: 260+              ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir títulos
print_section() {
    echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
}

# Función para imprimir resultado
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}\n"
    else
        echo -e "${RED}❌ $2${NC}\n"
    fi
}

# =====================================================
# OPCIÓN 1: Ejecutar TODOS los tests
# =====================================================
run_all_tests() {
    print_section "OPCIÓN 1: Ejecutar TODOS los tests"
    
    echo "Ejecutando..."
    php artisan test tests/Feature/Cotizacion/ --verbose
    local result=$?
    
    print_result $result "Suite completa ejecutada"
    return $result
}

# =====================================================
# OPCIÓN 2: Suite Completa (44 + 33 cotizaciones)
# =====================================================
run_complete_suite() {
    print_section "OPCIÓN 2: Suite Completa"
    
    echo "Creando 11 cotizaciones de cada tipo..."
    echo "- 11 Muestra (M)"
    echo "- 11 Prototipo (P)"
    echo "- 11 Grande (G)"
    echo "- 11 Bordado"
    echo "- 33 Concurrencia (3 asesores × 11)"
    echo "Total: 77 cotizaciones"
    echo ""
    
    php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php --verbose
    local result=$?
    
    print_result $result "Suite Completa ejecutada"
    return $result
}

# =====================================================
# OPCIÓN 3: Suite de Integridad
# =====================================================
run_integrity_suite() {
    print_section "OPCIÓN 3: Suite de Integridad"
    
    echo "Validando:"
    echo "- UNIQUE constraints"
    echo "- Foreign Keys"
    echo "- JSON fields"
    echo "- Enums y Validaciones"
    echo "- Relaciones One-to-Many"
    echo ""
    
    php artisan test tests/Feature/Cotizacion/CotizacionesIntegrityTest.php --verbose
    local result=$?
    
    print_result $result "Suite de Integridad ejecutada"
    return $result
}

# =====================================================
# OPCIÓN 4: Suite de Concurrencia
# =====================================================
run_concurrency_suite() {
    print_section "OPCIÓN 4: Suite de Concurrencia"
    
    echo "Validando:"
    echo "- 100 cotizaciones secuenciales"
    echo "- 3 asesores intercalados"
    echo "- Transacciones y rollback"
    echo "- Máxima complejidad (10 prendas × 10 fotos)"
    echo "- Performance"
    echo "Total: 183+ cotizaciones"
    echo ""
    
    php artisan test tests/Feature/Cotizacion/CotizacionesConcurrencyTest.php --verbose
    local result=$?
    
    print_result $result "Suite de Concurrencia ejecutada"
    return $result
}

# =====================================================
# OPCIÓN 5: Test específico - Tipo Muestra
# =====================================================
run_test_muestra() {
    print_section "OPCIÓN 5: Test Específico - Tipo Muestra"
    
    echo "Creando 11 cotizaciones tipo MUESTRA..."
    echo "Campos: Cliente, 1 Prenda, 3 Fotos, 2 Telas, 3 Tallas"
    echo ""
    
    php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php \
        --filter=test_crear_11_cotizaciones_tipo_muestra \
        --verbose
    local result=$?
    
    print_result $result "Test Muestra ejecutado"
    return $result
}

# =====================================================
# OPCIÓN 6: Test específico - Tipo Prototipo
# =====================================================
run_test_prototipo() {
    print_section "OPCIÓN 6: Test Específico - Tipo Prototipo"
    
    echo "Creando 11 cotizaciones tipo PROTOTIPO..."
    echo "Campos: Cliente, 2 Prendas, 4 Fotos c/u, 3 Telas, 4 Tallas"
    echo ""
    
    php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php \
        --filter=test_crear_11_cotizaciones_tipo_prototipo \
        --verbose
    local result=$?
    
    print_result $result "Test Prototipo ejecutado"
    return $result
}

# =====================================================
# OPCIÓN 7: Test específico - Tipo Grande
# =====================================================
run_test_grande() {
    print_section "OPCIÓN 7: Test Específico - Tipo Grande"
    
    echo "Creando 11 cotizaciones tipo GRANDE..."
    echo "Campos: Cliente, 3 Prendas, 5 Fotos c/u, 4 Telas, 6 Tallas"
    echo ""
    
    php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php \
        --filter=test_crear_11_cotizaciones_tipo_grande \
        --verbose
    local result=$?
    
    print_result $result "Test Grande ejecutado"
    return $result
}

# =====================================================
# OPCIÓN 8: Test específico - Secuencialidad
# =====================================================
run_test_secuencial() {
    print_section "OPCIÓN 8: Test Específico - Secuencialidad"
    
    echo "Validando numero_cotizacion secuencial..."
    echo "Creando 11 de cada tipo (44 total)"
    echo "Verificando que todos sean únicos"
    echo ""
    
    php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php \
        --filter=test_numero_cotizacion_secuencial_global \
        --verbose
    local result=$?
    
    print_result $result "Test Secuencial ejecutado"
    return $result
}

# =====================================================
# OPCIÓN 9: Test específico - Concurrencia
# =====================================================
run_test_concurrency() {
    print_section "OPCIÓN 9: Test Específico - Concurrencia"
    
    echo "Validando concurrencia..."
    echo "3 asesores × 11 cotizaciones = 33 total"
    echo "Verificando integridad sin race conditions"
    echo ""
    
    php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php \
        --filter=test_concurrencia_multiples_asesores \
        --verbose
    local result=$?
    
    print_result $result "Test Concurrencia ejecutado"
    return $result
}

# =====================================================
# OPCIÓN 10: Test específico - 100 Secuencial
# =====================================================
run_test_100_sequential() {
    print_section "OPCIÓN 10: Test Específico - 100 Secuencial"
    
    echo "Creando 100 cotizaciones de forma secuencial..."
    echo "Validando que NO hay duplicados"
    echo "Este test toma ~2-3 minutos"
    echo ""
    
    php artisan test tests/Feature/Cotizacion/CotizacionesConcurrencyTest.php \
        --filter=test_100_cotizaciones_secuenciales_sin_duplicados \
        --verbose
    local result=$?
    
    print_result $result "Test 100 Secuencial ejecutado"
    return $result
}

# =====================================================
# MENÚ PRINCIPAL
# =====================================================
show_menu() {
    echo ""
    echo -e "${YELLOW}Selecciona una opción:${NC}"
    echo ""
    echo "  1) 🏃 Ejecutar TODOS los tests (26 tests, 260+ cotizaciones)"
    echo "  2) 📋 Suite Completa - 4 tipos (77 cotizaciones)"
    echo "  3) ✅ Suite de Integridad - Validaciones (12 tests)"
    echo "  4) ⚡ Suite de Concurrencia - 183+ cotizaciones (8 tests)"
    echo ""
    echo "  5) 📝 Test Individual - Tipo MUESTRA"
    echo "  6) 📝 Test Individual - Tipo PROTOTIPO"
    echo "  7) 📝 Test Individual - Tipo GRANDE"
    echo "  8) 📝 Test Individual - Validación SECUENCIAL"
    echo "  9) 📝 Test Individual - Validación CONCURRENCIA"
    echo "  10) 📝 Test Individual - 100 Secuencial"
    echo ""
    echo "  0) 🚪 Salir"
    echo ""
}

# =====================================================
# MAIN
# =====================================================
main() {
    # Si hay argumento, usarlo directamente
    if [ ! -z "$1" ]; then
        case $1 in
            1) run_all_tests ;;
            2) run_complete_suite ;;
            3) run_integrity_suite ;;
            4) run_concurrency_suite ;;
            5) run_test_muestra ;;
            6) run_test_prototipo ;;
            7) run_test_grande ;;
            8) run_test_secuencial ;;
            9) run_test_concurrency ;;
            10) run_test_100_sequential ;;
            *) echo "Opción no válida: $1" && exit 1 ;;
        esac
        exit 0
    fi

    # Si no hay argumento, mostrar menú interactivo
    while true; do
        show_menu
        read -p "Opción: " choice

        case $choice in
            1) run_all_tests ;;
            2) run_complete_suite ;;
            3) run_integrity_suite ;;
            4) run_concurrency_suite ;;
            5) run_test_muestra ;;
            6) run_test_prototipo ;;
            7) run_test_grande ;;
            8) run_test_secuencial ;;
            9) run_test_concurrency ;;
            10) run_test_100_sequential ;;
            0)
                echo -e "${GREEN}¡Hasta luego!${NC}"
                exit 0
                ;;
            *)
                echo -e "${RED}Opción no válida${NC}"
                ;;
        esac

        read -p "Presiona Enter para continuar..."
    done
}

# Ejecutar
main "$@"

