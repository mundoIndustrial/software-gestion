#!/bin/bash

# =====================================================================
# SCRIPT: Analizar Base de Datos y Generar Reporte
# Propósito: Ejecutar análisis SQL y crear documento con estructura
# =====================================================================

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}ANÁLISIS DE BASE DE DATOS - PROCESOS DE PEDIDOS${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Variables de conexión (ajusta según tu config)
DB_HOST="localhost"
DB_USER="root"
DB_PASS=""
DB_NAME="mundoindustrial"

echo -e "${YELLOW}📊 Conectando a base de datos: ${DB_NAME}${NC}"
echo ""

# Crear archivo de output
OUTPUT_FILE="ANALISIS_BD_ESTRUCTURA.md"
> "$OUTPUT_FILE"

# Función para ejecutar query
run_query() {
    local title=$1
    local query=$2
    
    echo -e "${BLUE}▶ ${title}${NC}"
    mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "$query" >> "$OUTPUT_FILE" 2>&1
    echo "" >> "$OUTPUT_FILE"
}

# Escribir header
cat > "$OUTPUT_FILE" << 'EOF'
# ANÁLISIS DE ESTRUCTURA DE BASE DE DATOS

**Fecha de análisis:** 2026-01-14
**Base de datos:** mundoindustrial

---

## 1. LISTADO DE TABLAS PRINCIPALES

EOF

# Query 1: Tablas
echo "Ejecutando: Listado de tablas..."
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "
SELECT 
    TABLE_NAME,
    TABLE_TYPE,
    TABLE_ROWS as 'Filas'
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;
" >> "$OUTPUT_FILE" 2>&1

echo "" >> "$OUTPUT_FILE"

# Query 2: Columnas de tablas importantes
echo "Ejecutando: Estructura de tablas clave..."

for table in pedidos pedido_items pedido_prendas reflectivo estampado bordado; do
    echo "### Tabla: $table" >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
    mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "DESCRIBE $table;" >> "$OUTPUT_FILE" 2>&1
    echo "" >> "$OUTPUT_FILE"
done

# Query 3: Relaciones foráneas
echo "### RELACIONES FORÁNEAS" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME;
" >> "$OUTPUT_FILE" 2>&1

echo "" >> "$OUTPUT_FILE"

# Query 4: Ejemplos de datos
echo "### EJEMPLO DE DATOS EXISTENTES" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"
echo "#### Últimos pedidos:" >> "$OUTPUT_FILE"
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "SELECT * FROM pedidos LIMIT 3;" >> "$OUTPUT_FILE" 2>&1

echo "Ejecutando: Análisis de relaciones..."
echo "" >> "$OUTPUT_FILE"

# Generar reporte markdown final
cat >> "$OUTPUT_FILE" << 'EOF'

---

## ANÁLISIS Y RECOMENDACIONES

### Estado Actual
- Las tablas de procesos (reflectivo, estampado, etc.) ya existen
- Hay relación con pedidos
- Necesitamos entender la estructura para adaptar nuevos campos

### Estructura Propuesta

Para almacenar los procesos con toda la información (ubicaciones, observaciones, tallas):

```sql
CREATE TABLE pedido_procesos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    pedido_id BIGINT UNSIGNED NOT NULL,
    pedido_item_id BIGINT UNSIGNED NOT NULL,
    tipo_proceso ENUM('reflectivo', 'bordado', 'estampado', 'dtf', 'sublimado') NOT NULL,
    ubicaciones JSON NOT NULL COMMENT 'Array de ubicaciones: ["Frente", "Espalda"]',
    observaciones TEXT,
    tallas_dama JSON COMMENT 'Array: ["S", "M", "L"]',
    tallas_caballero JSON COMMENT 'Array: ["M", "L", "XL"]',
    imagen LONGBLOB,
    imagen_nombre VARCHAR(255),
    estado VARCHAR(50) DEFAULT 'PENDIENTE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (pedido_item_id) REFERENCES pedido_items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_proceso (pedido_id, pedido_item_id, tipo_proceso),
    INDEX idx_estado (estado)
);
```

### Datos de Ejemplo

```json
{
    "tipo_proceso": "reflectivo",
    "ubicaciones": ["Frente", "Espalda"],
    "observaciones": "Reflectivo de 3M, color plateado",
    "tallas_dama": ["S", "M", "L"],
    "tallas_caballero": ["M", "L", "XL"],
    "imagen": "base64 o url"
}
```

EOF

echo -e "${GREEN}✅ Análisis completado${NC}"
echo -e "${GREEN}📄 Resultados guardados en: ${OUTPUT_FILE}${NC}"
echo ""
