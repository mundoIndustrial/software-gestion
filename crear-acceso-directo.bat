@echo off
title Crear Acceso Directo - Mundo Industrial
color 0B

echo.
echo ========================================
echo   CREAR ACCESO DIRECTO CON ICONO
echo ========================================
echo.
echo Este script creara un acceso directo de
echo INICIAR.bat en tu escritorio con el
echo icono personalizado de Mundo Industrial.
echo.
echo ========================================
echo.

powershell -ExecutionPolicy Bypass -File "%~dp0crear-acceso-directo.ps1"

pause
