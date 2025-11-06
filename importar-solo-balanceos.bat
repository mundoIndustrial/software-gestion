@echo off
chcp 65001 >nul
echo ╔════════════════════════════════════════════════════════════╗
echo ║           IMPORTACIÓN DE BALANCEOS DESDE EXCEL             ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo Este script importará solo los balanceos desde:
echo   clasico (1).xlsx
echo.

set /p limpiar="¿Deseas limpiar los balanceos existentes? (S/N): "

echo.
echo ═══════════════════════════════════════════════════════════
echo Ejecutando importación...
echo ═══════════════════════════════════════════════════════════
echo.

if /i "%limpiar%"=="S" (
    php artisan importar:solo-balanceos --limpiar
) else (
    php artisan importar:solo-balanceos
)

echo.
echo ═══════════════════════════════════════════════════════════
echo Proceso finalizado
echo ═══════════════════════════════════════════════════════════
echo.
pause
