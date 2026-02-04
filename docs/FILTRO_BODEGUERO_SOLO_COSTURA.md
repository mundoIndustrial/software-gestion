# 🔐 Filtro Bodeguero - Solo COSTURA-BODEGA

## Descripción

El bodeguero ahora ve **ÚNICAMENTE** recibos de tipo **COSTURA-BODEGA**, no otros procesos.

## Cambios Realizados

### Backend - Filtrado en `/pedidos-public/{id}/recibos-datos`

**Archivo**: `app/Http/Controllers/Api_temp/PedidoController::obtenerDetalleCompleto()`

**Lógica**:
1. Detecta si el usuario autenticado tiene rol `bodeguero`
2. Si es bodeguero, filtra los procesos de cada prenda
3. Solo mantiene procesos donde el tipo contiene `'costura-bodega'`
4. Reindexiza el array para que el frontend no tenga índices vacíos

**Código**:
```php
// Verificar si es bodeguero
$esBodyguero = auth()->check() && auth()->user()->hasRole('bodeguero');

// FILTRO BODEGUERO: Si es bodeguero, filtrar procesos para mostrar SOLO 'costura-bodega'
if ($esBodyguero && isset($responseData['prendas']) && is_array($responseData['prendas'])) {
    foreach ($responseData['prendas'] as &$prenda) {
        if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
            // Filtrar: solo mantener procesos 'costura-bodega'
            $procesosFiltrados = array_filter($prenda['procesos'], function($proceso) {
                $tipo = strtolower($proceso['tipo_proceso'] ?? $proceso['nombre_proceso'] ?? '');
                return strpos($tipo, 'costura-bodega') !== false || strpos($tipo, 'costurabodega') !== false;
            });
            
            $prenda['procesos'] = array_values($procesosFiltrados); // Reindexar array
        }
    }
}
```

## Flujo de Acceso

```
Bodeguero accede a Vista Bodega
    ↓
Abre card de prenda
    ↓
Haz clic en "Ver Recibos"
    ↓
Frontend llama a `/pedidos-public/{pedidoId}/recibos-datos`
    ↓
Backend detecta rol bodeguero
    ↓
Filtra procesos → solo devuelve 'costura-bodega'
    ↓
Frontend renderiza modal con SOLO COSTURA-BODEGA
```

## Logs de Debug

El sistema registra logs cuando activa el filtro bodeguero:

```
[2026-02-04] [PedidoController] 🔐 FILTRO BODEGUERO: Filtrando procesos - Solo COSTURA-BODEGA
  - pedido_id: 1
  - usuario_id: 5
  - total_prendas: 1

[2026-02-04] [PedidoController] 🔐 Procesos filtrados para bodeguero
  - prenda_id: 1
  - procesos_antes: 7
  - procesos_despues: 1
```

## Testing

### Escenario 1: Usuario Normal (Asesor/Supervisor)
```
GET /pedidos-public/1/recibos-datos (como asesor)
Response: Todos los procesos de la prenda
  - COSTURA
  - COSTURA-BODEGA
  - BORDADO
  - ESTAMPADO
  - etc.
```

### Escenario 2: Bodeguero
```
GET /pedidos-public/1/recibos-datos (como bodeguero)
Response: SOLO procesos COSTURA-BODEGA
  - COSTURA-BODEGA ← ÚNICO PROCESO
```

## Variantes Soportadas

El filtro detecta variantes del nombre:
-  `costura-bodega`
-  `COSTURA-BODEGA`
-  `costurabodega`
-  `COSTURABODEGA`
-  `Costura-Bodega`

## Retrocompatibilidad

 **Sin efectos en otros roles**: Cortador, Costurero, Asesor, Admin, etc. ven todos los procesos normalmente.

 **Sin efectos en otras vistas**: Solo aplica en endpoints que retornan `recibos-datos`.

## Notas Importantes

-  El filtro solo aplica si el usuario está autenticado Y tiene rol bodeguero
-  Si no hay procesos COSTURA-BODEGA, el bodeguero verá prenda sin procesos
-  El frontend (`ReceiptBuilder`) recibirá solo 1 proceso, por lo que mostrará solo COSTURA-BODEGA

---

**Implementación**: 4 de Febrero de 2026
**Estado**:  Producción
