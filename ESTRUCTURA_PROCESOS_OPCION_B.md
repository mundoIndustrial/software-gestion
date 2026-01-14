# 📋 Estructura de Tablas para Procesos - OPCIÓN B (2 Tablas)

## Diagrama de Relaciones

```
┌─────────────────────────────────────────────────────────────────┐
│                    pedidos_produccion                           │
│ ────────────────────────────────────────────────────────────────│
│ id (PK)                                                          │
│ numero_pedido (UNIQUE)                                          │
│ cliente_id → clientes                                           │
│ asesor_id → users                                               │
│ estado (enum)                                                   │
│ ...                                                             │
└───────────────────────────────┬─────────────────────────────────┘
                                │ (numero_pedido)
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                      prendas_pedido                             │
│ ────────────────────────────────────────────────────────────────│
│ id (PK)                                                          │
│ numero_pedido (FK → pedidos_produccion.numero_pedido)           │
│ nombre_prenda                                                   │
│ cantidad                                                        │
│ cantidad_talla (JSON)  ← {dama: {...}, caballero: {...}}       │
│ genero (JSON)          ← [dama, caballero]                     │
│ color_id, tela_id                                              │
│ tiene_reflectivo (boolean)                                      │
│ ...                                                             │
└───────────────────────────────┬─────────────────────────────────┘
                                │ (id)
                ┌───────────────┴───────────────┐
                │                               │
                ▼                               ▼
    ┌─────────────────────────┐      ┌──────────────────────────┐
    │ prendas_reflectivo      │      │ procesos_prenda_detalles │
    │ (Tabla Existente)       │      │ (TABLA NUEVA)            │
    │─────────────────────────│      │──────────────────────────│
    │ id (PK)                 │      │ id (PK)                  │
    │ prenda_pedido_id (FK)   │      │ prenda_pedido_id (FK)    │
    │ nombre_producto         │      │ tipo_proceso_id (FK)     │
    │ ubicaciones (JSON) ✓    │      │ ubicaciones (JSON) ✓     │
    │ generos (JSON)          │      │ observaciones (TEXT)     │
    │ cantidad_talla (JSON)   │      │ tallas_dama (JSON) ✓     │
    │ ...                     │      │ tallas_caballero (JSON)✓ │
    └─────────────────────────┘      │ imagen_ruta (VARCHAR)    │
                                     │ estado (ENUM)            │
                                     │ aprobado_por (FK)        │
                                     │ datos_adicionales (JSON) │
                                     │ timestamps               │
                                     └────────────┬─────────────┘
                                                  │ (tipo_proceso_id)
                                                  │
                                                  ▼
                                    ┌──────────────────────────┐
                                    │   tipos_procesos         │
                                    │ (TABLA NUEVA - Catálogo) │
                                    │──────────────────────────│
                                    │ id (PK)                  │
                                    │ nombre (UNIQUE) ✓        │
                                    │ slug (UNIQUE)            │
                                    │ descripcion              │
                                    │ color (#HEX)             │
                                    │ icono (nombre)           │
                                    │ activo (boolean)         │
                                    │ timestamps               │
                                    └──────────────────────────┘
```

## 📊 TABLA 1: tipos_procesos (CATÁLOGO)

**Propósito:** Almacenar los tipos de procesos disponibles como referencia estática.

**Campos:**

| Campo | Tipo | Nulo | Key | Descripción |
|-------|------|------|-----|-------------|
| id | BIGINT UNSIGNED | ✗ | PRI | Identificador único |
| nombre | VARCHAR(50) | ✗ | UNI | reflectivo, bordado, estampado, dtf, sublimado |
| slug | VARCHAR(50) | ✗ | UNI | URL-friendly name |
| descripcion | TEXT | ✓ | - | Descripción del proceso |
| color | VARCHAR(7) | ✓ | - | Código HEX para UI (#FFB000) |
| icono | VARCHAR(100) | ✓ | - | Nombre del ícono (para Font Awesome, etc) |
| activo | BOOLEAN | ✗ | - | Si está disponible |
| created_at | TIMESTAMP | ✓ | - | Fecha creación |
| updated_at | TIMESTAMP | ✓ | - | Fecha actualización |
| deleted_at | TIMESTAMP | ✓ | - | Soft delete |

**Datos Iniciales:**

```json
[
  {
    "nombre": "Reflectivo",
    "slug": "reflectivo",
    "descripcion": "Material reflectivo de seguridad que brilla en la oscuridad",
    "color": "#FFB000",
    "icono": "shield-alert"
  },
  {
    "nombre": "Bordado",
    "slug": "bordado",
    "descripcion": "Bordado personalizado en máquina",
    "color": "#8B4513",
    "icono": "needle-thread"
  },
  {
    "nombre": "Estampado",
    "slug": "estampado",
    "descripcion": "Estampado de imágenes o logos en prendas",
    "color": "#FF6B6B",
    "icono": "image"
  },
  {
    "nombre": "DTF",
    "slug": "dtf",
    "descripcion": "Direct-to-Fabric: Impresión directa en tela",
    "color": "#4ECDC4",
    "icono": "printer"
  },
  {
    "nombre": "Sublimado",
    "slug": "sublimado",
    "descripcion": "Sublimación: Transferencia de tinta sublimada a tela",
    "color": "#A8E6CF",
    "icono": "cloud-upload"
  }
]
```

## 📦 TABLA 2: procesos_prenda_detalles (DETALLES POR PRENDA)

**Propósito:** Almacenar los detalles específicos de cada proceso asignado a cada prenda del pedido.

**Campos:**

| Campo | Tipo | Nulo | Key | Descripción |
|-------|------|------|-----|-------------|
| id | BIGINT UNSIGNED | ✗ | PRI | Identificador único |
| prenda_pedido_id | BIGINT UNSIGNED | ✗ | FK,UNI | FK → prendas_pedido.id |
| tipo_proceso_id | BIGINT UNSIGNED | ✗ | FK,UNI | FK → tipos_procesos.id |
| ubicaciones | JSON | ✗ | - | ["Frente", "Espalda", "Manga"] |
| observaciones | TEXT | ✓ | - | Notas personalizadas |
| tallas_dama | JSON | ✓ | - | ["S", "M", "L"] |
| tallas_caballero | JSON | ✓ | - | ["M", "L", "XL"] |
| imagen_ruta | VARCHAR(500) | ✓ | - | /storage/procesos/... |
| nombre_imagen | VARCHAR | ✓ | - | Nombre original del archivo |
| tipo_mime | VARCHAR | ✓ | - | image/jpeg, image/png, etc |
| tamaño_imagen | BIGINT | ✓ | - | Tamaño en bytes |
| estado | ENUM | ✗ | - | PENDIENTE, EN_REVISION, APROBADO, EN_PRODUCCION, COMPLETADO, RECHAZADO |
| notas_rechazo | TEXT | ✓ | - | Motivo si fue rechazado |
| fecha_aprobacion | DATETIME | ✓ | - | Cuándo fue aprobado |
| aprobado_por | BIGINT UNSIGNED | ✓ | FK | FK → users.id (quién aprobó) |
| datos_adicionales | JSON | ✓ | - | Campos flexibles según proceso |
| created_at | TIMESTAMP | ✓ | - | Fecha creación |
| updated_at | TIMESTAMP | ✓ | - | Fecha actualización |
| deleted_at | TIMESTAMP | ✓ | - | Soft delete |

**Restricciones:**
- UNIQUE KEY (prenda_pedido_id, tipo_proceso_id) → Una prenda solo puede tener 1 reflectivo, 1 bordado, etc.
- FOREIGN KEY prenda_pedido_id → CASCADE on delete
- FOREIGN KEY tipo_proceso_id → RESTRICT on delete (no borrar tipo si hay procesos asignados)
- FOREIGN KEY aprobado_por → SET NULL on delete

## 📝 Ejemplo de Fila en procesos_prenda_detalles

```json
{
  "id": 1,
  "prenda_pedido_id": 150,
  "tipo_proceso_id": 1,
  "ubicaciones": ["Frente", "Espalda", "Manga derecha"],
  "observaciones": "Reflectivo de 3M color plateado, visibilidad máxima. Coser con hilo resistente.",
  "tallas_dama": ["S", "M", "L", "XL"],
  "tallas_caballero": ["M", "L", "XL", "XXL"],
  "imagen_ruta": "/storage/procesos/reflectivo-150-2026-01-14.jpg",
  "nombre_imagen": "reflectivo-diseño.jpg",
  "tipo_mime": "image/jpeg",
  "tamaño_imagen": 2048000,
  "estado": "APROBADO",
  "fecha_aprobacion": "2026-01-14 10:30:00",
  "aprobado_por": 5,
  "datos_adicionales": {
    "ancho_reflectivo": "5cm",
    "tipo_material": "3M Scotchlite",
    "acabado": "mate"
  },
  "created_at": "2026-01-14 09:00:00",
  "updated_at": "2026-01-14 10:30:00"
}
```

## 🔄 Flujo de Datos desde Modal

```
1. Usuario configura proceso en modal:
   ├─ Selecciona tipo: "reflectivo" (ID: 1)
   ├─ Escribe ubicaciones: ["Frente", "Espalda"]
   ├─ Escribe observaciones: "Reflectivo de 3M"
   ├─ Selecciona tallas: dama: ["S", "M"], caballero: ["L"]
   └─ Sube imagen: reflectivo.jpg

2. Modal envía POST /api/pedidos/{id}/procesos:
   {
     "prenda_pedido_id": 150,
     "tipo_proceso_id": 1,
     "ubicaciones": ["Frente", "Espalda"],
     "observaciones": "Reflectivo de 3M",
     "tallas_dama": ["S", "M"],
     "tallas_caballero": ["L"],
     "imagen": <base64 o file>
   }

3. Backend guarda en procesos_prenda_detalles:
   ├─ Valida que tipo_proceso_id exista en tipos_procesos
   ├─ Valida que prenda_pedido_id exista en prendas_pedido
   ├─ Valida que no exista otro proceso del mismo tipo para esa prenda
   ├─ Guarda imagen en /storage/procesos/
   ├─ Inserta fila con estado = 'PENDIENTE'
   └─ Retorna ID y estado

4. Frontend actualiza:
   ├─ Muestra proceso agregado en resumen
   ├─ Permite editar o eliminar
   ├─ Muestra estado (PENDIENTE, APROBADO, etc)
   └─ Bloquea cambios si está APROBADO
```

## ✅ Ventajas de Esta Estructura

1. **Normalización DB**: tipos_procesos como tabla de referencia
2. **Escalabilidad**: Agregar nuevo proceso = insertar 1 fila en tipos_procesos
3. **Flexibilidad**: Campos JSON para datos variables por proceso
4. **Auditoría**: Tracking de aprobaciones con aprobado_por y fecha_aprobacion
5. **Control**: Estado permite workflow (PENDIENTE → APROBADO → EN_PRODUCCION → COMPLETADO)
6. **Integridad**: Restricciones foráneas garantizan consistencia
7. **Compatibilidad**: Coexiste con prendas_reflectivo existente
8. **Queries Eficientes**: Índices en estado, tipo_proceso_id, created_at

## 🚀 Próximos Pasos

1. ✅ Crear migración (2026_01_14_000000_create_procesos_tables.php)
2. ✅ Crear seeder (TiposProcesosSeeder.php)
3. ⏳ Crear Modelos (TipoProceso.php, ProcesoPrendaDetalle.php)
4. ⏳ Crear Controller (ProcesosController.php)
5. ⏳ Crear API Routes (/api/pedidos/{id}/procesos)
6. ⏳ Actualizar Modal JavaScript para enviar datos correctos
7. ⏳ Crear vista para mostrar procesos agregados
8. ⏳ Implementar aprobación de procesos

