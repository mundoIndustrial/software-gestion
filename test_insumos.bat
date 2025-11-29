@echo off
REM Script para ejecutar test del módulo de insumos
REM Verifica que la migración a pedidos_produccion funcione correctamente

echo.
echo ============================================================
echo  Ejecutando Tests del Módulo de Insumos
echo ============================================================
echo.

cd /d "%~dp0"

REM Ejecutar el script PHP
php test_insumos.php

echo.
pause
