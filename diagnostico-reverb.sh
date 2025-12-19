#!/bin/bash

#############################################
# DIAGNÓSTICO DE REVERB Y WEBSOCKET
# Verifica que todo esté funcionando
#############################################

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PROJECT_PATH="/var/www/mundoindustrial"

echo -e "\n${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  DIAGNÓSTICO REVERB / WEBSOCKET${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"

# 1. Verificar si Reverb está instalado
echo -e "${YELLOW}[1/8] Verificando instalación de Reverb...${NC}"
cd "$PROJECT_PATH" || exit 1
if php artisan list | grep -q reverb; then
    echo -e "${GREEN}✓ Reverb está instalado${NC}"
else
    echo -e "${RED}✗ Reverb NO está instalado${NC}"
    echo "     Instala con: composer require laravel/reverb"
    exit 1
fi

# 2. Verificar configuración del .env
echo -e "\n${YELLOW}[2/8] Verificando configuración .env...${NC}"
grep -E "BROADCAST_DRIVER|REVERB_" "$PROJECT_PATH/.env" | head -10
echo ""

# 3. Verificar si el puerto 8080 está disponible
echo -e "${YELLOW}[3/8] Verificando puerto 8080...${NC}"
if netstat -tln | grep -q :8080; then
    echo -e "${GREEN}✓ Puerto 8080 está en uso (probablemente Reverb)${NC}"
    netstat -tln | grep :8080
else
    echo -e "${YELLOW}⚠️  Puerto 8080 NO está en uso${NC}"
    echo "     Reverb podría no estar corriendo"
fi

# 4. Verificar si Reverb está en Supervisor
echo -e "\n${YELLOW}[4/8] Verificando Supervisor...${NC}"
if command -v supervisorctl &> /dev/null; then
    if supervisorctl status reverb 2>/dev/null | grep -q reverb; then
        echo -e "${GREEN}✓ Reverb está en Supervisor${NC}"
        supervisorctl status reverb
    else
        echo -e "${YELLOW}⚠️  Reverb NO está en Supervisor${NC}"
        echo "     Instalación: cp reverb.conf /etc/supervisor/conf.d/"
        echo "     Luego: supervisorctl reread && supervisorctl update"
    fi
else
    echo -e "${YELLOW}⚠️  Supervisor no está instalado${NC}"
fi

# 5. Verificar certificados SSL
echo -e "\n${YELLOW}[5/8] Verificando certificados SSL...${NC}"
SSL_CERT="/etc/letsencrypt/live/sistemamundoindustrial.online/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/sistemamundoindustrial.online/privkey.pem"

if [ -f "$SSL_CERT" ] && [ -f "$SSL_KEY" ]; then
    echo -e "${GREEN}✓ Certificados SSL encontrados${NC}"
    CERT_DATE=$(openssl x509 -enddate -noout -in "$SSL_CERT" | cut -d= -f2)
    echo "   Expira: $CERT_DATE"
else
    echo -e "${YELLOW}⚠️  Certificados SSL NO encontrados${NC}"
    echo "   Reverb funcionará con HTTP en lugar de HTTPS"
fi

# 6. Verificar logs
echo -e "\n${YELLOW}[6/8] Verificando logs...${NC}"
if [ -f /var/log/mundo-industrial/reverb.log ]; then
    echo -e "${GREEN}✓ Log de Reverb encontrado${NC}"
    echo -e "${BLUE}   Últimas 5 líneas:${NC}"
    tail -5 /var/log/mundo-industrial/reverb.log
else
    echo -e "${YELLOW}⚠️  Log de Reverb no encontrado${NC}"
fi

# 7. Test de conexión
echo -e "\n${YELLOW}[7/8] Intentando conexión a WebSocket...${NC}"
if timeout 5 curl -I https://sistemamundoindustrial.online:8080 2>/dev/null | grep -q "HTTP\|Connection"; then
    echo -e "${GREEN}✓ Puerto 8080 responde${NC}"
elif timeout 5 curl -I http://sistemamundoindustrial.online:8080 2>/dev/null | grep -q "HTTP\|Connection"; then
    echo -e "${YELLOW}⚠️  Puerto 8080 responde solo en HTTP${NC}"
else
    echo -e "${YELLOW}⚠️  No hay respuesta en puerto 8080${NC}"
    echo "     Posibles causas:"
    echo "     - Reverb no está corriendo"
    echo "     - Firewall bloquea el puerto"
    echo "     - Certificado SSL inválido"
fi

# 8. Verificar configuración de Nginx
echo -e "\n${YELLOW}[8/8] Verificando Nginx...${NC}"
if [ -f /etc/nginx/sites-enabled/sistemamundoindustrial.online ]; then
    echo -e "${GREEN}✓ Sitio Nginx encontrado${NC}"
    
    # Verificar que tenga soporte para WebSocket
    if grep -q "upgrade.*websocket\|WebSocket" /etc/nginx/sites-enabled/sistemamundoindustrial.online; then
        echo -e "${GREEN}✓ Nginx está configurado para WebSocket${NC}"
    else
        echo -e "${YELLOW}⚠️  Nginx podría no tener soporte para WebSocket${NC}"
        echo "     Verifica que el archivo de configuración tenga:"
        echo "     proxy_http_version 1.1;"
        echo "     proxy_set_header Upgrade \$http_upgrade;"
        echo "     proxy_set_header Connection \"upgrade\";"
    fi
    
    # Verificar proxy a puerto 8080
    if grep -q ":8080" /etc/nginx/sites-enabled/sistemamundoindustrial.online; then
        echo -e "${GREEN}✓ Nginx está configurado para redirigir a puerto 8080${NC}"
    else
        echo -e "${YELLOW}⚠️  Nginx podría no estar redirigiendo al puerto 8080${NC}"
    fi
else
    echo -e "${RED}✗ Sitio Nginx no encontrado${NC}"
fi

echo -e "\n${BLUE}═══════════════════════════════════════════════════════════════${NC}"

# Resumen
echo -e "\n${BLUE}📋 RESUMEN Y PRÓXIMOS PASOS:${NC}\n"

echo -e "${YELLOW}Para arreglar WebSocket:${NC}\n"

echo "1️⃣  Copiar archivo de configuración de Supervisor:"
echo "   sudo cp $PROJECT_PATH/reverb.conf /etc/supervisor/conf.d/"
echo ""

echo "2️⃣  Recargar Supervisor:"
echo "   sudo supervisorctl reread"
echo "   sudo supervisorctl update"
echo "   sudo supervisorctl start reverb"
echo ""

echo "3️⃣  Verificar que está corriendo:"
echo "   supervisorctl status reverb"
echo "   netstat -tln | grep 8080"
echo ""

echo "4️⃣  Monitorear logs:"
echo "   tail -f /var/log/mundo-industrial/reverb.log"
echo ""

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"

