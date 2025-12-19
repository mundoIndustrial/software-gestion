#!/bin/bash

#############################################
# SCRIPT DE INSTALACIÓN Y CONFIGURACIÓN
# DE LARAVEL REVERB CON SUPERVISOR
#############################################

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PROJECT_PATH="/var/www/mundoindustrial"
APP_USER="www-data"

echo -e "\n${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  INSTALACIÓN DE LARAVEL REVERB - WEBSOCKET EN TIEMPO REAL${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"

# Verificar que se ejecuta como root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}✗ Este script debe ejecutarse como root${NC}"
   echo "   Usa: sudo ./instalar-reverb.sh"
   exit 1
fi

echo -e "${YELLOW}[1/7] Verificando si está instalado...${NC}"
cd "$PROJECT_PATH" || exit 1

if php artisan list | grep -q reverb; then
    echo -e "${GREEN}✓ Reverb ya está instalado${NC}"
else
    echo -e "${YELLOW}⚠️  Installando Laravel Reverb...${NC}"
    composer require laravel/reverb
    if [ $? -ne 0 ]; then
        echo -e "${RED}✗ Error instalando Reverb${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ Reverb instalado${NC}"
fi

echo -e "\n${YELLOW}[2/7] Publicando assets de Reverb...${NC}"
sudo -u $APP_USER php artisan vendor:publish --provider="Laravel\\Reverb\\ReverbServiceProvider" || true
echo -e "${GREEN}✓ Assets publicados${NC}"

echo -e "\n${YELLOW}[3/7] Verificando certificados SSL...${NC}"
SSL_CERT="/etc/letsencrypt/live/sistemamundoindustrial.online/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/sistemamundoindustrial.online/privkey.pem"

if [ -f "$SSL_CERT" ] && [ -f "$SSL_KEY" ]; then
    echo -e "${GREEN}✓ Certificados SSL encontrados${NC}"
else
    echo -e "${YELLOW}⚠️  Certificados SSL no encontrados${NC}"
    echo "   Reverb funcionará con HTTP"
    echo "   Para HTTPS, genera certificados con Let's Encrypt:"
    echo "   sudo certbot certonly --standalone -d sistemamundoindustrial.online"
fi

echo -e "\n${YELLOW}[4/7] Configurando Supervisor...${NC}"

# Copiar archivo de configuración de Supervisor
if [ ! -f /etc/supervisor/conf.d/reverb.conf ]; then
    cp "$PROJECT_PATH/reverb.conf" /etc/supervisor/conf.d/
    echo -e "${GREEN}✓ Archivo reverb.conf copiado${NC}"
else
    echo -e "${YELLOW}ℹ️  reverb.conf ya existe, actualizando...${NC}"
    cp "$PROJECT_PATH/reverb.conf" /etc/supervisor/conf.d/reverb.conf
fi

echo -e "\n${YELLOW}[5/7] Recargando Supervisor...${NC}"
supervisorctl reread
supervisorctl update
echo -e "${GREEN}✓ Supervisor recargado${NC}"

echo -e "\n${YELLOW}[6/7] Iniciando Reverb...${NC}"
supervisorctl start reverb
sleep 2
STATUS=$(supervisorctl status reverb)
echo "   $STATUS"

if echo "$STATUS" | grep -q "RUNNING"; then
    echo -e "${GREEN}✓ Reverb está corriendo${NC}"
else
    echo -e "${YELLOW}⚠️  Reverb podría no estar corriendo${NC}"
    echo "   Ver logs: tail -f /var/log/mundo-industrial/reverb.log"
fi

echo -e "\n${YELLOW}[7/7] Limpiando cache de Laravel...${NC}"
sudo -u $APP_USER php artisan config:cache
sudo -u $APP_USER php artisan cache:clear
echo -e "${GREEN}✓ Cache limpiado${NC}"

echo -e "\n${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✓ INSTALACIÓN COMPLETADA${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"

echo -e "${BLUE}📊 Estado actual:${NC}\n"
supervisorctl status reverb
echo ""

echo -e "${BLUE}🌐 Configuración:${NC}"
echo "   Host WebSocket: sistemamundoindustrial.online:8080"
echo "   Protocolo: WSS (WebSocket Secure)"
echo "   Broadcast: Reverb"
echo ""

echo -e "${BLUE}📋 Comandos útiles:${NC}\n"
echo "Ver estado:"
echo "  supervisorctl status reverb"
echo ""
echo "Ver logs:"
echo "  tail -f /var/log/mundo-industrial/reverb.log"
echo ""
echo "Reiniciar:"
echo "  supervisorctl restart reverb"
echo ""
echo "Detener:"
echo "  supervisorctl stop reverb"
echo ""
echo "Diagnóstico:"
echo "  sudo $PROJECT_PATH/diagnostico-reverb.sh"
echo ""

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"

