#!/bin/bash

#############################################
# SCRIPT PARA INICIAR LARAVEL REVERB
# WebSocket Server para tiempo real
# Dominio: sistemamundoindustrial.online
#############################################

PROJECT_PATH="/var/www/mundoindustrial"
APP_USER="www-data"
LOG_FILE="/var/log/mundo-industrial/reverb.log"
PID_FILE="/tmp/reverb.pid"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}═══════════════════════════════════════════${NC}"
echo -e "${BLUE}  INICIANDO LARAVEL REVERB (WebSocket)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════${NC}\n"

# Verificar que el proyecto existe
if [ ! -d "$PROJECT_PATH" ]; then
    echo -e "${RED}✗ Proyecto no encontrado en $PROJECT_PATH${NC}"
    exit 1
fi

cd "$PROJECT_PATH" || exit 1

# Crear directorio de logs si no existe
mkdir -p "$(dirname "$LOG_FILE")"

# Verificar si Reverb ya está corriendo
if [ -f "$PID_FILE" ]; then
    OLD_PID=$(cat "$PID_FILE")
    if ps -p "$OLD_PID" > /dev/null 2>&1; then
        echo -e "${YELLOW}⚠️  Reverb ya está corriendo (PID: $OLD_PID)${NC}"
        echo -e "${YELLOW}Matando el proceso anterior...${NC}"
        sudo kill -9 "$OLD_PID" 2>/dev/null || true
        sleep 1
    fi
    rm -f "$PID_FILE"
fi

echo -e "${BLUE}📋 Configuración:${NC}"
echo -e "   Host: 0.0.0.0:8080"
echo -e "   Dominio: sistemamundoindustrial.online"
echo -e "   Usuario: $APP_USER"
echo -e "   Logs: $LOG_FILE"
echo -e "   PID: $PID_FILE\n"

# Obtener rutas de SSL
SSL_CERT="/etc/letsencrypt/live/sistemamundoindustrial.online/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/sistemamundoindustrial.online/privkey.pem"

# Verificar certificados SSL
echo -e "${BLUE}🔐 Verificando certificados SSL...${NC}"
if [ -f "$SSL_CERT" ] && [ -f "$SSL_KEY" ]; then
    echo -e "${GREEN}✓ Certificados encontrados${NC}\n"
    USE_SSL="--with-tls=$SSL_CERT --with-key=$SSL_KEY"
else
    echo -e "${YELLOW}⚠️  Certificados SSL no encontrados, usando HTTP${NC}"
    echo -e "   Rutas esperadas:"
    echo -e "   - $SSL_CERT"
    echo -e "   - $SSL_KEY\n"
    USE_SSL=""
fi

# Limpiar y optimizar cache
echo -e "${BLUE}🧹 Limpiando cache...${NC}"
sudo -u $APP_USER php artisan config:cache > /dev/null 2>&1 || true
echo -e "${GREEN}✓ Cache limpiado${NC}\n"

# Iniciar Reverb con opciones optimizadas
echo -e "${BLUE}🚀 Iniciando Reverb...${NC}"
echo -e "${YELLOW}Comando: php artisan reverb:start --host=0.0.0.0 --port=8080 $USE_SSL${NC}\n"

# Ejecutar como el usuario de la app
sudo -u $APP_USER php artisan reverb:start \
    --host=0.0.0.0 \
    --port=8080 \
    $USE_SSL \
    2>&1 | tee -a "$LOG_FILE" &

REVERB_PID=$!
echo "$REVERB_PID" > "$PID_FILE"

echo -e "\n${GREEN}✓ Reverb iniciado (PID: $REVERB_PID)${NC}"
echo -e "${BLUE}📊 Estado del proceso:${NC}"
sleep 2
ps aux | grep -E "reverb:start|$REVERB_PID" | grep -v grep || echo -e "${RED}✗ El proceso no está corriendo${NC}"

echo -e "\n${BLUE}═══════════════════════════════════════════${NC}"
echo -e "${GREEN}✓ Reverb está ejecutándose en segundo plano${NC}"
echo -e "${BLUE}═══════════════════════════════════════════${NC}\n"

echo -e "${BLUE}📝 Para monitorear logs:${NC}"
echo "   tail -f $LOG_FILE"
echo -e "\n${BLUE}⏹️  Para detener Reverb:${NC}"
echo "   kill -9 $REVERB_PID"
echo "   # o"
echo "   supervisorctl stop reverb"

