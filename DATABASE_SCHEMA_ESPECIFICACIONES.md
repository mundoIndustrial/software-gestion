# 🗄️ DATABASE SCHEMA: forma_pago Storage

## Table Structure

```sql
CREATE TABLE cotizaciones (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    asesor_id BIGINT UNSIGNED NOT NULL,
    cliente_id BIGINT UNSIGNED NOT NULL,
    numero_cotizacion VARCHAR(255) NULLABLE,
    tipo_cotizacion_id BIGINT UNSIGNED NULLABLE,
    tipo_venta VARCHAR(50) NULLABLE,
    fecha_inicio TIMESTAMP NULLABLE,
    fecha_envio TIMESTAMP NULLABLE,
    fecha_enviado_a_aprobador TIMESTAMP NULLABLE,
    
    -- ← THIS IS WHERE forma_pago IS STORED
    especificaciones LONGTEXT NULLABLE COMMENT 'JSON: forma_pago, disponibilidad, etc',
    
    es_borrador TINYINT(1) DEFAULT 1,
    estado VARCHAR(255) DEFAULT 'borrador',
    aprobada_por_contador_en TIMESTAMP NULLABLE,
    aprobada_por_aprobador_en TIMESTAMP NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULLABLE,
    
    FOREIGN KEY (asesor_id) REFERENCES users(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (tipo_cotizacion_id) REFERENCES tipos_cotizacion(id),
    INDEX idx_asesor_id (asesor_id),
    INDEX idx_cliente_id (cliente_id),
    INDEX idx_numero_cotizacion (numero_cotizacion),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## JSON Column: `especificaciones`

### Raw MySQL Storage

```json
{
  "forma_pago": [
    {
      "valor": "Contado",
      "observacion": "Descuento 5%"
    },
    {
      "valor": "Crédito 30 días",
      "observacion": "Máximo 2 millones"
    }
  ],
  "disponibilidad": [
    {
      "valor": "Bodega",
      "observacion": "En stock disponible"
    },
    {
      "valor": "Cúcuta",
      "observacion": "Disponible en 2 días"
    }
  ],
  "regimen": [
    {
      "valor": "Común",
      "observacion": ""
    }
  ],
  "se_ha_vendido": [
    {
      "valor": "Sí",
      "observacion": "Año anterior"
    }
  ],
  "ultima_venta": [
    {
      "valor": "Enero 2025",
      "observacion": "Cliente XYZ"
    }
  ],
  "flete": [
    {
      "valor": "Incluido",
      "observacion": "A nivel nacional"
    }
  ]
}
```

---

## MySQL Column Details

```sql
COLUMN NAME:    especificaciones
COLUMN TYPE:    LONGTEXT
COLLATION:      utf8mb4_unicode_ci
NULL:           YES
DEFAULT:        NULL
EXTRA:          
COMMENT:        'JSON: forma_pago, disponibilidad, etc'
```

### Data Storage Example

```sql
-- Raw data as stored in MySQL
mysql> SELECT especificaciones FROM cotizaciones WHERE id = 1;

┌─────────────────────────────────────────────────────────────────────────────────────┐
│ especificaciones                                                                    │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ {"forma_pago":[{"valor":"Contado","observacion":"Desc 5%"}],"disponibilidad":[...]}│
└─────────────────────────────────────────────────────────────────────────────────────┘

(Single row in JSON format, no line breaks in storage)
```

---

## Laravel Model Cast

```php
// app/Models/Cotizacion.php

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';
    
    protected $fillable = [
        'asesor_id',
        'cliente_id',
        'numero_cotizacion',
        'tipo_cotizacion_id',
        'tipo_venta',
        'fecha_inicio',
        'fecha_envio',
        'es_borrador',
        'estado',
        'especificaciones',  // ← Stored as JSON
        // ... otros campos
    ];

    protected $casts = [
        'especificaciones' => 'array',  // ← Converts JSON ↔ Array automatically
        'es_borrador' => 'boolean',
        'fecha_inicio' => 'datetime',
        'fecha_envio' => 'datetime',
        'estado' => 'string',
        // ...
    ];
}
```

**How the cast works**:

| Operation | Input | Storage | Output |
|-----------|-------|---------|--------|
| **Save** | `['forma_pago' => [...]]` (array) | `'{"forma_pago":[...]}'` (JSON string) | Saved to DB |
| **Retrieve** | `'{"forma_pago":[...]}'` (JSON from DB) | - | `['forma_pago' => [...]]` (array) |

---

## MySQL Queries for forma_pago

### 1. Get All forma_pago Values

```sql
-- ✅ Extract forma_pago from especificaciones
SELECT 
    id,
    numero_cotizacion,
    JSON_EXTRACT(especificaciones, '$.forma_pago') as forma_pago
FROM cotizaciones
WHERE especificaciones IS NOT NULL
LIMIT 10;
```

**Result**:
```
id | numero_cotizacion | forma_pago
---|-------------------|------------------------
1  | COT-001          | [{"valor":"Contado","observacion":"Desc 5%"}]
2  | COT-002          | [{"valor":"Crédito","observacion":"30 días"}]
```

### 2. Get Only forma_pago Values (not observaciones)

```sql
SELECT 
    id,
    numero_cotizacion,
    JSON_EXTRACT(especificaciones, '$.forma_pago[*].valor') as valores
FROM cotizaciones
WHERE especificaciones IS NOT NULL;
```

**Result**:
```
id | numero_cotizacion | valores
---|-------------------|------------------------
1  | COT-001          | ["Contado"]
2  | COT-002          | ["Crédito 30 días", "Crédito 60 días"]
```

### 3. Check if forma_pago Contains "Contado"

```sql
-- ✅ Find all cotizaciones with "Contado" payment
SELECT 
    id,
    numero_cotizacion
FROM cotizaciones
WHERE JSON_CONTAINS(
    especificaciones->'$.forma_pago[*].valor',
    '"Contado"'
);
```

**Result**:
```
id | numero_cotizacion
---|-------------------
1  | COT-001
3  | COT-003
5  | COT-005
```

### 4. Count forma_pago Items

```sql
-- ✅ Count number of payment methods per cotizacion
SELECT 
    id,
    numero_cotizacion,
    JSON_LENGTH(especificaciones->'$.forma_pago') as num_formas_pago
FROM cotizaciones
WHERE JSON_LENGTH(especificaciones->'$.forma_pago') > 0;
```

**Result**:
```
id | numero_cotizacion | num_formas_pago
---|-------------------|----------------
1  | COT-001          | 1
2  | COT-002          | 2
3  | COT-003          | 3
```

### 5. Get All Field Values (Unnest Array)

```sql
-- ✅ Expand forma_pago array into separate rows
SELECT 
    id,
    numero_cotizacion,
    JSON_EXTRACT(especificaciones, '$.forma_pago[*].valor') as valores,
    JSON_EXTRACT(especificaciones, '$.forma_pago[*].observacion') as observaciones
FROM cotizaciones
WHERE especificaciones IS NOT NULL;
```

### 6. Filter by Observation

```sql
-- ✅ Find cotizaciones where forma_pago has "Descuento"
SELECT 
    id,
    numero_cotizacion,
    especificaciones
FROM cotizaciones
WHERE JSON_CONTAINS(
    especificaciones->'$.forma_pago[*].observacion',
    '"Descuento%"'
);
```

### 7. Update forma_pago

```sql
-- ✅ Add new forma_pago to existing cotizacion
UPDATE cotizaciones
SET especificaciones = JSON_ARRAY_APPEND(
    especificaciones,
    '$.forma_pago',
    JSON_OBJECT('valor', 'Crédito 90 días', 'observacion', 'Con garantía')
)
WHERE id = 1;

-- ✅ Replace forma_pago value
UPDATE cotizaciones
SET especificaciones = JSON_SET(
    especificaciones,
    '$.forma_pago[0].valor',
    'Crédito 45 días'
)
WHERE id = 1;

-- ✅ Update all observaciones to empty
UPDATE cotizaciones
SET especificaciones = JSON_SET(
    especificaciones,
    '$.forma_pago[*].observacion',
    ''
)
WHERE id = 1;
```

### 8. Delete forma_pago

```sql
-- ✅ Remove forma_pago completely
UPDATE cotizaciones
SET especificaciones = JSON_REMOVE(especificaciones, '$.forma_pago')
WHERE id = 1;

-- ✅ Remove specific forma_pago item (e.g., second item)
UPDATE cotizaciones
SET especificaciones = JSON_REMOVE(especificaciones, '$.forma_pago[1]')
WHERE id = 1;
```

---

## Storage Size Reference

### Estimated Storage Size per forma_pago Entry

```
Single entry: {"valor":"Contado","observacion":"Desc 5%"}
Size: ~45 bytes

With 3 entries: 
Size: ~135 bytes

Full especificaciones with all 6 categories (avg 2 items each):
Size: ~500-1000 bytes per row
```

### Total Storage Impact

- **1,000 cotizaciones** with especificaciones: ~1 MB
- **10,000 cotizaciones** with especificaciones: ~10 MB
- **100,000 cotizaciones** with especificaciones: ~100 MB

---

## Performance Considerations

### Indexing Strategy

```sql
-- ✅ Add JSON index for forma_pago queries (MySQL 5.7.9+)
ALTER TABLE cotizaciones 
ADD INDEX idx_forma_pago (
    (JSON_EXTRACT(especificaciones, '$.forma_pago[*].valor'))
);

-- ✅ Add index for existence check
ALTER TABLE cotizaciones 
ADD INDEX idx_especificaciones 
((JSON_LENGTH(especificaciones)));
```

### Query Performance

```sql
-- ⚠️ SLOW - Full table scan (no index)
SELECT * FROM cotizaciones 
WHERE JSON_CONTAINS(especificaciones->'$.forma_pago[*].valor', '"Contado"');

-- ✅ FAST - With index (after adding index)
-- Same query with JSON index will be much faster
```

---

## Data Integrity

### Validation at Database Level

```sql
-- ✅ Check if especificaciones is valid JSON
SELECT id, numero_cotizacion
FROM cotizaciones
WHERE especificaciones IS NOT NULL
AND JSON_VALID(especificaciones) = 0;

-- ✅ Check for missing forma_pago key
SELECT id, numero_cotizacion
FROM cotizaciones
WHERE especificaciones IS NOT NULL
AND JSON_CONTAINS(especificaciones, 'null', '$.forma_pago');

-- ✅ Check for empty forma_pago arrays
SELECT id, numero_cotizacion
FROM cotizaciones
WHERE especificaciones IS NOT NULL
AND JSON_LENGTH(especificaciones->'$.forma_pago') = 0;
```

---

## Backup and Recovery

### Export especificaciones

```sql
-- ✅ Export to JSON file
SELECT 
    id,
    numero_cotizacion,
    especificaciones
FROM cotizaciones
WHERE especificaciones IS NOT NULL
INTO OUTFILE '/tmp/cotizaciones_specs.json';
```

### Restore from JSON

```sql
-- ✅ Restore from exported data
LOAD DATA INFILE '/tmp/cotizaciones_specs.json'
INTO TABLE cotizaciones
(id, numero_cotizacion, especificaciones);
```

---

## Migration History

### Original Creation

The `especificaciones` column has been in place and is used by:

1. **Cotizacion Model** → Cast as 'array'
2. **Frontend JavaScript** → Saves JSON
3. **Blade Templates** → Displays from JSON
4. **Controllers** → Processes array
5. **Tests** → Validates structure

### No Separate Migration Needed

The `especificaciones` column already exists and handles all forma_pago data. No additional columns required.

---

## Summary Table

| Aspect | Detail |
|--------|--------|
| **Column Name** | `especificaciones` |
| **Table** | `cotizaciones` |
| **MySQL Type** | `LONGTEXT` |
| **Content Type** | JSON |
| **Laravel Cast** | `array` |
| **forma_pago Location** | `$.forma_pago` (JSON path) |
| **Storage Format** | `[{"valor":"...", "observacion":"..."}]` |
| **Max Items** | Unlimited (JSON limit ~1GB) |
| **Encoding** | UTF-8 JSON |
| **Nullable** | YES |
| **Default** | NULL |

