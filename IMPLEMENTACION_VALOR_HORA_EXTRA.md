# Implementación: Columna VALOR en Tabla de Horas Extras

## Descripción General
Se ha implementado una nueva funcionalidad para registrar y guardar el valor de la hora extra para cada persona en el módulo de Asistencia Personal.

## Cambios Realizados

### 1. Base de Datos

#### Migración Creada
**Archivo:** `database/migrations/2026_01_15_create_valor_hora_extra_table.php`

```php
- Crea tabla `valor_hora_extra`
- Campos:
  - id (primary key)
  - codigo_persona (unique, foreign key -> personal.codigo_persona)
  - valor (decimal 10,2)
  - timestamps
```

### 2. Modelos

#### Modelo: ValorHoraExtra
**Archivo:** `app/Models/ValorHoraExtra.php`

```php
- Table: valor_hora_extra
- Fillable: codigo_persona, valor
- Relación: belongsTo(Personal)
```

#### Actualización: Modelo Personal
**Archivo:** `app/Models/Personal.php`

```php
- Se agregó relación: valorHoraExtra()
- Tipo: hasOne(ValorHoraExtra)
```

### 3. Controlador API

#### Controlador Creado
**Archivo:** `app/Http/Controllers/Api/ValorHoraExtraController.php`

**Métodos:**
- `obtener($codigoPersona)` - GET - Obtiene el valor actual de una persona
- `guardar(Request $request)` - POST - Guarda o actualiza el valor

**Respuestas:**
```json
// GET - Obtener
{
    "success": true,
    "valor": 15000
}

// POST - Guardar
{
    "success": true,
    "message": "Valor guardado exitosamente",
    "data": {
        "id": 1,
        "codigo_persona": "001",
        "valor": 15000
    }
}
```

### 4. Rutas

#### Archivo: `routes/web.php`

Se agregaron 2 nuevas rutas API (autenticadas):

```php
Route::middleware(['auth', 'verified'])->prefix('api')->name('api.')->group(function () {
    Route::get('valor-hora-extra/{codigoPersona}', [App\Http\Controllers\Api\ValorHoraExtraController::class, 'obtener'])
        ->name('valor-hora-extra.obtener');
    Route::post('valor-hora-extra/guardar', [App\Http\Controllers\Api\ValorHoraExtraController::class, 'guardar'])
        ->name('valor-hora-extra.guardar');
});
```

### 5. Frontend - JavaScript

#### Archivo: `public/js/asistencia-personal/total-horas-extras.js`

**Cambios en tabla:**
1. Se agregó columna "VALOR" al encabezado (después de TOTAL)
2. Se agregó celda con input de número y botón guardar para cada persona

**Funciones Globales Agregadas:**

```javascript
/**
 * cargarValorActual(codigoPersona, inputElement)
 * - Carga el valor actual desde la API
 * - Se llama automáticamente al renderizar la tabla
 */

/**
 * guardarValorHoraExtra(codigoPersona, valor, btnElement)
 * - Envía el valor a guardar a la API
 * - Muestra feedback visual: ⏳ -> ✓ (si éxito) o error
 * - Valida que el valor sea numérico
 */
```

## Interfaz de Usuario

### Tabla de Total Horas Extras

```
┌────┬─────────┬──────────┬────┬────┬────┬────────┬──────────────┐
│ ID │ Nombre  │ Novedades│ 16 │ 17 │ 18 │ TOTAL  │ VALOR        │
├────┼─────────┼──────────┼────┼────┼────┼────────┼──────────────┤
│ 1  │ Juan    │ Sin novedades│ 2  │ 3  │ -  │ 5      │[15000.00]💾 │
├────┼─────────┼──────────┼────┼────┼────┼────────┼──────────────┤
│ 2  │ María   │ Ver Novedades│ 1  │ -  │ 2  │ 3      │[10000.00]💾 │
└────┴─────────┴──────────┴────┴────┴────┴────────┴──────────────┘
```

**Características:**
- Input editable para cada persona
- Botón guardar (💾) con feedback visual
- Carga automática del valor guardado
- Validación de valores numéricos

## Flujo de Uso

1. **Ver Reporte** → Click en "Ver" de un reporte guardado
2. **Abrir Modal** → Se abre el modal de detalles del reporte
3. **Click en "Total Horas Extras"** → Se carga la tabla con personas y horas extras
4. **Ingresar Valor** → Usuario digita el valor de la hora extra en el input
5. **Click en Guardar** → Se envía a la API y se guarda en BD
6. **Feedback** → Botón muestra ✓ (éxito) por 2 segundos

## Validaciones

### Frontend
- Valor debe ser numérico
- Valor debe ser >= 0
- Mensaje de error si falla

### Backend
- Validación de codigo_persona (debe ser string)
- Validación de valor (numeric, min:0)
- Verificación que la persona existe
- Manejo de excepciones

## Estructura de Base de Datos

```sql
CREATE TABLE valor_hora_extra (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    codigo_persona VARCHAR(255) UNIQUE NOT NULL,
    valor DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (codigo_persona) REFERENCES personal(codigo_persona) ON DELETE CASCADE
);
```

## Próximos Pasos (Opcional)

1. Ejecutar migración: `php artisan migrate`
2. Probar en navegador: 
   - Ir a Asistencia Personal
   - Abrir un reporte
   - Click en "Total Horas Extras"
   - Ingresar valores y guardar

## Notas Técnicas

- La tabla se actualiza en tiempo real sin recargar la página
- Los valores se persisten en base de datos
- Las rutas requieren autenticación (middleware 'auth', 'verified')
- Se utiliza CSRF token para la seguridad en POST
- El modelo Personal tiene relación 1:1 con ValorHoraExtra

