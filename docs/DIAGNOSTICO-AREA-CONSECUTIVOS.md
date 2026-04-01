# Problema: Área Incorrecta en el Modal de Prendas

## 🔴 Síntoma del Problema

El recibo de costura con `consecutivo_actual = 15` mostraba el área incorrecta:
- **Había**: "insumos"
- **Debería ser**: "control calidad"

## 🔍 Causa Raíz

Hay dos problemas en el código:

### 1. Query sin ordenamiento explícito
En `ConsecutivosRecibosRepository::obtenerTodosPorPrenda()`:
```php
// ❌ ANTES: Sin ordenar explícitamente
->get();  // Orden impredecible

// ✅ DESPUÉS: Ordenado por ID DESC
->orderByDesc('id')
->get();
```

### 2. Extrae área del primer consecutivo sin considerar el tipo
En `GetSeguimientoPorPrendaUseCase::obtenerAreaMasReciente()`:

Una prenda puede tener múltiples recibos:
```
consecutivos_recibos_pedidos:
- ID 10: tipo_recibo='COSTURA', area='insumos', consecutivo_actual=15 ← Primer registro
- ID 11: tipo_recibo='ESTAMPADO', area='estampado', consecutivo_actual=2
- ID 12: tipo_recibo='BORDADO', area='bordado', consecutivo_actual=3
```

**El problema**: `first()` devol un registro ALEATORIO u de los primeros registrados, no el COSTURA que es el que tienevalor significativo en `consecutivo_actual`.

## ✅ Solución Implementada

### 1. Ordenar explícitamente la query
**Archivo**: `app/Infrastructure/Repositories/ConsecutivosRecibosRepository.php`
```php
return DB::table('consecutivos_recibos_pedidos')
    ->where('prenda_id', $prendaId)
    ->where('pedido_produccion_id', $pedidoId)
    ->where('activo', 1)
    ->orderByDesc('id')  // ← AGREGADO
    ->get();
```

### 2. Filtrar específicamente por COSTURA
**Archivo**: `app/Application/Pedidos/UseCases/RegistroOrden/GetSeguimientoPorPrendaUseCase.php`
```php
private function obtenerAreaMasReciente($consecutivos): ?string
{
    // NUEVO: Buscar el recibo COSTURA (el que tiene consecutivo_actual significativo)
    $reciboCostura = null;
    foreach ($consecutivos as $c) {
        $tipoRecibo = is_array($c) ? ($c['tipo_recibo'] ?? null) : ($c->tipo_recibo ?? null);
        if ($tipoRecibo === 'COSTURA') {
            $reciboCostura = $c;
            break;
        }
    }

    // Usar COSTURA si existe, si no usar el primer consecutivo
    $consecutivoParaArea = $reciboCostura ?? $consecutivos->first();
    
    // Extraer el área del consecutivo correcto
    $arrayData = is_array($consecutivoParaArea) ? $consecutivoParaArea : (array) $consecutivoParaArea;
    $area = $arrayData['area'] ?? null;
    
    return !empty($area) ? trim($area) : null;
}
```

## 🧪 Cómo Verificar que está funcionando

### Opción 1: Query SQL
Ejecutar en la base de datos:
```sql
-- Ver qué área tiene cada recibo para una prenda
SELECT 
    id, tipo_recibo, consecutivo_actual, area
FROM consecutivos_recibos_pedidos
WHERE prenda_id = ? AND pedido_produccion_id = ?
ORDER BY id ASC;

-- Ver qué área tiene específicamente el recibo COSTURA
SELECT area
FROM consecutivos_recibos_pedidos
WHERE tipo_recibo = 'COSTURA' 
  AND prenda_id = ? 
  AND pedido_produccion_id = ?
LIMIT 1;
```

### Opción 2: Script Frontend
Copia el contenido de `database/scripts/verificacion-area-frontend.js` en la consola del navegador, luego carga el modal. Verás exactamente:
- Qué área se está enviando desde el servidor
- Todos los consecutivos registrados
- Sus tipos y áreas correspondientes

### Opción 3: Logs del Servidor
Agregar logs en `GetSeguimientoPorPrendaUseCase`:
```php
\Log::info('[GetSeguimientoPorPrendaUseCase] Área obtenida', [
    'prenda_id' => $prenda->id,
    'area_mas_reciente' => $area_mas_reciente,
    'todos_consecutivos' => $consecutivos->pluck('tipo_recibo', 'area')->toArray()
]);
```

## 📊 Ejemplos de Comportamiento

### Antes del Fix
```
Consecutivos en BD:
- ID 10: COSTURA, area='control calidad' 
- ID 11: ESTAMPADO, area='estampado'
- ID 12: BORDADO, area='bordado'

first() podría devolver cualquiera → Resultado impredecible ❌
```

### Después del Fix
```
Consecutivos en BD:
- ID 10: COSTURA, area='control calidad' ← SELECCIONADO ✅
- ID 11: ESTAMPADO, area='estampado'
- ID 12: BORDADO, area='bordado'

Busca específicamente COSTURA → Resultado consistente ✅
area_mas_reciente = 'control calidad'
```

## 🔄 Cambios Exactos

| Archivo | Cambio | Línea |
|---------|--------|-------|
| `ConsecutivosRecibosRepository.php` | Agregar `->orderByDesc('id')` | ~58 |
| `GetSeguimientoPorPrendaUseCase.php` | Filtrar por tipo_recibo='COSTURA' | ~443-473 |

## ✨ Resultado Esperado

Después de estos cambios:
- ✅ El área mostrada en el modal será siempre la del recibo COSTURA
- ✅ El consecutivo_actual será coherente con el área mostrada
- ✅ No habrá inconsistencias entre "insumos" y "control calidad"
- ✅ La query siempre devuelve ordenada por ID DESC (más reciente primero)
