@echo off
chcp 65001 >nul
echo ╔════════════════════════════════════════════════════════════╗
echo ║     IMPORTACIÓN MASIVA - OPCIONES AVANZADAS               ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo Selecciona una opción:
echo.
echo   1. Importar TODO (sin limpiar datos existentes)
echo   2. Importar TODO y LIMPIAR datos existentes
echo   3. Modo DRY-RUN (simular sin guardar)
echo   4. Importar solo POLOS
echo   5. Importar solo PRODUCCION
echo   6. Importar solo BALANCEOS
echo   7. Salir
echo.

set /p opcion="Ingresa el número de opción: "

if "%opcion%"=="1" goto importar_todo
if "%opcion%"=="2" goto importar_limpiar
if "%opcion%"=="3" goto dry_run
if "%opcion%"=="4" goto solo_polos
if "%opcion%"=="5" goto solo_produccion
if "%opcion%"=="6" goto solo_balanceos
if "%opcion%"=="7" goto salir

echo.
echo ❌ Opción inválida
pause
exit /b

:importar_todo
echo.
echo ═══════════════════════════════════════════════════════════
echo Importando TODO (sin limpiar)...
echo ═══════════════════════════════════════════════════════════
echo.
php artisan importar:todo-excel
goto fin

:importar_limpiar
echo.
echo ⚠️  ADVERTENCIA: Se eliminarán TODOS los datos existentes
set /p confirmar="¿Estás seguro? (S/N): "
if /i not "%confirmar%"=="S" (
    echo Operación cancelada.
    pause
    exit /b
)
echo.
echo ═══════════════════════════════════════════════════════════
echo Importando TODO (con limpieza)...
echo ═══════════════════════════════════════════════════════════
echo.
php artisan importar:todo-excel --limpiar
goto fin

:dry_run
echo.
echo ═══════════════════════════════════════════════════════════
echo Modo DRY-RUN (simulación)...
echo ═══════════════════════════════════════════════════════════
echo.
php artisan importar:todo-excel --dry-run
goto fin

:solo_polos
echo.
echo ═══════════════════════════════════════════════════════════
echo Importando solo POLOS...
echo ═══════════════════════════════════════════════════════════
echo.
php artisan importar:todo-excel --polo="resources/CONTROL DE PISO POLOS (Respuestas) .xlsx"
goto fin

:solo_produccion
echo.
echo ═══════════════════════════════════════════════════════════
echo Importando solo PRODUCCION...
echo ═══════════════════════════════════════════════════════════
echo.
php artisan importar:todo-excel --produccion="resources/CONTROL DE PISO PRODUCCION (respuestas) (1).xlsx"
goto fin

:solo_balanceos
echo.
echo ═══════════════════════════════════════════════════════════
echo Importando solo BALANCEOS...
echo ═══════════════════════════════════════════════════════════
echo.
php artisan importar:todo-excel --balanceo="resources/clasico (1).xlsx"
goto fin

:fin
echo.
echo ═══════════════════════════════════════════════════════════
echo Proceso finalizado
echo ═══════════════════════════════════════════════════════════
echo.
pause
exit /b

:salir
echo.
echo Saliendo...
exit /b
