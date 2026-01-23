@echo off
chcp 65001 >nul
title Servidor Analizador de Artículos

echo.
echo ========================================
echo  ANALIZADOR DE ARTICULOS
echo  Servidor Python Flask
echo ========================================
echo.
echo 📍 URL: http://localhost:5000
echo 🌐 Abre el navegador y accede a:
echo    file:///C:/Users/Usuario/Documents/mundoindustrial/analizador-articulos.html
echo.
echo ⏳ Iniciando servidor...
echo.

cd /d "C:\Users\Usuario\Documents\mundoindustrial"
python servidor_analizador.py

pause
