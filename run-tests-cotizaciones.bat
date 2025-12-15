@echo off
REM 🧪 SCRIPT: Ejecutar todos los tests de cotizaciones (Windows)
REM Fecha: 14 de Diciembre de 2025
REM Propósito: Suite completa de validación de cotizaciones

setlocal enabledelayedexpansion

echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║  🧪 SUITE COMPLETA DE TESTS - COTIZACIONES                ║
echo ║  Total Tests: 26 ^| Total Cotizaciones: 260+              ║
echo ╚════════════════════════════════════════════════════════════╝
echo.

REM Colores (no funcionan bien en CMD antiguo, solo para WSL)
REM Para mejor compatibilidad, usaremos solo texto plano

REM =====================================================
REM Verificar que estamos en la carpeta correcta
REM =====================================================
if not exist artisan (
    echo ❌ ERROR: No se encontró 'artisan'
    echo Por favor, ejecuta este script desde la raíz del proyecto
    pause
    exit /b 1
)

REM =====================================================
REM MENÚ PRINCIPAL
REM =====================================================
:menu
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║           SUITE DE TESTS - COTIZACIONES                  ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo Selecciona una opción:
echo.
echo  1) 🏃  Ejecutar TODOS los tests (26 tests, 260+ cotizaciones)
echo  2) 📋 Suite Completa - 4 tipos (77 cotizaciones)
echo  3) ✅ Suite de Integridad - Validaciones (12 tests)
echo  4) ⚡ Suite de Concurrencia - 183+ cotizaciones (8 tests)
echo.
echo  5) 📝 Test Individual - Tipo MUESTRA
echo  6) 📝 Test Individual - Tipo PROTOTIPO
echo  7) 📝 Test Individual - Tipo GRANDE
echo  8) 📝 Test Individual - Validación SECUENCIAL
echo  9) 📝 Test Individual - Validación CONCURRENCIA
echo  10) 📝 Test Individual - 100 Secuencial
echo.
echo  11) 🗑️  Limpiar base de datos (RefreshDatabase)
echo  12) 📊 Mostrar estadísticas
echo.
echo  0) 🚪 Salir
echo.
set /p choice="Opción: "

REM =====================================================
REM PROCESAR OPCIÓN
REM =====================================================
if "%choice%"=="1" goto run_all_tests
if "%choice%"=="2" goto run_complete_suite
if "%choice%"=="3" goto run_integrity_suite
if "%choice%"=="4" goto run_concurrency_suite
if "%choice%"=="5" goto run_test_muestra
if "%choice%"=="6" goto run_test_prototipo
if "%choice%"=="7" goto run_test_grande
if "%choice%"=="8" goto run_test_secuencial
if "%choice%"=="9" goto run_test_concurrency
if "%choice%"=="10" goto run_test_100_sequential
if "%choice%"=="11" goto clean_database
if "%choice%"=="12" goto show_stats
if "%choice%"=="0" goto exit_script
echo ❌ Opción no válida
pause
goto menu

REM =====================================================
REM OPCIÓN 1: Ejecutar TODOS los tests
REM =====================================================
:run_all_tests
cls
echo.
echo ════════════════════════════════════════════════════════════
echo OPCIÓN 1: Ejecutar TODOS los tests
echo ════════════════════════════════════════════════════════════
echo.
echo Ejecutando suite completa (26 tests, 260+ cotizaciones)...
echo.
php artisan test tests/Feature/Cotizacion/ --verbose
set result_code=%errorlevel%
echo.
if %result_code%==0 (
    echo ✅ Suite completa ejecutada exitosamente
) else (
    echo ❌ Suite falló con código de error: %result_code%
)
pause
goto menu

REM =====================================================
REM OPCIÓN 2: Suite Completa
REM =====================================================
:run_complete_suite
cls
echo.
echo ════════════════════════════════════════════════════════════
echo OPCIÓN 2: Suite Completa
echo ════════════════════════════════════════════════════════════
echo.
echo Creando 77 cotizaciones:
echo   - 11 Muestra (M)
echo   - 11 Prototipo (P)
echo   - 11 Grande (G)
echo   - 11 Bordado
echo   - 33 Concurrencia (3 asesores × 11)
echo.
php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php --verbose
set result_code=%errorlevel%
echo.
if %result_code%==0 (
    echo ✅ Suite Completa ejecutada exitosamente
) else (
    echo ❌ Suite falló con código de error: %result_code%
)
pause
goto menu

REM =====================================================
REM OPCIÓN 3: Suite de Integridad
REM =====================================================
:run_integrity_suite
cls
echo.
echo ════════════════════════════════════════════════════════════
echo OPCIÓN 3: Suite de Integridad
echo ════════════════════════════════════════════════════════════
echo.
echo Validando:
echo   - UNIQUE constraints
echo   - Foreign Keys
echo   - JSON fields
echo   - Enums y Validaciones
echo   - Relaciones One-to-Many
echo.
php artisan test tests/Feature/Cotizacion/CotizacionesIntegrityTest.php --verbose
set result_code=%errorlevel%
echo.
if %result_code%==0 (
    echo ✅ Suite de Integridad ejecutada exitosamente
) else (
    echo ❌ Suite falló con código de error: %result_code%
)
pause
goto menu

REM =====================================================
REM OPCIÓN 4: Suite de Concurrencia
REM =====================================================
:run_concurrency_suite
cls
echo.
echo ════════════════════════════════════════════════════════════
echo OPCIÓN 4: Suite de Concurrencia
echo ════════════════════════════════════════════════════════════
echo.
echo Validando:
echo   - 100 cotizaciones secuenciales
echo   - 3 asesores intercalados
echo   - Transacciones y rollback
echo   - Máxima complejidad (10 prendas × 10 fotos)
echo   - Performance
echo   Total: 183+ cotizaciones
echo.
echo ⏱️  Este test puede tomar 5-10 minutos...
echo.
php artisan test tests/Feature/Cotizacion/CotizacionesConcurrencyTest.php --verbose
set result_code=%errorlevel%
echo.
if %result_code%==0 (
    echo ✅ Suite de Concurrencia ejecutada exitosamente
) else (
    echo ❌ Suite falló con código de error: %result_code%
)
pause
goto menu

REM =====================================================
REM OPCIÓN 5: Test Muestra
REM =====================================================
:run_test_muestra
cls
echo.
echo ════════════════════════════════════════════════════════════
echo OPCIÓN 5: Test Específico - Tipo MUESTRA
echo ════════════════════════════════════════════════════════════
echo.
echo Creando 11 cotizaciones tipo MUESTRA...
echo Campos: Cliente, 1 Prenda, 3 Fotos, 2 Telas, 3 Tallas
echo.
php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php --filter=test_crear_11_cotizaciones_tipo_muestra --verbose
set result_code=%errorlevel%
echo.
if %result_code%==0 (
    echo ✅ Test Muestra ejecutado exitosamente
) else (
    echo ❌ Test falló con código de error: %result_code%
)
pause
goto menu

REM =====================================================
REM OPCIÓN 6: Test Prototipo
REM =====================================================
:run_test_prototipo
cls
echo.
echo ════════════════════════════════════════════════════════════
echo OPCIÓN 6: Test Específico - Tipo PROTOTIPO
echo ════════════════════════════════════════════════════════════
echo.
echo Creando 11 cotizaciones tipo PROTOTIPO...
echo Campos: Cliente, 2 Prendas, 4 Fotos c/u, 3 Telas, 4 Tallas
echo.
php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php --filter=test_crear_11_cotizaciones_tipo_prototipo --verbose
set result_code=%errorlevel%
echo.
if %result_code%==0 (
    echo ✅ Test Prototipo ejecutado exitosamente
) else (
    echo ❌ Test falló con código de error: %result_code%
)
pause
goto menu

REM =====================================================
REM OPCIÓN 7: Test Grande
REM =====================================================
:run_test_grande
cls
echo.
echo ════════════════════════════════════════════════════════════
echo OPCIÓN 7: Test Específico - Tipo GRANDE
echo ════════════════════════════════════════════════════════════
echo.
echo Creando 11 cotizaciones tipo GRANDE...
echo Campos: Cliente, 3 Prendas, 5 Fotos c/u, 4 Telas, 6 Tallas
echo.
php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php --filter=test_crear_11_cotizaciones_tipo_grande --verbose
set result_code=%errorlevel%
echo.
if %result_code%==0 (
    echo ✅ Test Grande ejecutado exitosamente
) else (
    echo ❌ Test falló con código de error: %result_code%
)
pause
goto menu

REM =====================================================
REM OPCIÓN 8: Test Secuencial
REM =====================================================
:run_test_secuencial
cls
echo.
echo ════════════════════════════════════════════════════════════
echo OPCIÓN 8: Test Específico - Validación SECUENCIAL
echo ════════════════════════════════════════════════════════════
echo.
echo Validando numero_cotizacion secuencial...
echo Creando 11 de cada tipo (44 total)
echo Verificando que todos sean únicos
echo.
php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php --filter=test_numero_cotizacion_secuencial_global --verbose
set result_code=%errorlevel%
echo.
if %result_code%==0 (
    echo ✅ Test Secuencial ejecutado exitosamente
) else (
    echo ❌ Test falló con código de error: %result_code%
)
pause
goto menu

REM =====================================================
REM OPCIÓN 9: Test Concurrencia
REM =====================================================
:run_test_concurrency
cls
echo.
echo ════════════════════════════════════════════════════════════
echo OPCIÓN 9: Test Específico - Validación CONCURRENCIA
echo ════════════════════════════════════════════════════════════
echo.
echo Validando concurrencia...
echo 3 asesores × 11 cotizaciones = 33 total
echo Verificando integridad sin race conditions
echo.
php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php --filter=test_concurrencia_multiples_asesores --verbose
set result_code=%errorlevel%
echo.
if %result_code%==0 (
    echo ✅ Test Concurrencia ejecutado exitosamente
) else (
    echo ❌ Test falló con código de error: %result_code%
)
pause
goto menu

REM =====================================================
REM OPCIÓN 10: Test 100 Secuencial
REM =====================================================
:run_test_100_sequential
cls
echo.
echo ════════════════════════════════════════════════════════════
echo OPCIÓN 10: Test Específico - 100 Secuencial
echo ════════════════════════════════════════════════════════════
echo.
echo Creando 100 cotizaciones de forma secuencial...
echo Validando que NO hay duplicados
echo.
echo ⏱️  Este test toma ~2-3 minutos...
echo.
php artisan test tests/Feature/Cotizacion/CotizacionesConcurrencyTest.php --filter=test_100_cotizaciones_secuenciales_sin_duplicados --verbose
set result_code=%errorlevel%
echo.
if %result_code%==0 (
    echo ✅ Test 100 Secuencial ejecutado exitosamente
) else (
    echo ❌ Test falló con código de error: %result_code%
)
pause
goto menu

REM =====================================================
REM OPCIÓN 11: Limpiar base de datos
REM =====================================================
:clean_database
cls
echo.
echo ════════════════════════════════════════════════════════════
echo OPCIÓN 11: Limpiar Base de Datos
echo ════════════════════════════════════════════════════════════
echo.
echo ⚠️  ADVERTENCIA: Esto eliminará todos los datos de la base de datos
set /p confirm="¿Estás seguro? (S/N): "
if /i "%confirm%"=="S" (
    php artisan migrate:refresh --seed
    echo.
    echo ✅ Base de datos limpiada y reseteada
) else (
    echo ❌ Operación cancelada
)
pause
goto menu

REM =====================================================
REM OPCIÓN 12: Mostrar estadísticas
REM =====================================================
:show_stats
cls
echo.
echo ════════════════════════════════════════════════════════════
echo ESTADÍSTICAS DE LA SUITE DE TESTS
echo ════════════════════════════════════════════════════════════
echo.
echo ARCHIVO: CotizacionesCompleteTest.php
echo   - Total Tests: 6
echo   - Total Cotizaciones: 77 (44 + 33 concurrencia)
echo   - Prendas: ~200
echo   - Fotos: ~600
echo.
echo ARCHIVO: CotizacionesIntegrityTest.php
echo   - Total Tests: 12
echo   - Validaciones UNIQUE: 1
echo   - Validaciones FK: 2
echo   - Validaciones JSON: 2
echo   - Validaciones Enum: 2
echo   - Validaciones Relación: 1
echo.
echo ARCHIVO: CotizacionesConcurrencyTest.php
echo   - Total Tests: 8
echo   - Total Cotizaciones: 183+ (100 + 33 + 50)
echo   - Máximo Prendas: 10
echo   - Máximo Fotos: 100 (10 prendas × 10 fotos)
echo.
echo ════════════════════════════════════════════════════════════
echo TOTAL GENERAL
echo ════════════════════════════════════════════════════════════
echo   - Total Tests: 26
echo   - Total Cotizaciones: 260+
echo   - Total Prendas: 800+
echo   - Total Fotos: 2000+
echo.
echo Tiempo estimado: 5-10 minutos (dependiendo del servidor)
echo.
pause
goto menu

REM =====================================================
REM SALIR
REM =====================================================
:exit_script
cls
echo.
echo ✅ ¡Hasta luego!
echo.
exit /b 0

