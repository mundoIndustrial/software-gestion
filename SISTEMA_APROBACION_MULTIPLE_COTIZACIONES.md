# Sistema de Aprobación Múltiple de Cotizaciones

## Resumen

Se implementó un sistema de aprobación múltiple donde **TODOS** los usuarios con rol `aprobador_cotizaciones` deben aprobar una cotización antes de que cambie al estado final.

## Estado Actual del Flujo

### Antes de la Implementación
- Cuando un aprobador aprobaba → Estado cambiaba inmediatamente a `APROBADA_POR_APROBADOR`
- No se rastreaban aprobaciones individuales

### Después de la Implementación
1. Cotización llega con estado `APROBADA_CONTADOR`
2. Cada aprobador registra su aprobación individualmente
3. Solo cuando **TODOS** los aprobadores aprueban → Estado cambia a `APROBADA_POR_APROBADOR`
4. Mientras tanto, permanece en `APROBADA_CONTADOR`

## Archivos Creados

### 1. Migración: `2025_12_18_150600_create_cotizacion_aprobaciones_table.php`
```php
- Tabla: cotizacion_aprobaciones
- Campos:
  * id
  * cotizacion_id (FK a cotizaciones)
  * usuario_id (FK a users)
  * fecha_aprobacion
  * comentario (nullable)
  * timestamps
- Constraint único: (cotizacion_id, usuario_id) - Evita aprobaciones duplicadas
```

### 2. Modelo: `app/Models/CotizacionAprobacion.php`
- Relación con Cotizacion
- Relación con User
- Campos fillable y casts configurados

## Archivos Modificados

### 1. `app/Models/Cotizacion.php`
**Agregado:**
```php
public function aprobaciones()
{
    return $this->hasMany(CotizacionAprobacion::class);
}
```

### 2. `app/Http/Controllers/CotizacionEstadoController.php`
**Método `aprobarAprobador()` modificado:**

**Lógica implementada:**
1. Verifica que el usuario no haya aprobado ya (evita duplicados)
2. Registra la aprobación del usuario actual
3. Cuenta total de aprobadores con rol `aprobador_cotizaciones`
4. Cuenta aprobaciones actuales de la cotización
5. **Si todos aprobaron:** Cambia estado a `APROBADA_POR_APROBADOR`
6. **Si faltan aprobaciones:** Mantiene estado `APROBADA_CONTADOR`

**Respuesta JSON incluye:**
```json
{
    "success": true,
    "message": "Mensaje dinámico según estado",
    "cotizacion": {...},
    "aprobaciones_actuales": 2,
    "total_aprobadores": 3,
    "aprobacion_completa": false
}
```

### 3. `routes/web.php`
**Ruta `/cotizaciones/pendientes` modificada:**
```php
- Carga relación: ->with(['aprobaciones.usuario'])
- Pasa variable: $totalAprobadores
```

### 4. `resources/views/cotizaciones/pendientes.blade.php`

**Cambios en la tabla:**
1. **Nueva columna "Aprobaciones"** que muestra:
   - Contador: `2/3` (aprobaciones actuales / total)
   - Barra de progreso visual
   - Checkmark verde ✓ si el usuario actual ya aprobó

2. **Mensajes de éxito mejorados:**
   - Si todos aprobaron: "¡Aprobación Completa!" (verde)
   - Si faltan aprobaciones: "Aprobación Registrada" con contador (azul)

## Cómo Funciona

### Ejemplo con 3 Aprobadores

**Usuario 1 aprueba:**
- Se registra aprobación en BD
- Mensaje: "Tu aprobación ha sido registrada. Faltan 2 aprobación(es) más."
- Estado: `APROBADA_CONTADOR` (sin cambios)
- Vista muestra: 1/3 con barra al 33%

**Usuario 2 aprueba:**
- Se registra aprobación en BD
- Mensaje: "Tu aprobación ha sido registrada. Faltan 1 aprobación(es) más."
- Estado: `APROBADA_CONTADOR` (sin cambios)
- Vista muestra: 2/3 con barra al 66%

**Usuario 3 aprueba:**
- Se registra aprobación en BD
- Mensaje: "Cotización aprobada completamente. Todos los aprobadores han dado su visto bueno."
- Estado: Cambia a `APROBADA_POR_APROBADOR` ✅
- Vista muestra: 3/3 con barra verde al 100%

## Protecciones Implementadas

1. **Constraint único en BD:** Evita que un usuario apruebe dos veces
2. **Validación en controlador:** Verifica si el usuario ya aprobó antes de registrar
3. **Transacciones DB:** Garantiza consistencia de datos
4. **Logs detallados:** Rastrea cada aprobación y cambio de estado

## Migración de Base de Datos

Para aplicar los cambios, ejecutar:
```bash
php artisan migrate
```

Esto creará la tabla `cotizacion_aprobaciones`.

## Notas Importantes

- El sistema es **dinámico**: Si se agregan/eliminan usuarios con rol `aprobador_cotizaciones`, el total se ajusta automáticamente
- Las cotizaciones existentes en estado `APROBADA_CONTADOR` requerirán aprobación de todos los usuarios actuales
- Si un usuario ya aprobó e intenta aprobar de nuevo, recibe mensaje de error
- La vista muestra claramente quién ha aprobado mediante el checkmark verde
