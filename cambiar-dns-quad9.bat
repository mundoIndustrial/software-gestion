@echo off
REM Script para cambiar DNS a Quad9 (mejor cobertura global)
REM Ejecutar como Administrador

echo ===============================================
echo Cambiando DNS a Quad9 (9.9.9.9)
echo ===============================================

REM Mostrar DNS actual
echo.
echo DNS ACTUAL:
netsh interface ipv4 show dns name="Ethernet"

REM Cambiar a Quad9
echo.
echo Cambiando a Quad9 (9.9.9.9 y 149.112.112.112)...
netsh interface ipv4 set dns name="Ethernet" static 9.9.9.9 validate=no
netsh interface ipv4 add dns name="Ethernet" 149.112.112.112 validate=no index=2

REM Verificar cambios
echo.
echo DNS NUEVO:
netsh interface ipv4 show dns name="Ethernet"

REM Limpiar cache de DNS de Windows
echo.
echo Limpiando cache de DNS de Windows...
ipconfig /flushdns

REM Probar resolución
echo.
echo Probando resolución de api.nager.date...
nslookup api.nager.date 9.9.9.9

echo.
echo ===============================================
echo Completado! Los cambios deberían tomar efecto inmediatamente
echo ===============================================
pause
