# ANÁLISIS DE BASE DE DATOS - PLAN DE ALMACENAMIENTO DE PROCESOS
# Fecha: 14 de Enero 2026
# Objetivo: Entender cómo guardar los procesos (Reflectivo, Bordado, Estampado, DTF, Sublimado)

## ESTRUCTURA ACTUAL QUE NECESITAMOS ANALIZAR:

### 1. TABLA: pedidos
- id (PK)
- numero_pedido
- cliente_id (FK)
- estado
- created_at
- updated_at

### 2. TABLA: pedido_items
- id (PK)
- pedido_id (FK → pedidos)
- tipo_item (P = Prenda, EPP = Equipo Protección Personal)
- origen_id (FK → prendas o eppitems)
- cantidad_total
- estado
- created_at

### 3. TABLA: pedido_prendas (si existe) O similar
- id (PK)
- pedido_id (FK → pedidos)
- prenda_id (FK → prendas)
- cantidad_por_talla (JSON o relación)
- estado
- created_at

### 4. TABLA: reflectivo (PATRÓN EXISTENTE PARA PROCESOS)
- id (PK)
- pedido_id (FK → pedidos)
- tipo_reflectivo
- cantidad
- ubicaciones
- observaciones
- imagen
- created_at

## LO QUE NECESITAMOS GUARDAR:

Para cada PROCESO (Reflectivo, Bordado, Estampado, DTF, Sublimado):

1. **Ubicaciones**: Array o string separado por comas
   - Ej: ["Frente", "Espalda", "Manga derecha"]
   - Guardado como: JSON o TEXT

2. **Observaciones**: Texto
   - Ej: "Colores específicos, tamaño 10cm"
   - Guardado como: TEXT o VARCHAR

3. **Tallas Aplicables**: Por género
   - DAMA: [S, M, L]
   - CABALLERO: [M, L, XL]
   - Guardado como: JSON o relación separada

4. **Imagen**: Referencia a imagen subida
   - Ej: url o path
   - Guardado como: VARCHAR o LONGBLOB

## OPCIONES DE DISEÑO:

### OPCIÓN A: Tabla por proceso (Similar a reflectivo actual)
```
Tabla: pedido_reflectivo
- id
- pedido_id (FK)
- pedido_prenda_id (FK) ← cuál prenda específica
- ubicaciones (JSON)
- observaciones (TEXT)
- tallas_dama (JSON) ← ["S", "M", "L"]
- tallas_caballero (JSON) ← ["M", "L"]
- imagen (VARCHAR)
- estado
- created_at, updated_at

Similar para: pedido_bordado, pedido_estampado, pedido_dtf, pedido_sublimado
```

### OPCIÓN B: Tabla única para todos los procesos
```
Tabla: pedido_procesos
- id
- pedido_id (FK)
- pedido_prenda_id (FK)
- tipo_proceso (ENUM: 'reflectivo', 'bordado', 'estampado', 'dtf', 'sublimado')
- ubicaciones (JSON)
- observaciones (TEXT)
- tallas_dama (JSON)
- tallas_caballero (JSON)
- imagen (VARCHAR)
- estado
- created_at, updated_at

Índices: UNIQUE(pedido_id, pedido_prenda_id, tipo_proceso)
```

### OPCIÓN C: Tabla pivote para tallas + tabla de procesos
```
Tabla: pedido_procesos
- id
- pedido_id (FK)
- pedido_prenda_id (FK)
- tipo_proceso
- ubicaciones (JSON)
- observaciones (TEXT)
- imagen (VARCHAR)
- estado

Tabla: pedido_proceso_tallas
- id
- proceso_id (FK → pedido_procesos)
- genero (ENUM: 'dama', 'caballero')
- talla
- UNIQUE(proceso_id, genero, talla)
```

## RECOMENDACIÓN:

**OPCIÓN B (Tabla única)** es la más flexible y escalable porque:
1. ✅ Fácil de consultar todos los procesos de un pedido
2. ✅ Usa JSON para las tallas (es estándar en MySQL 5.7+)
3. ✅ No hay múltiples tablas innecesarias
4. ✅ Fácil de agregar más campos sin modificar schema
5. ✅ Mejor rendimiento que múltiples JOINs

## PRÓXIMO PASO:

Ejecuta el script `analisis-base-datos.sql` en la base de datos para ver:
1. Tablas existentes
2. Estructura actual de cada tabla
3. Relaciones entre tablas

Luego decidiremos si crear tabla nueva o adaptar las existentes.
