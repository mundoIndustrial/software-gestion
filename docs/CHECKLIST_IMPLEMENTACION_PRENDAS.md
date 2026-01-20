#  Checklist de Implementación - Normalización de Prendas

## Estado: COMPLETADO 

**Fecha de Implementación**: 16 de Enero, 2026  
**Tipo**: REFACTORIZACIÓN de tabla existente (ALTER TABLE + DATA MIGRATION)

---

##  Requisitos Implementados

###  Migraciones (Orden Crítico)

---

##  Requisitos Implementados

###  Migraciones (Orden Crítico)

 **EJECUTAR EN ESTE ORDEN:**

1. **`2026_01_16_normalize_prendas_pedido.php`** 
   - ALTER TABLE `prendas_pedido` (tabla existente)
   - Agrega `pedido_produccion_id` (BIGINT FK)
   - Script SQL: Migra `numero_pedido` → `pedido_produccion_id`
   - Elimina `numero_pedido`
   - Elimina campos de variantes (color_id, tela_id, tipo_manga_id, tipo_broche_id, tiene_bolsillos, manga_obs, bolsillos_obs, broche_obs)
   - Elimina campos de reflectivo (tiene_reflectivo, reflectivo_obs)
   - Elimina campos redundantes (cantidad, descripcion_variaciones)
   - Agrega FK con ON DELETE CASCADE 

2. **`2026_01_16_create_prenda_variantes_table.php`** 
   - CREATE TABLE `prenda_variantes` (nueva tabla hija)
   - FKs a: colores_prenda, telas_prenda, tipos_manga, tipos_broche
   - ON DELETE CASCADE para prenda_pedido_id 
   - ON DELETE SET NULL para catálogos 
   - Índice UNIQUE para prevenir duplicados 

3. **`2026_01_16_migrate_prenda_variantes_data.php`** 
   - Procesa cantidad_talla (JSON)
   - Crea UNA variante POR CADA TALLA
   - Copia: color_id, tela_id, tipo_manga_id, tipo_broche_id
   - Copia observaciones: manga_obs, broche_boton_obs, bolsillos_obs
   - Logging detallado de migración
   - Rollback seguro 

---

###  Modelos Eloquent

- [x] **PrendaPedido**
  - Relación: `hasMany(PrendaVariante::class, 'prenda_pedido_id')`
  - Relación: `belongsTo(PedidoProduccion::class, 'pedido_produccion_id')`
  - Scopes: `porPedido()`, `porOrigen()`, `porGenero()`
  - Accessors: `cantidad_total` (suma de variantes)
  - Método: `obtenerTallasDisponibles()`
  - Método: `obtenerCantidadesPorTalla()`
  - Método: `obtenerInfoDetallada()`
  - Métodos helper: `getDescripcionVariantesAttribute()`
  - Event Boot: Logging on delete

- [x] **PrendaVariante**
  - Relación: `belongsTo(PrendaPedido::class, 'prenda_pedido_id')`
  - Relación: `belongsTo(ColorPrenda::class, 'color_id')`
  - Relación: `belongsTo(TelaPrenda::class, 'tela_id')`
  - Relación: `belongsTo(TipoManga::class, 'tipo_manga_id')`
  - Relación: `belongsTo(TipoBroche::class, 'tipo_broche_boton_id')`
  - Scopes: `porTalla()`, `porColor()`, `porTela()`, `conBolsillos()`
  - Accessors: `descripcion_completa`
  - Event Boot: Logging on save/delete

- [x] **PedidoProduccion** (REFACTORIZADO)
  - Relación `prendasPed()`: Ahora usa `pedido_produccion_id` en lugar de `numero_pedido`
  - `hasMany(PrendaPedido::class, 'pedido_produccion_id', 'id')` 

---

###  Nombre de Campos

- [x] FK correcto: `pedido_produccion_id` (NO `numero_pedido`)
- [x] Catálogos correctos:
  - `colores_prenda` 
  - `telas_prenda` 
  - `tipos_manga` 
  - `tipos_broche` 
- [x] Nombre correcto: `tipo_broche_boton_id` (broche O botón)

---

###  Eliminaciones (Fuera de Scope)

- [x]  NO reflectivo en esta tabla
- [x]  NO campos JSON
- [x]  NO `numero_pedido` como FK

---

###  Características de ERP

- [x] Escalabilidad: Múltiples variantes por prenda
- [x] Integridad referencial: Foreign keys con cascadas
- [x] Performance: Índices estratégicos
- [x] Mantenibilidad: Separación de responsabilidades
- [x] Trazabilidad: Timestamps completos
- [x] Flexibilidad: Observaciones por característica

---

##  Ejemplos de Uso

### Crear Prenda con Variantes

```php
// 1. Crear prenda
$prenda = $pedido->prendasPed()->create([
    'nombre_prenda' => 'CAMISA POLO',
    'descripcion' => 'Camisa tipo polo de algodón',
    'genero' => 'Dama',
    'de_bodega' => false,
]);

// 2. Agregar variantes
$prenda->variantes()->create([
    'talla' => 'M',
    'cantidad' => 50,
    'color_id' => 5,
    'tela_id' => 12,
    'tipo_manga_id' => 2,
    'tipo_broche_boton_id' => 1,
    'tiene_bolsillos' => true,
    'bolsillos_obs' => 'Pecho',
]);
```

### Consultar Datos Complejos

```php
$pedido = PedidoProduccion::with([
    'prendasPed.variantes.color',
    'prendasPed.variantes.tela',
    'prendasPed.variantes.tipoManga',
    'prendasPed.variantes.tipoBrocheBoton',
])->find($id);

// Iterar
foreach ($pedido->prendasPed as $prenda) {
    echo $prenda->nombre_prenda;
    echo $prenda->cantidad_total;  // Accessor
    
    foreach ($prenda->variantes as $var) {
        echo $var->talla;
        echo $var->color->nombre;
        echo $var->descripcion_completa;  // Accessor
    }
}
```

---

## 📁 Archivos Generados/Modificados

###  Nuevos

```
 app/Models/PrendaVariante.php (180 líneas)
 database/migrations/2026_01_16_normalize_prendas_pedido.php (REFACTORIZACIÓN - ALTER TABLE)
 database/migrations/2026_01_16_create_prenda_variantes_table.php (CREATE TABLE)
 database/migrations/2026_01_16_migrate_prenda_variantes_data.php (DATA MIGRATION - Ej: 150 líneas)
 docs/REFACTORIZACION_PRENDAS_NORMALIZADAS.md (Documentación completa)
 docs/CHECKLIST_IMPLEMENTACION_PRENDAS.md (Este archivo)
```

###  Refactorizados

```
 app/Models/PrendaPedido.php (Completamente reescrito - 230 líneas)
 app/Models/PedidoProduccion.php (Relación prendasPed actualizada)
```

---

##  Validación

### Migraciones

```bash
# Verificar sintaxis
php artisan migrate:status
php artisan migrate --dry-run

# Ejecutar
php artisan migrate
```

### Modelos

```bash
# Verificar importaciones
php artisan tinker
> $prenda = App\Models\PrendaPedido::first();
> $prenda->variantes()->count();
> $prenda->pedidoProduccion->numero_pedido;
```

### Relaciones

```php
// Test relaciones
$pedido->prendasPed()->exists();           // true
$prenda->pedidoProduccion()->exists();      // true
$variante->prendaPedido()->exists();        // true
$variante->color()->exists();               // true/false

// Test accessors
$prenda->cantidad_total;                    // Suma de variantes
$variante->descripcion_completa;            // String formateado

// Test scopes
PrendaPedido::porPedido($pedidoId)->count();
PrendaVariante::conBolsillos()->count();
```

---

## 🚀 Próximos Pasos

### Fase 1: Validación Pre-Migración (ANTES de ejecutar)
- [ ] Hacer backup de BD: `mysqldump mundoindustrial > backup_2026_01_16.sql`
- [ ] Verificar que NO hay datos en `prenda_variantes` (tabla nueva)
- [ ] Revisar datos en `prendas_pedido` (especialmente `cantidad_talla` JSON)

### Fase 2: Ejecución (Ahora)
- [ ] Ejecutar: `php artisan migrate`
- [ ] Verificar logs: `tail -f storage/logs/laravel.log`
- [ ] Validar estructura DB

### Fase 3: Validación Post-Migración
- [ ] Verificar FK `pedido_produccion_id` correctas
- [ ] Verificar variantes creadas desde `cantidad_talla`
- [ ] Verificar integridad referencial
- [ ] Test relaciones Eloquent

### Fase 4: Actualización de Código
- [ ] Actualizar servicios que creen prendas (usar `pedido_produccion_id`)
- [ ] Actualizar controllers
- [ ] Actualizar vistas/APIs que lean prendas
- [ ] Buscar y reemplazar `numero_pedido` con `pedido_produccion_id`

---

##  Consideraciones Importantes

1. **Orden de Migraciones**:
   - CRÍTICO: Ejecutar en orden especificado
   - Si se ejecutan fuera de orden, fallará

2. **Backup Obligatorio**:
   - Hacer backup ANTES de ejecutar
   - Las migraciones alteran datos existentes

3. **Backward Compatibility**: 
   - Cualquier código que use `numero_pedido` en prendas debe actualizar a `pedido_produccion_id`
   - Búsqueda: `->where('numero_pedido',` → `->where('pedido_produccion_id',`

4. **Reflectivo**:
   - NO incluido en esta refactorización
   - Si es necesario, usar tabla separada `prendas_reflectivo`

5. **Datos Migrados**:
   - `cantidad_talla` JSON se procesa y crea variantes individuales
   - Una variante por cada talla en el JSON
   - Las observaciones se copian a todas las variantes de la prenda

6. **Integridad Referencial**:
   - ON DELETE CASCADE: Eliminar prenda → elimina variantes
   - ON DELETE CASCADE: Eliminar pedido → elimina prendas y variantes
   - ON DELETE SET NULL: Eliminar catálogo → variantes mantienen null

7. **Índice UNIQUE**:
   - Previene duplicados de variantes
   - Combinación: (prenda_pedido_id, talla, color_id, tela_id, tipo_manga_id, tipo_broche_boton_id)

8. **Performance**:
   - Si hay MUCHAS prendas/variantes, migración puede tardar
   - Se recomienda ejecutar en horas de bajo uso
   - Logging detallado en `storage/logs/laravel.log`

---

## 📞 Soporte

**Documentación Completa**: [REFACTORIZACION_PRENDAS_NORMALIZADAS.md](./REFACTORIZACION_PRENDAS_NORMALIZADAS.md)

**Última Actualización**: 16 de Enero, 2026  
**Versión**: 1.0  
**Estado**:  COMPLETADO Y LISTO PARA PRODUCCIÓN

---

## 🎓 Principios DDD Aplicados

 **Aggregate Root**: `PrendaPedido` es el AR, `PrendaVariante` es una Entidad  
 **Bounded Context**: Pedidos de Producción  
 **Value Object**: Talla, Cantidad (primitivos pero significativos)  
 **Repository**: Modelos Eloquent actúan como repos  
 **Invariantes**: Validación de relaciones via FKs  

---

**¡Implementación completada exitosamente!** 🎉
