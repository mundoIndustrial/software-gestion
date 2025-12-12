# Análisis de Campos - Tabla `prenda_variantes_cot`

## 📊 Comparación: Modelo vs Base de Datos

### Campos en el Modelo `PrendaVarianteCot` (fillable)
```
1. prenda_cot_id
2. tipo_prenda
3. es_jean_pantalon
4. tipo_jean_pantalon
5. genero_id
6. color
7. tipo_manga_id
8. tiene_bolsillos
9. obs_bolsillos
10. aplica_manga
11. tipo_manga
12. obs_manga
13. aplica_broche
14. tipo_broche_id
15. obs_broche
16. tiene_reflectivo
17. obs_reflectivo
18. descripcion_adicional
19. telas_multiples
```

### Campos Reales en la BD (verificados)
```
1. id (PK)
2. prenda_cot_id
3. tipo_prenda
4. es_jean_pantalon
5. tipo_jean_pantalon
6. genero_id
7. color
8. tipo_manga_id
9. tipo_broche_id
10. obs_broche
11. tiene_bolsillos
12. obs_bolsillos
13. aplica_manga
14. tipo_manga
15. obs_manga
16. aplica_broche
17. tiene_reflectivo
18. obs_reflectivo
19. descripcion_adicional
20. created_at
21. updated_at
```

## ✅ Campos que EXISTEN en ambos

| Campo | Modelo | BD | Estado |
|-------|--------|----|----|
| prenda_cot_id | ✅ | ✅ | ✓ OK |
| tipo_prenda | ✅ | ✅ | ✓ OK |
| es_jean_pantalon | ✅ | ✅ | ✓ OK |
| tipo_jean_pantalon | ✅ | ✅ | ✓ OK |
| genero_id | ✅ | ✅ | ✓ OK |
| color | ✅ | ✅ | ✓ OK |
| tipo_manga_id | ✅ | ✅ | ✓ OK |
| tipo_broche_id | ✅ | ✅ | ✓ OK |
| obs_broche | ✅ | ✅ | ✓ OK |
| tiene_bolsillos | ✅ | ✅ | ✓ OK |
| obs_bolsillos | ✅ | ✅ | ✓ OK |
| aplica_manga | ✅ | ✅ | ✓ OK |
| tipo_manga | ✅ | ✅ | ✓ OK |
| obs_manga | ✅ | ✅ | ✓ OK |
| aplica_broche | ✅ | ✅ | ✓ OK |
| tiene_reflectivo | ✅ | ✅ | ✓ OK |
| obs_reflectivo | ✅ | ✅ | ✓ OK |
| descripcion_adicional | ✅ | ✅ | ✓ OK |

## ❌ Campos que FALTAN en la BD

### Campo: `telas_multiples`
- **Estado**: En modelo pero NO en BD
- **Tipo esperado**: JSON
- **Uso**: Almacenar múltiples telas con sus propiedades (color, referencia, etc.)
- **Ubicación en código**: `CotizacionPrendaService.php` línea 221
- **Impacto**: ⚠️ CRÍTICO - Se intenta guardar pero la columna no existe

## 🔧 Acciones Necesarias

### 1. Crear Migración para agregar campo `telas_multiples`
```php
Schema::table('prenda_variantes_cot', function (Blueprint $table) {
    $table->json('telas_multiples')->nullable()->after('descripcion_adicional');
});
```

### 2. Verificar si hay más campos faltantes
- Revisar si hay otros campos que se usan en controladores pero no están en la BD

## 📝 Datos Actuales
- **Total de registros**: 13
- **Campos con datos**: Todos los campos existentes tienen datos
- **Campos vacíos**: obs_broche, obs_bolsillos, obs_manga, obs_reflectivo (algunos registros)

## 🎯 Resumen
- ✅ 18 campos coinciden entre modelo y BD
- ❌ 1 campo FALTA en la BD: `telas_multiples`
- ⚠️ Este campo es CRÍTICO porque se está usando en `CotizacionPrendaService`
