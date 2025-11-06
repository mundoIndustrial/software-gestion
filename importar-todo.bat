@echo off
chcp 65001 >nul
echo ╔════════════════════════════════════════════════════════════╗
echo ║     IMPORTACIÓN MASIVA DE DATOS DESDE EXCEL               ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo Este script importará los siguientes archivos:
echo   1. CONTROL DE PISO POLOS (Respuestas).xlsx
echo   2. CONTROL DE PISO PRODUCCION (respuestas) (1).xlsx
echo   3. clasico (1).xlsx
echo.
echo ⚠️  ADVERTENCIA: Este proceso puede tardar varios minutos
echo.

set /p confirmar="¿Deseas continuar? (S/N): "
if /i not "%confirmar%"=="S" (
    echo.
    echo Operación cancelada.
    pause
    exit /b
)

echo.
echo ═══════════════════════════════════════════════════════════
echo Ejecutando importación...
echo ═══════════════════════════════════════════════════════════
echo.

php artisan importar:todo-excel

echo.
echo ═══════════════════════════════════════════════════════════
echo Proceso finalizado
echo ═══════════════════════════════════════════════════════════
echo.
pause
