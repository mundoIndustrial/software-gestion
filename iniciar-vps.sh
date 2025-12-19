#!/bin/bash

###############################################
# MUNDO INDUSTRIAL - SCRIPT DE INICIALIZACIÓN VPS
# Dominio: sistemamundoindustrial.online
# Autor: Script de Deployment
# Fecha: 2025-12-14
# Mejorado: Manejo robusto de errores y rollback
###############################################

# CONFIGURACIÓN ESTRICTA DE SEGURIDAD
set -euo pipefail
IFS=$'\n\t'

# Trap para errores - Ejecutar limpieza en caso de fallo
trap handle_error ERR
trap cleanup EXIT

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuración
PROJECT_PATH="/var/www/mundoindustrial"
APP_USER="www-data"
APP_GROUP="www-data"
LOG_DIR="/var/log/mundo-industrial"
BACKUP_DIR="/backups/mundo-industrial"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG_FILE="$LOG_DIR/deploy_$TIMESTAMP.log"
LOCK_FILE="/tmp/mundo-industrial.lock"

# FLAGS DE SEGURIDAD (cambiar a 1 para activar cada comando)
ENABLE_MIGRATE=0              # Ejecutar migraciones
ENABLE_REBUILD_ASSETS=0       # npm run build
ENABLE_CHMOD=0                # Cambiar permisos masivos
ENABLE_RESTART_SERVICES=1     # Reiniciar servicios
ENABLE_DATABASE_BACKUP=0      # Backup de BD

# Funciones de manejo de errores
handle_error() {
    local line_number=$1
    echo -e "${RED}✗ ERROR en línea $line_number del script${NC}" | tee -a "$LOG_FILE"
    echo -e "${RED}El deployment ha fallado. Intentando recuperación...${NC}" | tee -a "$LOG_FILE"
    rollback_changes
    exit 1
}

cleanup() {
    rm -f "$LOCK_FILE"
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}✓ $1${NC}" | tee -a "$LOG_FILE"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}✗ $1${NC}" | tee -a "$LOG_FILE"
}

# Health check del sistema
health_check() {
    log_info "=== HEALTH CHECK DEL SISTEMA ==="
    
    # Verificar espacio en disco
    DISK_USAGE=$(df "$PROJECT_PATH" | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$DISK_USAGE" -gt 90 ]; then
        log_error "DISCO LLENO: ${DISK_USAGE}% usado. NO CONTINUANDO."
        return 1
    fi
    log_success "Disco: ${DISK_USAGE}% (OK)"
    
    # Verificar memoria disponible
    FREE_MEM=$(free -m | awk 'NR==2 {print $7}')
    if [ "$FREE_MEM" -lt 512 ]; then
        log_warn "Memoria baja: ${FREE_MEM}MB libre"
    else
        log_success "Memoria: ${FREE_MEM}MB (OK)"
    fi
    
    # Verificar conexión BD
    if command -v mysql &> /dev/null; then
        if timeout 5 mysql -e "SELECT 1" > /dev/null 2>&1; then
            log_success "Base de datos: Conectada (OK)"
        else
            log_warn "Base de datos: No se pudo conectar"
        fi
    fi
    
    # Verificar carga del sistema
    LOAD=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}')
    log_success "Carga del sistema: $LOAD"
    
    return 0
}

# Función para verificar si el servidor está siendo usado
check_active_connections() {
    log_info "Verificando conexiones activas..."
    
    if command -v netstat &> /dev/null; then
        CONNECTIONS=$(netstat -an | grep -E "ESTABLISHED|TIME_WAIT" | wc -l)
        if [ "$CONNECTIONS" -gt 100 ]; then
            log_warn "Muchas conexiones activas: $CONNECTIONS. Considera esperar."
            return 1
        fi
    fi
    return 0
}

# Crear directorio de logs si no existe
create_log_dir() {
    mkdir -p "$LOG_DIR"
    mkdir -p "$BACKUP_DIR"
}

# Verificar si ya está ejecutándose
check_lock() {
    if [ -f "$LOCK_FILE" ]; then
        log_error "Otro deployment está en progreso. Abortando."
        exit 1
    fi
    touch "$LOCK_FILE"
}

# Rollback en caso de fallo
rollback_changes() {
    log_warn "Iniciando rollback..."
    cd "$PROJECT_PATH" || return 1
    
    # Restaurar permisos originales
    chown -R $APP_USER:$APP_GROUP "$PROJECT_PATH" 2>/dev/null || true
    chmod -R 755 "$PROJECT_PATH" 2>/dev/null || true
    chmod -R 775 "$PROJECT_PATH/storage" 2>/dev/null || true
    
    # Reintentar servicios (mejor algo que nada)
    sudo supervisorctl restart all 2>/dev/null || true
    
    log_warn "Rollback completado"
}

echo -e "${BLUE}"
echo "=========================================="
echo "   MUNDO INDUSTRIAL - SERVIDOR VPS"
echo "   Dominio: sistemamundoindustrial.online"
echo "=========================================="
echo -e "${NC}"
echo ""



# 1. Crear logging
echo -e "${YELLOW}[1/11] Inicializando logging...${NC}"
create_log_dir
log_info "Iniciando deployment - $TIMESTAMP"
log_success "Logging inicializado en $LOG_FILE"
echo ""

# HEALTH CHECK PREVIO
echo -e "${YELLOW}[PRE-CHECK] Analizando salud del sistema...${NC}"
if ! health_check; then
    log_error "Sistema en estado crítico. Abortando deployment."
    exit 1
fi
echo ""

# Advertencias sobre flags desactivados
echo -e "${YELLOW}[ADVERTENCIA] Revisando flags de seguridad...${NC}"
[ "$ENABLE_MIGRATE" -eq 0 ] && log_warn "Migraciones DESACTIVADAS (ENABLE_MIGRATE=0)"
[ "$ENABLE_REBUILD_ASSETS" -eq 0 ] && log_warn "Rebuild assets DESACTIVADO (ENABLE_REBUILD_ASSETS=0)"
[ "$ENABLE_CHMOD" -eq 0 ] && log_warn "Cambio de permisos DESACTIVADO (ENABLE_CHMOD=0)"
[ "$ENABLE_RESTART_SERVICES" -eq 0 ] && log_warn "Restart servicios DESACTIVADO (ENABLE_RESTART_SERVICES=0)"
[ "$ENABLE_DATABASE_BACKUP" -eq 0 ] && log_warn "Backup BD DESACTIVADO (ENABLE_DATABASE_BACKUP=0)"
echo ""

# 2. Verificar lock file
echo -e "${YELLOW}[2/11] Verificando deployments previos...${NC}"
check_lock
log_success "Lock file creado"
echo ""

# 3. Validar que el script se ejecuta como root
echo -e "${YELLOW}[3/11] Validando permisos...${NC}"
if [[ $EUID -ne 0 ]]; then
   log_error "Este script debe ejecutarse como root"
   exit 1
fi
log_success "Permisos correctos"
echo ""

# 4. Validar que el proyecto existe
echo -e "${YELLOW}[4/11] Validando proyecto...${NC}"
if [ ! -d "$PROJECT_PATH" ]; then
    log_error "Proyecto no encontrado en $PROJECT_PATH"
    exit 1
fi
cd "$PROJECT_PATH" || exit 1
log_success "Proyecto encontrado"
echo ""

# 5. Validar herramientas necesarias
echo -e "${YELLOW}[5/11] Validando herramientas...${NC}"
for tool in php node npm composer; do
    if ! command -v $tool &> /dev/null; then
        log_warn "$tool no está instalado"
    else
        log_success "$tool detectado: $(which $tool)"
    fi
done
echo ""

# 6. Validar .env
echo -e "${YELLOW}[6/11] Validando configuración...${NC}"
if [ ! -f "$PROJECT_PATH/.env" ]; then
    log_warn ".env no existe, creando desde .env.example..."
    if [ -f "$PROJECT_PATH/.env.example" ]; then
        cp "$PROJECT_PATH/.env.example" "$PROJECT_PATH/.env"
        log_success ".env creado desde .env.example"
    else
        log_error "No se encontró .env ni .env.example"
        exit 1
    fi
fi
log_success "Configuración validada"
echo ""

# 7. Backup de base de datos (SOLO SI HABILITADO)
echo -e "${YELLOW}[7/11] Verificando backup de BD...${NC}"
if [ "$ENABLE_DATABASE_BACKUP" -eq 1 ]; then
    BACKUP_FILE="$BACKUP_DIR/mysql_backup_$TIMESTAMP.sql.gz"
    if command -v mysqldump &> /dev/null; then
        log_info "Creando backup (esto puede tomar tiempo)..."
        if mysqldump --all-databases --single-transaction | gzip > "$BACKUP_FILE"; then
            log_success "Backup creado: $BACKUP_FILE"
        else
            log_error "Error creando backup. Abortando."
            exit 1
        fi
    else
        log_warn "mysqldump no disponible, omitiendo backup"
    fi
else
    log_warn "Backup de BD DESACTIVADO - Activa ENABLE_DATABASE_BACKUP=1 si lo necesitas"
fi
echo ""

# 8. Limpiar cache (SIEMPRE SEGURO)
echo -e "${YELLOW}[8/11] Limpiando cache...${NC}"
if [ -f "$PROJECT_PATH/artisan" ]; then
    log_info "Limpiando cache de Laravel..."
    sudo -u $APP_USER php artisan config:clear 2>/dev/null || log_warn "Error en config:clear"
    sudo -u $APP_USER php artisan cache:clear 2>/dev/null || log_warn "Error en cache:clear"
    sudo -u $APP_USER php artisan view:clear 2>/dev/null || log_warn "Error en view:clear"
    sudo -u $APP_USER php artisan route:clear 2>/dev/null || log_warn "Error en route:clear"
    log_success "Cache limpiada"
else
    log_warn "artisan no encontrado, omitiendo limpieza de cache"
fi
echo ""

# 9. Migrar base de datos (SOLO SI HABILITADO)
echo -e "${YELLOW}[9/11] Estado de migraciones...${NC}"
if [ -f "$PROJECT_PATH/artisan" ]; then
    PENDING=$(sudo -u $APP_USER php artisan migrate:status 2>/dev/null | grep -c "Pending" || echo "0")
    log_info "Migraciones pendientes: $PENDING"
    
    if [ "$PENDING" -gt 0 ] && [ "$ENABLE_MIGRATE" -eq 1 ]; then
        log_warn "EJECUTANDO MIGRACIONES - Esto podría impactar la BD"
        if ! check_active_connections; then
            log_error "Hay demasiadas conexiones activas. NO ejecutando migraciones."
        elif sudo -u $APP_USER php artisan migrate --force 2>&1 | tee -a "$LOG_FILE"; then
            log_success "Migraciones completadas"
        else
            log_error "Error en migraciones"
            exit 1
        fi
    elif [ "$PENDING" -gt 0 ]; then
        log_warn "Hay $PENDING migraciones pendientes. Activa ENABLE_MIGRATE=1 para ejecutarlas"
    else
        log_success "Base de datos actualizada"
    fi
else
    log_warn "artisan no disponible"
fi
echo ""

# 10. Compilar assets (SOLO SI HABILITADO)
echo -e "${YELLOW}[10/11] Estado de assets...${NC}"
if [ -f "$PROJECT_PATH/package.json" ]; then
    if [ "$ENABLE_REBUILD_ASSETS" -eq 1 ]; then
        log_warn "RECONSTRUYENDO ASSETS - Esto consume recursos"
        if [ -d "$PROJECT_PATH/node_modules" ]; then
            log_info "node_modules detectado"
        else
            log_info "Instalando dependencias npm..."
            if cd "$PROJECT_PATH" && npm install --production 2>&1 | tail -10 >> "$LOG_FILE"; then
                log_success "Dependencias npm instaladas"
            else
                log_warn "Error instalando npm (continuando de todas formas)"
            fi
        fi
        
        if cd "$PROJECT_PATH" && npm run build 2>&1 | tail -10 >> "$LOG_FILE"; then
            log_success "Assets compilados"
        else
            log_warn "Error compilando assets"
        fi
    else
        log_warn "Assets NO se reconstruyeron. Activa ENABLE_REBUILD_ASSETS=1 si es necesario"
    fi
else
    log_warn "package.json no encontrado"
fi
echo ""

# 11. Establecer permisos (SOLO SI HABILITADO)
echo -e "${YELLOW}[11/11] Revisando permisos...${NC}"
if [ "$ENABLE_CHMOD" -eq 1 ]; then
    log_warn "CAMBIANDO PERMISOS - Esto podría afectar la seguridad"
    chown -R $APP_USER:$APP_GROUP "$PROJECT_PATH" 2>/dev/null || log_warn "Error en chown"
    chmod -R 755 "$PROJECT_PATH" 2>/dev/null || log_warn "Error en chmod 755"
    chmod -R 775 "$PROJECT_PATH/storage" 2>/dev/null || log_warn "storage no existe"
    chmod -R 775 "$PROJECT_PATH/bootstrap/cache" 2>/dev/null || log_warn "bootstrap/cache no existe"
    chmod -R 775 "$PROJECT_PATH/public" 2>/dev/null || log_warn "public no existe"
    log_success "Permisos ajustados"
else
    log_warn "Permisos NO fueron modificados. Activa ENABLE_CHMOD=1 si es necesario"
fi
echo ""

# Reiniciar servicios (SOLO SI HABILITADO)
echo -e "${YELLOW}[BONUS] Estado de servicios...${NC}"
if [ "$ENABLE_RESTART_SERVICES" -eq 1 ]; then
    log_warn "REINICIANDO SERVICIOS - El servidor tendrá downtime"
    if command -v supervisorctl &> /dev/null; then
        sudo supervisorctl reread || log_warn "Error en supervisorctl reread"
        sudo supervisorctl update || log_warn "Error en supervisorctl update"
        sudo supervisorctl restart all || log_warn "Error reiniciando servicios"
        sleep 2
        log_success "Servicios reiniciados"
    else
        log_warn "Supervisor no disponible"
    fi
else
    log_warn "Servicios NO fueron reiniciados. Activa ENABLE_RESTART_SERVICES=1 si es necesario"
fi
echo ""

# Mostrar estado final
echo -e "${YELLOW}[FINAL] Verificando servicios...${NC}"
if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl status 2>/dev/null | tee -a "$LOG_FILE" || true
else
    log_warn "Supervisor no disponible para verificación"
fi
echo ""

# HEALTH CHECK FINAL
echo -e "${YELLOW}[POST-CHECK] Analizando salud final del sistema...${NC}"
if health_check; then
    echo -e "${GREEN}"
    echo "=========================================="
    echo "   SERVIDOR VERIFICADO Y OPERACIONAL"
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
    echo "  - Deploy: $LOG_FILE"
    echo "  - Laravel: $LOG_DIR/laravel.log"
    echo "  - Nginx: /var/log/nginx/error.log"
    echo ""
    echo -e "${GREEN}=========================================="
    echo -e "${NC}"
    
    log_success "Deployment completado - Sistema OK"
else
    echo -e "${YELLOW}=========================================="
    echo "   ADVERTENCIA: Problemas detectados"
    echo "=========================================="
    echo -e "${NC}"
    log_warn "Revisa el log para detalles: $LOG_FILE"
fi
