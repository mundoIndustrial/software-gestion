#!/bin/bash
# Script de prueba rápida para Google OAuth

echo "================================================"
echo "VERIFICAR CONFIGURACIÓN DE GOOGLE OAUTH"
echo "================================================"
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Verificar que .env tenga las variables
echo -e "${YELLOW}1. Verificando variables en .env...${NC}"
if grep -q "GOOGLE_CLIENT_ID=" .env; then
    CLIENT_ID=$(grep "GOOGLE_CLIENT_ID=" .env | cut -d'=' -f2)
    echo -e "${GREEN}✓ GOOGLE_CLIENT_ID: ${CLIENT_ID:0:20}...${NC}"
else
    echo -e "${RED}✗ GOOGLE_CLIENT_ID no encontrado${NC}"
fi

if grep -q "GOOGLE_CLIENT_SECRET=" .env; then
    echo -e "${GREEN}✓ GOOGLE_CLIENT_SECRET: configurado${NC}"
else
    echo -e "${RED}✗ GOOGLE_CLIENT_SECRET no encontrado${NC}"
fi

if grep -q "GOOGLE_REDIRECT_URI=" .env; then
    REDIRECT=$(grep "GOOGLE_REDIRECT_URI=" .env | cut -d'=' -f2)
    echo -e "${GREEN}✓ GOOGLE_REDIRECT_URI: ${REDIRECT}${NC}"
else
    echo -e "${RED}✗ GOOGLE_REDIRECT_URI no encontrado${NC}"
fi

echo ""

# 2. Verificar que la ruta exista
echo -e "${YELLOW}2. Verificando rutas de Google OAuth...${NC}"
if grep -q "auth/google" routes/auth.php; then
    echo -e "${GREEN}✓ Rutas de Google OAuth encontradas${NC}"
else
    echo -e "${RED}✗ Rutas de Google OAuth no encontradas${NC}"
fi

echo ""

# 3. Verificar que Socialite esté instalado
echo -e "${YELLOW}3. Verificando instalación de Socialite...${NC}"
if grep -q "laravel/socialite" composer.json; then
    echo -e "${GREEN}✓ Socialite está en composer.json${NC}"
else
    echo -e "${RED}✗ Socialite no está instalado${NC}"
fi

echo ""

# 4. Verificar config/socialite.php
echo -e "${YELLOW}4. Verificando config/socialite.php...${NC}"
if [ -f "config/socialite.php" ]; then
    echo -e "${GREEN}✓ Archivo config/socialite.php existe${NC}"
else
    echo -e "${RED}✗ config/socialite.php no existe${NC}"
fi

echo ""
echo -e "${YELLOW}5. Limpiar caché de Laravel...${NC}"
php artisan config:clear
php artisan cache:clear
echo -e "${GREEN}✓ Caché limpiado${NC}"

echo ""
echo "================================================"
echo -e "${GREEN}VERIFICACIÓN COMPLETADA${NC}"
echo "================================================"
echo ""
echo "Si todo es verde, prueba en: http://localhost:8000/login"
echo "Haz clic en 'Iniciar sesión con Google'"
echo ""
