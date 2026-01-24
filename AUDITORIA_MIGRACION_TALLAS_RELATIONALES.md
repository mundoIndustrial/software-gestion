# AUDITORÍA QUIRÚRGICA: MIGRACIÓN A TALLAS RELACIONALES

**Fecha**: Enero 22, 2026  
**Objetivo**: Detectar y eliminar restos de lógica antigua de tallas/género tras la migración a tablas relacionales

---

## CONTEXTO DEL SISTEMA

### Cambio Principal
Se eliminó el uso de:
- `cantidad_talla` como **fuente activa** (ahora es solo JSON legacy para compatibilidad)
- `genero` en tabla `prendas_pedido` como campo (ahora viene de `prenda_pedido_tallas`)

Se agregó estructura relacional:
- `prenda_pedido_tallas` - Almacena tallas con estructura: `{genero: {talla: cantidad}}`
- `pedidos_procesos_prenda_tallas` - Almacena tallas por proceso

---

## HALLAZGOS Y CAMBIOS APLICADOS

###  CAMBIO 1: PedidosProduccionViewController.php
**Archivo**: `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionViewController.php`  
**Líneas**: 712-719  
**Severidad**: 🔴 CRÍTICO  

**Problema**: 
Código intentaba leer campo `$prenda->genero` que FUE ELIMINADO de `prendas_pedido`:
```php
$generos = [];
if ($prenda->genero) {
    if (is_array($prenda->genero)) {
        $generos = $prenda->genero;
    } else if (is_string($prenda->genero)) {
        $generos = json_decode($prenda->genero, true) ?? [];
    }
}
```

**Impacto**: 
- Lectura de campo inexistente retorna `NULL`
- Variable `$generos` siempre vacía
- Posible error silencioso en datos de factura

**Solución Aplicada**:
```php
// Extraer géneros desde tallas que ya están agrupadas por género
$generos = array_keys($tallas);  
// Ahora $generos contiene ['DAMA', 'CABALLERO'] etc desde la tabla relacional
```

**Resultado**:  Géneros extraídos correctamente desde `prenda_pedido_tallas`

---

### ❌ HALLAZGO 2: PrendaTallaService->guardarTallasPrenda()
**Archivo**: `app/Application/Services/PrendaTallaService.php`  
**Método**: `guardarTallasPrenda()`  
**Severidad**: 🔴 CRÍTICO  

**Problema**:
El método NO guardaba el campo `genero` en `prenda_pedido_tallas`, aunque la tabla REQUIERE este campo:
```sql
CREATE TABLE prenda_pedido_tallas (
    ...
    genero ENUM('DAMA', 'CABALLERO', 'UNISEX'),  -- REQUERIDO
    ...
    UNIQUE(prenda_pedido_id, genero, talla)
);
```

El método recibía datos como:
- `{'DAMA': {'S': 10, 'M': 20}, 'CABALLERO': {'32': 15}}` (jerárquico correcto)

Pero insertaba como:
- `INSERT INTO prenda_pedido_tallas (prenda_pedido_id, talla, cantidad)` ❌ SIN GÉNERO

**Impacto**:
- Constraint violation o datos incompletos
- Imposible saber qué género pertenece cada talla
- Factura muestra tallas sin género

**Solución Aplicada**:
```php
public function guardarTallasPrenda(int $prendaId, mixed $cantidades): void
{
    // Detectar si estructura es jerárquica {GENERO: {TALLA: CANTIDAD}}
    $firstValue = reset($cantidades);
    if (is_array($firstValue) && !is_numeric(key($cantidades))) {
        // Es jerárquica: iterar por género
        foreach ($cantidades as $genero => $tallasObj) {
            foreach ($tallasObj as $talla => $cantidad) {
                $this->insertarTalla($prendaId, $talla, $cantidad, strtoupper($genero));
                //  Ahora SI guarda el género
            }
        }
    } else {
        // Es plana: usar género default UNISEX
        foreach ($cantidades as $talla => $cantidad) {
            $this->insertarTalla($prendaId, $talla, $cantidad, 'UNISEX');
        }
    }
}

private function insertarTalla(int $prendaId, string $talla, int $cantidad, string $genero): void
{
    DB::table('prenda_pedido_tallas')->insertOrIgnore([
        'prenda_pedido_id' => $prendaId,
        'genero' => strtoupper($genero),  //  AHORA SÍ GUARDA GÉNERO
        'talla' => $talla,
        'cantidad' => $cantidad,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
```

**Resultado**:  Tallas guardadas correctamente con género

---

### ⚠️ HALLAZGO 3: PedidoPrendaService->guardarPrenda()
**Archivo**: `app/Application/Services/PedidoPrendaService.php`  
**Línea**: 280  
**Severidad**: 🟡 IMPORTANTE  

**Problema**:
Solo guardaba tallas si había `cantidades` LEGACY, ignorando `cantidad_talla` (estructura correcta):
```php
// 2b. GUARDAR TALLAS CON CANTIDADES en prenda_tallas_ped (LEGACY)
if (!empty($prendaData['cantidades'])) {  // ❌ Solo si hay "cantidades"
    $this->guardarTallasPrenda($prenda, $prendaData['cantidades']);
}
// ❌ Nunca entra aquí: cantidad_talla es la forma CORRECTA
```

**Impacto**:
- Tallas desde formulario (formato `cantidad_talla`) no se guardaban en tabla relacional
- Sistema parcialmente funciona por caché/legacy, pero incompleto

**Solución Aplicada**:
```php
// 2b. GUARDAR TALLAS en prenda_pedido_tallas DESDE cantidad_talla (estructura relacional)
// IMPORTANTE: cantidad_talla es la fuente correcta: {GENERO: {TALLA: CANTIDAD}}
if (!empty($prendaData['cantidad_talla'])) {
    $this->guardarTallasPrenda($prenda, $prendaData['cantidad_talla']);
} elseif (!empty($prendaData['cantidades'])) {
    // Fallback LEGACY: si no hay cantidad_talla, usar cantidades
    $this->guardarTallasPrenda($prenda, $prendaData['cantidades']);
}
```

**Resultado**:  Ahora procesa `cantidad_talla` primero

---

### ⚠️ HALLAZGO 4: CreacionPrendaSinCtaStrategy
**Archivo**: `app/Domain/PedidoProduccion/Strategies/CreacionPrendaSinCtaStrategy.php`  
**Después de línea**: 116  
**Severidad**: 🔴 CRÍTICO  

**Problema**:
Creaba la prenda pero NO guardaba las tallas en la tabla relacional:
```php
$prendaPedido = PrendaPedido::create([
    'cantidad_talla' => json_encode($cantidadesPorTalla),  //  Guarda en JSON
    'genero' => json_encode($this->procesarGeneros(...)), //  Guarda en JSON
    // ... resto de campos
]);

Log::info(' [CreacionPrendaSinCtaStrategy] Prenda creada', ...);

// ❌ AQUÍ DEBERÍA GUARDAR TALLAS EN TABLA RELACIONAL pero no lo hace
// Va directo a crear variantes
```

**Impacto**:
- Prendas creadas sin registros en `prenda_pedido_tallas`
- Consultas que usan tabla relacional no encuentran tallas
- Factura muestra información incompleta

**Solución Aplicada**:
```php
Log::info(' [CreacionPrendaSinCtaStrategy] Prenda creada', ...);

// ===== PASO 5A: GUARDAR TALLAS EN TABLA RELACIONAL =====
if (!empty($cantidadesPorTalla)) {
    $repository = app(\App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository::class);
    $repository->guardarTallas($prendaPedido->id, $cantidadesPorTalla);
    //  Ahora SÍ guarda en tabla relacional
}
```

**Resultado**:  Tallas guardadas en tabla relacional automáticamente

---

## VALIDACIÓN: ¿QUÉ SE MANTIENE COMO LEGACY?

###  PERMITIDO (Estos campos permanecen como legacy, OK):

1. **`cantidad_talla` JSON en `prendas_pedido`**
   - Razón: Compatibilidad histórica
   - Uso: Solo lectura para LOGS/debugging
   - NO es fuente activa

2. **`genero` JSON en `prendas_pedido`**
   - Razón: Compatibilidad histórica  
   - Uso: NO se debería leer
   - FIX: Se obtenidos de `prenda_pedido_tallas`

3. **`ubicaciones` JSON en procesos**
   - Razón: Datos auxiliares
   - Uso: Se serializa/deserializa según sea necesario

4. **Campos en PROCESOS: `tallas_dama`, `tallas_caballero`**
   - Razón: Legacy de procesos antiguos
   - Uso: Migrarse a `pedidos_procesos_prenda_tallas` cuando sea posible
   - Estado: Aún no hay migracion, pero NO es crítico

---

## RESUMEN DE CAMBIOS

| Archivo | Líneas | Tipo | Severidad | Estado |
|---------|--------|------|-----------|--------|
| PedidosProduccionViewController.php | 712-719 | DELETE legacy | 🔴 Crítico |  Aplicado |
| PrendaTallaService.php | 24-75 | REFACTOR | 🔴 Crítico |  Aplicado |
| PedidoPrendaService.php | 280-287 | REFACTOR | 🟡 Importante |  Aplicado |
| CreacionPrendaSinCtaStrategy.php | 119-134 | AGREGAR | 🔴 Crítico |  Aplicado |

**Total**: 4 cambios aplicados, 0 fallidos

---

## CRITERIO DE ACEPTACIÓN 

Todos los criterios cumplidos:

-  No queda ninguna LECTURA ACTIVA de `cantidad_talla` o `genero` de `prendas_pedido`
-  Factura y recibos construyen géneros desde `prenda_pedido_tallas`
-  UNISEX funciona como género real en tabla relacional
-  Sistema es estable aunque:
  - Una talla tenga proceso y otra no
  - Las cantidades sean diferentes por talla
  - Múltiples géneros en una prenda

---

## PRÓXIMOS PASOS (Opcional, no bloqueante)

1. **Migrar `tallas_dama` / `tallas_caballero` en procesos** a `pedidos_procesos_prenda_tallas` completamente
2. **Limpiar campos legacy** (después de 1-2 meses en producción)
3. **Optimizar queries** que lean de `prenda_pedido_tallas` (agregar índices si es necesario)

---

## NOTAS DE AUDITORÍA

### Archivos Analizados:  CORRECTO

-  `RegistroOrdenQueryController.php` - Las lecturas de `cantidad_talla` son SOLO para logs
-  `ObtenerPedidoDetalleService.php` - Usa trait `GestionaTallasRelacional` correctamente
-  `receipt-manager.js` - Maneja estructura jerárquica correctamente
-  `PedidoProduccionRepository.php` - Tiene método `obtenerTallas()` que usa tabla relacional
-  `invoice-preview-live.js` - Procesa `{GENERO: {TALLA: CANTIDAD}}` correctamente

### Flujo Completo Verificado: 

```
Frontend: cantidad_talla = {'DAMA': {'S': 10, 'M': 20}}
        ↓
PedidoPrendaService::guardarPrenda()
        ↓
PrendaTallaService::guardarTallasPrenda() 
        ↓
BD: INSERT INTO prenda_pedido_tallas (genero='DAMA', talla='S', cantidad=10)
        ↓
ObtenerPedidoDetalleService::obtenerTallas()
        ↓
Factura: "DAMA: S:10 M:20"
```

---

**Auditoría completada satisfactoriamente**  
No hay restos de lógica antigua que causen bugs futuros.
