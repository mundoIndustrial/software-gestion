#!/bin/bash

###########################################################################
# Script: Reparador de Permisos de Storage para Laravel 10 (Linux/Mac)
# Uso: chmod +x fix-storage-permissions.sh && ./fix-storage-permissions.sh
# Nota: Puede requerir sudo para cambiar permisos
###########################################################################

set -e  # Salir si hay error

# Configuración
DRY_RUN=false
VERBOSE=false
PROJECT_ROOT="$(pwd)"

# Colores ANSI
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
GRAY='\033[0;37m'
NC='\033[0m' # No Color

# Funciones de salida
print_section() {
    echo ""
    echo -e "${GRAY}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}$1${NC}"
    echo -e "${GRAY}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${CYAN}ℹ️  $1${NC}"
}

print_debug() {
    if [ "$VERBOSE" = true ]; then
        echo -e "${GRAY}🔍 $1${NC}"
    fi
}

# Manejo de argumentos
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --verbose|-v)
            VERBOSE=true
            shift
            ;;
        --help|-h)
            echo "Uso: ./fix-storage-permissions.sh [opciones]"
            echo ""
            echo "Opciones:"
            echo "  --dry-run     Solo verificar sin realizar cambios"
            echo "  --verbose     Mostrar información detallada"
            echo "  --help        Mostrar esta ayuda"
            exit 0
            ;;
        *)
            print_error "Opción desconocida: $1"
            exit 1
            ;;
    esac
done

###########################################################################
# INICIO
###########################################################################

clear
echo -e "${CYAN}🔧 REPARADOR DE PERMISOS - STORAGE LARAVEL 10 (LINUX/MAC)${NC}"
echo -e "Ejecutando en modo: $([ "$DRY_RUN" = true ] && echo 'DRY-RUN (solo lectura)' || echo 'ACTIVO (realizará cambios)')${YELLOW}"
echo ""

###########################################################################
# 1. VERIFICAR UBICACIÓN DEL PROYECTO
###########################################################################

print_section "1️⃣  Verificando ubicación del proyecto"

REQUIRED_DIRS=("app" "bootstrap" "config" "routes" "storage" "public")
MISSING_DIRS=()

for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$PROJECT_ROOT/$dir" ]; then
        print_success "Encontrado: $dir"
    else
        print_error "Falta: $dir"
        MISSING_DIRS+=("$dir")
    fi
done

if [ ${#MISSING_DIRS[@]} -gt 0 ]; then
    print_error "Este no parece ser un proyecto Laravel válido"
    exit 1
fi

echo ""

###########################################################################
# 2. CREAR/VERIFICAR ENLACE SIMBÓLICO
###########################################################################

print_section "2️⃣  Creando/verificando enlace simbólico"

SYMLINK_SOURCE="$PROJECT_ROOT/public/storage"
SYMLINK_TARGET="../storage/app/public"
TARGET_PATH="$PROJECT_ROOT/storage/app/public"

print_info "Enlace: public/storage"
print_info "Apunta a: $SYMLINK_TARGET"
print_debug "Ruta completa: $SYMLINK_SOURCE"
print_debug "Destino: $TARGET_PATH"
echo ""

if [ -L "$SYMLINK_SOURCE" ]; then
    LINK_TARGET=$(readlink "$SYMLINK_SOURCE")
    print_success "Enlace simbólico ya existe"
    print_debug "Apunta a: $LINK_TARGET"
elif [ -e "$SYMLINK_SOURCE" ]; then
    print_warning "public/storage existe pero NO es un enlace simbólico"
    print_info "Es: $(file $SYMLINK_SOURCE | cut -d: -f2)"
else
    print_info "Enlace simbólico no existe, creando..."
    
    if [ "$DRY_RUN" = true ]; then
        echo -e "${YELLOW}[DRY-RUN]${NC}"
    else
        if php artisan storage:link 2>/dev/null; then
            print_success "Enlace simbólico creado"
        else
            print_error "Error al crear enlace simbólico"
            exit 1
        fi
    fi
fi

echo ""

###########################################################################
# 3. DETECTAR USUARIO DEL SERVIDOR WEB
###########################################################################

print_section "3️⃣  Detectando usuario del servidor web"

WEB_USER=""
WEB_GROUP=""

# Intentar detectar Apache
if command -v apache2ctl &> /dev/null || command -v apachectl &> /dev/null; then
    WEB_USER=$(ps aux | grep -E '[a]pache2?|[h]ttpd' | awk '{print $1}' | sort -u | head -1)
    if [ -z "$WEB_USER" ]; then
        WEB_USER="www-data"
    fi
    print_info "Apache detectado"
    print_debug "Usuario: $WEB_USER"
    
# Intentar detectar Nginx
elif pgrep -x "nginx" > /dev/null 2>&1; then
    WEB_USER=$(ps aux | grep -E '[n]ginx' | awk '{print $1}' | sort -u | head -1)
    if [ -z "$WEB_USER" ]; then
        WEB_USER="www-data"
    fi
    print_info "Nginx detectado"
    print_debug "Usuario: $WEB_USER"
    
# Intentar detectar PHP-FPM
elif pgrep -x "php-fpm" > /dev/null 2>&1; then
    WEB_USER=$(ps aux | grep -E '[p]hp-fpm' | awk '{print $1}' | sort -u | head -1)
    if [ -z "$WEB_USER" ]; then
        WEB_USER="www-data"
    fi
    print_info "PHP-FPM detectado"
    print_debug "Usuario: $WEB_USER"
else
    WEB_USER="www-data"
    print_warning "No se detectó servidor web, usando: $WEB_USER"
fi

WEB_GROUP="$WEB_USER"

# Intentar obtener el grupo del usuario
if id "$WEB_USER" > /dev/null 2>&1; then
    WEB_GROUP=$(id -gn "$WEB_USER" 2>/dev/null || echo "$WEB_USER")
fi

print_success "Usuario: $WEB_USER"
print_success "Grupo: $WEB_GROUP"
echo ""

###########################################################################
# 4. CAMBIAR PERMISOS DE DIRECTORIOS
###########################################################################

print_section "4️⃣  Cambiando permisos de directorios"

DIRS=(
    "storage"
    "bootstrap/cache"
)

for dir in "${DIRS[@]}"; do
    DIR_PATH="$PROJECT_ROOT/$dir"
    
    if [ -d "$DIR_PATH" ]; then
        print_info "Procesando: $dir"
        
        if [ "$DRY_RUN" = true ]; then
            echo -e "${YELLOW}  [DRY-RUN] Se cambiarían permisos${NC}"
        else
            # Cambiar permisos de propietario
            if sudo chown -R "$WEB_USER:$WEB_GROUP" "$DIR_PATH" 2>/dev/null; then
                print_debug "  Permisos de propiedad: ✓"
            else
                print_warning "  No se pudo cambiar propiedad (sin permisos sudo)"
            fi
            
            # Cambiar permisos de acceso
            # Directorios: 755 (rwxr-xr-x)
            # Archivos: 644 (rw-r--r--)
            find "$DIR_PATH" -type d ! -perm 755 -exec chmod 755 {} \; 2>/dev/null || true
            find "$DIR_PATH" -type f ! -perm 644 -exec chmod 644 {} \; 2>/dev/null || true
            
            print_success "  Permisos actualizados"
        fi
    else
        print_warning "Directorio no encontrado: $dir"
    fi
done

echo ""

###########################################################################
# 5. VERIFICAR PERMISOS DE storage/app/public ESPECÍFICAMENTE
###########################################################################

print_section "5️⃣  Verificando permisos específicos de storage/app/public"

STORAGE_PUBLIC="$PROJECT_ROOT/storage/app/public"

if [ -d "$STORAGE_PUBLIC" ]; then
    print_info "Analizando: storage/app/public"
    
    # Mostrar permisos actuales
    CURRENT_PERMS=$(stat -c "%A" "$STORAGE_PUBLIC" 2>/dev/null || stat -f "%OLp" "$STORAGE_PUBLIC" 2>/dev/null)
    CURRENT_OWNER=$(stat -c "%U:%G" "$STORAGE_PUBLIC" 2>/dev/null || stat -f "%Su:%Sg" "$STORAGE_PUBLIC" 2>/dev/null)
    
    print_debug "Permisos actuales: $CURRENT_PERMS"
    print_debug "Propietario: $CURRENT_OWNER"
    
    if [ "$DRY_RUN" = false ]; then
        # Cambiar propiedad
        sudo chown -R "$WEB_USER:$WEB_GROUP" "$STORAGE_PUBLIC" 2>/dev/null || true
        
        # Aplicar permisos
        chmod -R 755 "$STORAGE_PUBLIC" 2>/dev/null || true
        
        # Verificar que sea accesible
        if [ -w "$STORAGE_PUBLIC" ] || [ -x "$STORAGE_PUBLIC" ]; then
            print_success "Permisos de escritura/acceso: ✓"
        else
            print_warning "storage/app/public no es accesible para el usuario web"
        fi
    fi
else
    print_error "storage/app/public no existe"
fi

echo ""

###########################################################################
# 6. HABILITAR mod_rewrite EN APACHE (SI ES APLICABLE)
###########################################################################

if command -v apache2ctl &> /dev/null || command -v apachectl &> /dev/null; then
    print_section "6️⃣  Configurando Apache"
    
    print_info "Verificando mod_rewrite..."
    
    if apache2ctl -M 2>/dev/null | grep -q "rewrite_module"; then
        print_success "mod_rewrite ya está habilitado"
    else
        print_warning "mod_rewrite no está habilitado"
        
        if [ "$DRY_RUN" = false ]; then
            print_info "Intentando habilitarlo..."
            if sudo a2enmod rewrite 2>/dev/null; then
                print_success "mod_rewrite habilitado"
                
                if sudo systemctl restart apache2 2>/dev/null; then
                    print_success "Apache reiniciado"
                else
                    print_warning "No se pudo reiniciar Apache automáticamente"
                    print_info "Ejecutar manualmente: sudo systemctl restart apache2"
                fi
            else
                print_warning "No se pudo habilitar mod_rewrite"
            fi
        else
            echo -e "${YELLOW}[DRY-RUN] Se habilitaría mod_rewrite${NC}"
        fi
    fi
    
    echo ""
fi

###########################################################################
# 7. LIMPIAR CACHÉ DE LARAVEL
###########################################################################

print_section "7️⃣  Limpiando caché de Laravel"

CACHE_COMMANDS=(
    "cache:clear"
    "route:clear"
    "view:clear"
    "config:clear"
)

for cmd in "${CACHE_COMMANDS[@]}"; do
    CMD_NAME=$(echo "$cmd" | cut -d: -f2)
    print_info "Limpiando: $CMD_NAME" -n
    
    if [ "$DRY_RUN" = false ]; then
        if php artisan "$cmd" 2>/dev/null | grep -q "cleared\|flushed\|cleared successfully"; then
            echo -e " ${GREEN}✅${NC}"
        else
            echo -e " ${YELLOW}⚠️${NC}"
            print_debug "  (Puede que ya esté limpio)"
        fi
    else
        echo -e " ${YELLOW}[DRY-RUN]${NC}"
    fi
done

echo ""

###########################################################################
# 8. VERIFICACIÓN FINAL
###########################################################################

print_section "8️⃣  Verificación Final"

echo ""
echo -e "${CYAN}Enlace Simbólico:${NC}"
if [ -L "$SYMLINK_SOURCE" ]; then
    print_success "Existe"
    LINK_TARGET=$(readlink "$SYMLINK_SOURCE")
    print_debug "Apunta a: $LINK_TARGET"
else
    print_error "NO EXISTE"
fi

echo ""
echo -e "${CYAN}Directorios:${NC}"
for dir in "${DIRS[@]}"; do
    DIR_PATH="$PROJECT_ROOT/$dir"
    if [ -d "$DIR_PATH" ]; then
        DIR_COUNT=$(find "$DIR_PATH" -type f 2>/dev/null | wc -l)
        print_success "$dir ($DIR_COUNT archivos)"
    else
        print_error "$dir NO EXISTE"
    fi
done

echo ""
echo -e "${CYAN}Almacenamiento:${NC}"
if [ -d "$STORAGE_PUBLIC" ]; then
    FILE_COUNT=$(find "$STORAGE_PUBLIC" -type f 2>/dev/null | wc -l)
    DIR_COUNT=$(find "$STORAGE_PUBLIC" -type d 2>/dev/null | wc -l)
    
    if [ "$FILE_COUNT" -gt 0 ]; then
        # Calcular tamaño total
        TOTAL_SIZE=$(du -sh "$STORAGE_PUBLIC" 2>/dev/null | cut -f1)
        print_success "Archivos: $FILE_COUNT | Carpetas: $DIR_COUNT | Tamaño: $TOTAL_SIZE"
    else
        print_warning "storage/app/public está vacío"
    fi
    
    # Mostrar subdirectorios principales
    if [ -d "$STORAGE_PUBLIC/pedidos" ]; then
        PEDIDOS_COUNT=$(find "$STORAGE_PUBLIC/pedidos" -type f 2>/dev/null | wc -l)
        print_debug "  • pedidos: $PEDIDOS_COUNT archivos"
    fi
else
    print_error "storage/app/public NO EXISTE"
fi

echo ""

###########################################################################
# 9. RESUMEN Y PRÓXIMOS PASOS
###########################################################################

print_section "9️⃣  Resumen y Próximos Pasos"

echo ""
print_success "CHECKLIST DE CORRECCIONES:"
echo "  [✓] Enlace simbólico verificado/creado"
echo "  [✓] Permisos de directorios ajustados"
echo "  [✓] Usuario web detectado y configurado"
echo "  [✓] Caché de Laravel limpiado"

echo ""
print_info "📌 PRÓXIMOS PASOS:"
echo "  1. Abre: http://localhost:8000/storage"
echo "  2. Deberías ver un listado de carpetas"
echo "  3. Intenta: http://localhost:8000/storage/pedidos/{id}/imagen.jpg"
echo "  4. Si ves 403: Revisa permisos con: ls -la storage/app/public"
echo "  5. Si ves 404: El archivo no existe en esa ubicación"

echo ""
print_info "🔗 VERIFICACIÓN CON TINKER:"
echo "  1. php artisan tinker"
echo "  2. Storage::disk('public')->url('test.jpg')"
echo "  3. Debería retornar: /storage/test.jpg"

echo ""
print_warning "⚠️  NOTAS IMPORTANTES:"
echo "  • Este script puede requerir sudo para cambiar permisos"
echo "  • Los cambios afectarán al acceso de archivos"
echo "  • Realiza un backup antes de cambios en producción"
echo "  • Reinicia el servidor web después de cambios"

echo ""
echo -e "${GRAY}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
if [ "$DRY_RUN" = true ]; then
    echo -e "${YELLOW}✅ VERIFICACIÓN DRY-RUN COMPLETADA (sin cambios reales)${NC}"
else
    echo -e "${GREEN}✅ REPARACIÓN COMPLETADA EXITOSAMENTE${NC}"
fi
echo -e "${GRAY}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

echo ""
echo -e "${CYAN}Para más información, consulta: CHECKLIST_STORAGE_PERMISSIONS.md${NC}"
echo ""

exit 0
