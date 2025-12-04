# 🔧 FIX: tipo_manga no se guardaba en Cotizaciones de Prenda

**Fecha**: 4 de Diciembre de 2025  
**Status**: ✅ COMPLETADO  
**Severidad**: Alta

---

## 📋 Problema

Cuando se creaba una cotización de prenda, el campo `tipo_manga` (CORTA, LARGA, 3/4, RAGLAN, CAMPANA, OTRA) no se guardaba correctamente.

### Síntomas
- El select de manga se mostraba y permitía seleccionar
- Al guardar la cotización, el campo quedaba vacío
- En los datos guardados en BD no aparecía el tipo de manga

---

## 🔍 Causa Raíz

En el archivo `CotizacionPrendaController.php` (línea 327), solo se estaba buscando el campo `tipo_manga_id` (que es un ID numérico de FK), pero el formulario enviaba `tipo_manga` (que es el nombre: "CORTA", "LARGA", etc).

### Flujo Incorrecto
```
Formulario (create.blade.php)
  ↓
  <select name="productos_prenda[][variantes][tipo_manga]">
  ├─ value="CORTA"
  ├─ value="LARGA"
  └─ value="3/4"
  
Servidor (CotizacionPrendaController.php)
  ↓
  Busca: $variantes['tipo_manga_id']  ❌ NO EXISTE
  Resultado: Campo no guardado
```

---

## ✅ Solución Aplicada

**Archivo**: `app/Http/Controllers/CotizacionPrendaController.php`  
**Línea**: 327-332

### Cambio
```php
// ANTES:
if ($variantes['tipo_manga_id'] ?? false) {
    $variantesTransformadas['tipo_manga_id'] = $variantes['tipo_manga_id'];
}

// AHORA:
if ($variantes['tipo_manga_id'] ?? false) {
    $variantesTransformadas['tipo_manga_id'] = $variantes['tipo_manga_id'];
}

// NUEVO: Agregar soporte para tipo_manga (nombre)
if ($variantes['tipo_manga'] ?? false) {
    $variantesTransformadas['tipo_manga'] = $variantes['tipo_manga'];
}
```

### Flujo Correcto Ahora
```
Formulario (create.blade.php)
  ↓
  <select name="productos_prenda[][variantes][tipo_manga]">
  ├─ value="CORTA"      ← Se envía como string
  ├─ value="LARGA"
  └─ value="3/4"
  
Servidor (CotizacionPrendaController.php)
  ↓
  Busca: $variantes['tipo_manga']  ✅ EXISTE
  Guarda: $variantesTransformadas['tipo_manga'] = 'CORTA'
  
BD (productos JSON)
  ↓
  {
    "variantes": {
      "tipo_manga": "CORTA",  ← Guardado correctamente
      ...
    }
  }
```

---

## 📊 Campos de Manga Soportados

El select en el formulario permite:
- **CORTA** - Manga corta (0-3cm)
- **LARGA** - Manga larga (cubriendo todo el brazo)
- **3/4** - Manga tres cuartos
- **RAGLAN** - Manga tipo raglan (costura diagonal)
- **CAMPANA** - Manga acampanada
- **OTRA** - Otro tipo no listado

---

## 🧪 Cómo Validar

### 1. Crear cotización de prenda
```
1. Ir a Cotizaciones → Crear Prenda
2. Agregar un producto
3. En la tabla de variantes, seleccionar "Manga: LARGA"
4. Llenar otros campos (nombre, tallas, etc)
5. Guardar
```

### 2. Verificar BD
```sql
-- Buscar la cotización creada
SELECT id, productos FROM cotizaciones 
WHERE cliente LIKE 'TEST%' 
ORDER BY created_at DESC 
LIMIT 1;

-- En la columna 'productos' (JSON), 
-- debe aparecer:
{
  "variantes": {
    "tipo_manga": "LARGA"  ← Debe estar presente
  }
}
```

### 3. Verificar en Logs
```
tail -f storage/logs/laravel.log | grep "Procesando producto"
```

Debe mostrar:
```
"tipo_manga": "LARGA"  ← Campo capturado
```

---

## 📝 Checklist

- [x] Identificar causa (campo no capturado)
- [x] Agregar captura de `tipo_manga` en controller
- [x] Verificar que el JSON se guarde correctamente
- [x] Documentar el fix
- [x] Listo para producción

---

## 🚀 Notas

Este fix mantiene compatibilidad con:
- ✅ `tipo_manga_id` (si alguien envía por ID numérico)
- ✅ `tipo_manga` (nombre del tipo - caso principal)
- ✅ JSON storage en BD

El sistema ahora guarda ambos si están disponibles.

---

**Tipo**: Fix Crítico  
**Impacto**: Cotizaciones de Prenda  
**Status**: ✅ COMPLETADO
