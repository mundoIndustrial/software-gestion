#!/bin/bash

###############################################
# SCRIPT DE ACTUALIZACIÓN - MUNDO INDUSTRIAL
# Para ejecutar después de hacer git pull
# Uso: ./actualizar-vps.sh
###############################################

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PROJECT_PATH="/var/www/sistemamundoindustrial"
APP_USER="www-data"
APP_GROUP="www-data"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}MUNDO INDUSTRIAL - ACTUALIZACIÓN VPS${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# 1. Ir al directorio del proyecto
cd "$PROJECT_PATH" || exit 1

# 2. Hacer git pull
echo -e "${YELLOW}[1/6] Actualizando código...${NC}"
git pull origin main
if [ $? -ne 0 ]; then
    echo -e "${RED}✗ Error en git pull${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Código actualizado${NC}"
echo ""

# 3. Instalar dependencias PHP
echo -e "${YELLOW}[2/6] Instalando dependencias PHP...${NC}"
composer install --no-dev --optimize-autoloader
if [ $? -ne 0 ]; then
    echo -e "${RED}✗ Error en composer install${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Dependencias PHP instaladas${NC}"
echo ""

# 4. Compilar assets
echo -e "${YELLOW}[3/6] Compilando assets...${NC}"
npm install
npm run build
if [ $? -ne 0 ]; then
    echo -e "${RED}✗ Error en npm build${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Assets compilados${NC}"
echo ""

# 5. Ejecutar migraciones
echo -e "${YELLOW}[4/6] Ejecutando migraciones...${NC}"
sudo -u $APP_USER php artisan migrate --force
echo -e "${GREEN}✓ Migraciones completadas${NC}"
echo ""

# 6. Limpiar caches
echo -e "${YELLOW}[5/6] Limpiando caches...${NC}"
sudo -u $APP_USER php artisan config:clear
sudo -u $APP_USER php artisan cache:clear
sudo -u $APP_USER php artisan view:clear
echo -e "${GREEN}✓ Caches limpiadas${NC}"
echo ""

# 7. Establecer permisos
echo -e "${YELLOW}[6/6] Estableciendo permisos...${NC}"
chown -R $APP_USER:$APP_GROUP "$PROJECT_PATH"
chmod -R 755 "$PROJECT_PATH"
chmod -R 775 "$PROJECT_PATH/storage"
chmod -R 775 "$PROJECT_PATH/bootstrap/cache"
chmod -R 775 "$PROJECT_PATH/public"
echo -e "${GREEN}✓ Permisos establecidos${NC}"
echo ""

# 8. Reiniciar servicios
echo -e "${YELLOW}Reiniciando servicios...${NC}"
sudo supervisorctl restart all
sleep 2

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✓ ACTUALIZACIÓN COMPLETADA${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Estado de servicios:"
sudo supervisorctl status
