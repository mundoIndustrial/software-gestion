#!/bin/bash

###############################################
# MUNDO INDUSTRIAL - SCRIPT DE INICIALIZACIÓN VPS
# Dominio: sistemamundoindustrial.online
# Autor: Script de Deployment
# Fecha: 2025-12-14
###############################################

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuración
PROJECT_PATH="/var/www/sistemamundoindustrial"
APP_USER="www-data"
APP_GROUP="www-data"

echo -e "${BLUE}"
echo "=========================================="
echo "   MUNDO INDUSTRIAL - SERVIDOR VPS"
echo "   Dominio: sistemamundoindustrial.online"
echo "=========================================="
echo -e "${NC}"
echo ""

# 1. Validar que el script se ejecuta como root
echo -e "${YELLOW}[1/8] Validando permisos...${NC}"
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}Este script debe ejecutarse como root${NC}"
   exit 1
fi
echo -e "${GREEN}✓ Permisos correctos${NC}"
echo ""

# 2. Validar que el proyecto existe
echo -e "${YELLOW}[2/8] Validando proyecto...${NC}"
if [ ! -d "$PROJECT_PATH" ]; then
    echo -e "${RED}✗ Proyecto no encontrado en $PROJECT_PATH${NC}"
    exit 1
fi
cd "$PROJECT_PATH" || exit 1
echo -e "${GREEN}✓ Proyecto encontrado${NC}"
echo ""

# 3. Limpiar cache
echo -e "${YELLOW}[3/8] Limpiando cache...${NC}"
sudo -u $APP_USER php artisan config:clear
sudo -u $APP_USER php artisan cache:clear
sudo -u $APP_USER php artisan view:clear
sudo -u $APP_USER php artisan route:clear
echo -e "${GREEN}✓ Cache limpiada${NC}"
echo ""

# 4. Migrar base de datos (si es necesario)
echo -e "${YELLOW}[4/8] Verificando migraciones de BD...${NC}"
sudo -u $APP_USER php artisan migrate --force
echo -e "${GREEN}✓ Migraciones completadas${NC}"
echo ""

# 5. Compilar assets
echo -e "${YELLOW}[5/8] Compilando assets...${NC}"
npm run build
chown -R $APP_USER:$APP_GROUP "$PROJECT_PATH/public"
echo -e "${GREEN}✓ Assets compilados${NC}"
echo ""

# 6. Establecer permisos correctos
echo -e "${YELLOW}[6/8] Estableciendo permisos...${NC}"
chown -R $APP_USER:$APP_GROUP "$PROJECT_PATH"
chmod -R 755 "$PROJECT_PATH"
chmod -R 775 "$PROJECT_PATH/storage"
chmod -R 775 "$PROJECT_PATH/bootstrap/cache"
chmod -R 775 "$PROJECT_PATH/public"
echo -e "${GREEN}✓ Permisos establecidos${NC}"
echo ""

# 7. Reiniciar servicios con Supervisor
echo -e "${YELLOW}[7/8] Reiniciando servicios (Supervisor)...${NC}"
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all
sleep 2
echo -e "${GREEN}✓ Servicios reiniciados${NC}"
echo ""

# 8. Mostrar estado
echo -e "${YELLOW}[8/8] Verificando estado de servicios...${NC}"
echo ""
sudo supervisorctl status
echo ""

echo -e "${GREEN}"
echo "=========================================="
echo "   SERVIDOR INICIADO CORRECTAMENTE"
echo "=========================================="
echo ""
echo -e "${BLUE}Acceso:${NC}"
echo "  - HTTPS: https://sistemamundoindustrial.online"
echo "  - HTTP:  http://sistemamundoindustrial.online"
echo ""
echo -e "${BLUE}Servicios:${NC}"
echo "  - Nginx (Web Server): Puerto 80/443"
echo "  - Laravel Reverb (WebSocket): Puerto 8080"
echo "  - Queue Worker: Activo"
echo "  - Laravel App: Activo"
echo ""
echo -e "${BLUE}Logs:${NC}"
echo "  - Laravel: /var/log/mundo-industrial/laravel.log"
echo "  - Nginx: /var/log/nginx/error.log"
echo "  - Supervisor: /var/log/supervisor/"
echo ""
echo -e "${GREEN}=========================================="
echo -e "${NC}"
