# 📋 MIGRACIONES DE SOPORTE NECESARIAS

Para que las migraciones de prendas funcionen correctamente, necesitas ejecutar primero las siguientes migraciones de soporte:

## ✅ ORDEN DE EJECUCIÓN

### 1. Tablas Base (Sin dependencias)
```bash
php artisan migrate --path=database/migrations/2025_12_10_create_tipo_prendas_table.php
php artisan migrate --path=database/migrations/2025_12_10_create_genero_prendas_table.php
```

### 2. Tablas de Atributos (Si no existen)
```bash
# Si no existen, crear:
php artisan migrate --path=database/migrations/XXXX_XX_XX_create_tipo_mangas_table.php
php artisan migrate --path=database/migrations/XXXX_XX_XX_create_tipo_broches_table.php
php artisan migrate --path=database/migrations/XXXX_XX_XX_create_colores_prenda_table.php
php artisan migrate --path=database/migrations/XXXX_XX_XX_create_telas_prenda_table.php
```

### 3. Tablas de Prendas (Después de las dependencias)
```bash
php artisan migrate --path=database/migrations/2025_12_10_create_prendas_cot_table.php
php artisan migrate --path=database/migrations/2025_12_10_create_prenda_variantes_cot_table.php
php artisan migrate --path=database/migrations/2025_12_10_create_prenda_tallas_cot_table.php
php artisan migrate --path=database/migrations/2025_12_10_create_prenda_fotos_cot_table.php
php artisan migrate --path=database/migrations/2025_12_10_create_prenda_telas_cot_table.php
```

---

## 📝 TABLAS DE SOPORTE REQUERIDAS

Si las siguientes tablas NO existen en tu BD, debes crearlas:

### 1. tipo_mangas
```sql
CREATE TABLE tipo_mangas (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) UNIQUE NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 2. tipo_broches
```sql
CREATE TABLE tipo_broches (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) UNIQUE NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 3. colores_prenda
```sql
CREATE TABLE colores_prenda (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) UNIQUE NOT NULL,
    codigo VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 4. telas_prenda
```sql
CREATE TABLE telas_prenda (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) UNIQUE NOT NULL,
    referencia VARCHAR(255),
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## ✅ VERIFICAR TABLAS EXISTENTES

Para verificar qué tablas ya existen:

```bash
php artisan tinker
>>> DB::select('SHOW TABLES')
```

O en MySQL directamente:
```sql
SHOW TABLES LIKE 'tipo_%';
SHOW TABLES LIKE 'colores_%';
SHOW TABLES LIKE 'telas_%';
```

---

## 🔄 FLUJO COMPLETO

Si todas las tablas de soporte existen, ejecuta en este orden:

```bash
# 1. Tablas base
php artisan migrate --path=database/migrations/2025_12_10_create_tipo_prendas_table.php
php artisan migrate --path=database/migrations/2025_12_10_create_genero_prendas_table.php

# 2. Tablas de prendas
php artisan migrate --path=database/migrations/2025_12_10_create_prendas_cot_table.php
php artisan migrate --path=database/migrations/2025_12_10_create_prenda_variantes_cot_table.php
php artisan migrate --path=database/migrations/2025_12_10_create_prenda_tallas_cot_table.php
php artisan migrate --path=database/migrations/2025_12_10_create_prenda_fotos_cot_table.php
php artisan migrate --path=database/migrations/2025_12_10_create_prenda_telas_cot_table.php
```

---

## 🚨 SI ALGO FALLA

Si una migración falla porque una tabla ya existe:

```bash
# Rollback de esa migración
php artisan migrate:rollback --path=database/migrations/2025_12_10_create_prendas_cot_table.php

# Luego ejecutarla de nuevo
php artisan migrate --path=database/migrations/2025_12_10_create_prendas_cot_table.php
```

---

## ✨ ESTADO ACTUAL

✅ Creadas:
- tipo_prendas
- genero_prendas

❓ Necesitas verificar si existen:
- tipo_mangas
- tipo_broches
- colores_prenda
- telas_prenda

⏳ Pendientes de ejecutar:
- prendas_cot
- prenda_variantes_cot
- prenda_tallas_cot
- prenda_fotos_cot
- prenda_telas_cot

