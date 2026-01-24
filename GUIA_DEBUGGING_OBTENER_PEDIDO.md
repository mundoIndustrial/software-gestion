# Guía de Debugging: ObtenerPedidoUseCase y Relaciones BD

## Síntomas y Diagnóstico

### Síntoma 1: "Cannot read properties of undefined (reading 'map')"

**Ubicación:** En JavaScript `invoice-preview-live.js` línea 826

**Causa probable:**
- API retorna `prendas: []` (vacío) o `prendas: undefined`

**Verificación:**

```bash
# 1. Abrir navegador y ir a URL
GET /api/pedidos/2700

# 2. En consola del navegador, ver respuesta:
console.log(response.data.prendas);

# Si es undefined o [], el problema está en backend
```

**Debugging en Backend:**

```bash
# 1. Abrir Laravel Tinker
php artisan tinker

# 2. Verificar que prendas existen
$pedido = \App\Models\PedidoProduccion::find(2700);
$pedido->prendas->count();  // Debe ser > 0

# 3. Verificar que ObtenerPedidoUseCase retorna prendas
$useCase = app(\App\Application\Pedidos\UseCases\ObtenerPedidoUseCase::class);
$resultado = $useCase->ejecutar(2700);
dd($resultado->prendas);

# 4. Si prendas es [], revisar logs
tail -f storage/logs/laravel.log
# Buscar: "Error obteniendo prendas completas"
```

**Solución según logs:**

| Mensaje de Log | Solución |
|---|---|
| `"Pedido sin prendas"` | Agregar prendas al pedido |
| `"Error obteniendo prendas completas"` | Ver error específico en trace |
| `"Call to undefined method tallas()"` | Verificar FK en tabla prendas_pedido |
| `"Call to undefined method tipoManga()"` | Verificar FK tipo_manga_id en variantes |

---

### Síntoma 2: Frontend Recibe Prendas pero Campos Vacíos

**Descripción:** Modal abre pero muestra "undefined" o campos en blanco

**Verificación:**

```javascript
// En consola del navegador
fetch('/api/pedidos/2700').then(r => r.json()).then(d => console.log(d.data.prendas[0]));

// Verificar estructura:
{
  "nombre_prenda": "CAMISA",
  "tela": "DRILL",  // ¿Es null?
  "color": "NARANJA",  // ¿Es null?
  "variantes": [],  // ¿Está vacío?
  "imagenes_tela": [],  // ¿Está vacío?
  "tallas": {}  // ¿Está vacío?
}
```

**Debugging:**

```bash
# Si tela y color son null:
php artisan tinker

$prenda = \App\Models\PrendaPedido::find(100);
$prenda->coloresTelas;  // ¿Está vacío?

# Si coloresTelas está vacío, necesitas crear registros:
# INSERT INTO prenda_pedido_colores_telas (prenda_pedido_id, color_id, tela_id) 
# VALUES (100, 1, 1);
```

---

### Síntoma 3: "Swal is not defined" en UIModalService

**Ubicación:** En JavaScript `ui-modal-service.js`

**Causa:** SweetAlert2 aún no ha cargado cuando UIModalService intenta usarlo

**Verificación en Navegador:**

```javascript
// Esperar 2 segundos y verificar
setTimeout(() => {
  console.log(typeof Swal);  // Debe ser 'function'
}, 2000);

// Si es 'undefined', el script CDN no cargó
```

**Solución:**

La solución ya está implementada en [public/js/utilidades/ui-modal-service.js](public/js/utilidades/ui-modal-service.js)

```javascript
// Función _ensureSwal() automáticamente espera a que Swal cargue
function _ensureSwal(callback, maxWaitTime = 5000) {
  if (typeof Swal !== 'undefined') {
    callback();
  } else {
    // Reintentar cada 50ms
    const checkInterval = setInterval(() => {
      if (typeof Swal !== 'undefined') {
        clearInterval(checkInterval);
        callback();
      }
    }, 50);
    
    // Timeout después de 5 segundos
    setTimeout(() => clearInterval(checkInterval), maxWaitTime);
  }
}
```

---

## Debugging Step-by-Step

### Paso 1: Verificar Integridad de Datos

```bash
cd C:\Users\Usuario\Documents\trabahiiiii\v10\v10\mundoindustrial

# Ejecutar script de validación
php validate-bd-relations.php 2700
```

**Resultado esperado:** Todos los 

**Si hay error:** Leer mensaje específico y buscar en tabla [Errores Comunes](#errores-comunes-y-soluciones)

---

### Paso 2: Verificar API Endpoint

```bash
# En PowerShell o terminal
curl http://localhost:8000/api/pedidos/2700

# O en navegador
http://localhost:8000/api/pedidos/2700
```

**Respuesta esperada:**
```json
{
  "data": {
    "numero": "2700",
    "prendas": [
      {
        "nombre_prenda": "CAMISA DRILL",
        "tela": "DRILL BORNEO",
        "color": "NARANJA",
        "tallas": {
          "DAMA": { "S": 20, "M": 20 }
        },
        "variantes": [...],
        "imagenes": [...],
        "imagenes_tela": [...]
      }
    ],
    "epps": [...]
  }
}
```

**Si respuesta es diferente:**

| Respuesta | Acción |
|---|---|
| `"prendas": []` | Ejecutar: `SELECT COUNT(*) FROM prendas_pedido WHERE pedido_produccion_id = 2700;` |
| `"prendas": null` | Revisar logs: `tail -f storage/logs/laravel.log` |
| `error: "Something went wrong"` | Ver sección de errores 500 más abajo |

---

### Paso 3: Verificar Logs

```bash
# Ver últimos 50 líneas del log
tail -50 storage/logs/laravel.log

# Filtrar por errores
grep -i "error\|warning" storage/logs/laravel.log

# Filtrar por ObtenerPedido
grep "ObtenerPedido" storage/logs/laravel.log

# Ver el log en tiempo real
tail -f storage/logs/laravel.log
```

**Buscar específicamente:**

- ❌ `"Error obteniendo prendas completas"` → Problema con $prenda->tallas
- ❌ `"Error obteniendo variantes"` → Problema con $prenda->variantes
- ❌ `"Error obteniendo color y tela"` → Problema con coloresTelas
- ❌ `"Error obteniendo imágenes de tela"` → Problema con fotosTela
- ❌ `"Error obteniendo EPPs"` → Problema con $pedido->epps

---

### Paso 4: Verificar Database Directamente

Si sospechas que los datos en BD están mal:

```bash
# Conectar a BD (ajustar según tu BD)
mysql -u usuario -p nombre_bd

# Verificar pedido
SELECT * FROM pedidos_produccion WHERE numero_pedido = 2700;

# Verificar prendas
SELECT * FROM prendas_pedido WHERE pedido_produccion_id = (
  SELECT id FROM pedidos_produccion WHERE numero_pedido = 2700
);

# Verificar tallas de primera prenda
SELECT * FROM prenda_pedido_tallas 
WHERE prenda_pedido_id = 100 
LIMIT 10;

# Verificar variantes
SELECT * FROM prenda_pedido_variantes 
WHERE prenda_pedido_id = 100;

# Verificar colores/telas
SELECT * FROM prenda_pedido_colores_telas 
WHERE prenda_pedido_id = 100;

# Verificar fotos de tela
SELECT * FROM prenda_fotos_tela_pedido 
WHERE prenda_pedido_colores_telas_id IN (
  SELECT id FROM prenda_pedido_colores_telas 
  WHERE prenda_pedido_id = 100
);

# Verificar EPPs
SELECT * FROM pedido_epp 
WHERE pedido_produccion_id = (
  SELECT id FROM pedidos_produccion WHERE numero_pedido = 2700
);
```

---

## Errores Comunes y Soluciones

### Error: `SQLSTATE[HY000]: General error: 1030`

**Causa:** BD está sin espacio o BD está offline

**Solución:**
```bash
# Verificar conexión
php artisan db:ping

# Si falla, revisar config/database.php
# Verificar credenciales en .env
```

---

### Error: `Call to undefined method prendas()`

**Causa:** Modelo PedidoProduccion no tiene relación `prendas()` definida

**Solución:**

Verificar en [app/Models/PedidoProduccion.php](app/Models/PedidoProduccion.php) línea ~155:

```php
public function prendas(): HasMany
{
    return $this->hasMany(PrendaPedido::class, 'pedido_produccion_id');
}
```

Si no existe, agregar esa relación.

---

### Error: `Call to undefined method tallas()`

**Causa:** Modelo PrendaPedido no tiene relación `tallas()` definida

**Solución:**

Verificar en [app/Models/PrendaPedido.php](app/Models/PrendaPedido.php) línea ~140:

```php
public function tallas(): HasMany
{
    return $this->hasMany(PrendaPedidoTalla::class, 'prenda_pedido_id');
}
```

Si no existe, agregar esa relación.

---

### Error: `Foreign key constraint fails`

**Causa:** prenda_pedido_id en prendas_pedido no apunta a pedidos_produccion.id

**Verificación:**
```bash
php artisan tinker

# Ver estructura de tabla
\DB::table('prendas_pedido')->first();

# Debe tener columna pedido_produccion_id
```

**Solución (si falta):**
```sql
ALTER TABLE prendas_pedido ADD COLUMN pedido_produccion_id BIGINT UNSIGNED;
ALTER TABLE prendas_pedido ADD FOREIGN KEY (pedido_produccion_id) 
REFERENCES pedidos_produccion(id) ON DELETE CASCADE;
```

---

### Error: `500 Internal Server Error` desde API

**Debugging:**

```bash
# Ver logs completos
tail -100 storage/logs/laravel.log

# Ver error específico (puede tener múltiples líneas)
grep -A 20 "SQLSTATE\|Exception" storage/logs/laravel.log

# Si es error de relación, habrá algo como:
# "Call to undefined method..."
# "SQLSTATE[42S22]: Column not found"
```

---

### Error: `SQLSTATE[42S22]: Column not found in where clause`

**Causa:** Nombre de tabla o columna incorrecto en relación

**Ejemplo (incorrecto):**
```php
// Esto falla si tabla es "prenda_fotos_pedido" no "prenda_fotos"
public function fotos(): HasMany {
    return $this->hasMany(PrendaFoto::class);  // ← Busca prenda_pedido_id en "prenda_fotos"
}
```

**Corrección:**
```php
// Especificar tabla y FK correctos
public function fotos(): HasMany {
    return $this->hasMany(PrendaFotoPedido::class, 'prenda_pedido_id');
}
```

---

## Herramientas de Debugging

### Herramienta 1: Tinker Interactivo

```bash
php artisan tinker

# Cargar modelo
$prenda = \App\Models\PrendaPedido::find(100);

# Inspeccionar relaciones
dd($prenda->tallas);  // Dumpa y muere (para ver estructura)
$prenda->tallas->count();  // Cuenta registros
$prenda->tallas->pluck('cantidad');  // Extrae una columna

# Ver query SQL generada
\DB::enableQueryLog();
$prenda->tallas;
dd(\DB::getQueryLog());
```

---

### Herramienta 2: Laravel DebugBar (si está instalado)

```bash
# En cualquier request, DebugBar muestra:
# - Queries ejecutadas
# - Tiempos de ejecución
# - Errores de BD
```

Aparece en la esquina inferior derecha del navegador.

---

### Herramienta 3: Query Log en Tests

```php
// En tests
\DB::enableQueryLog();
// ... hacer algo ...
dd(\DB::getQueryLog());
```

Muestra todas las queries SQL ejecutadas.

---

## Checklist de Debugging

- [ ] ¿Pedido existe en BD? → `SELECT COUNT(*) FROM pedidos_produccion WHERE numero_pedido = 2700;`
- [ ] ¿Prendas existen? → `SELECT COUNT(*) FROM prendas_pedido WHERE pedido_produccion_id = ?;`
- [ ] ¿Tallas existen? → `SELECT COUNT(*) FROM prenda_pedido_tallas WHERE prenda_pedido_id = ?;`
- [ ] ¿Variantes existen? → `SELECT COUNT(*) FROM prenda_pedido_variantes WHERE prenda_pedido_id = ?;`
- [ ] ¿Colores/Telas existen? → `SELECT COUNT(*) FROM prenda_pedido_colores_telas WHERE prenda_pedido_id = ?;`
- [ ] ¿Foreign keys correctas? → Ver logs de relaciones
- [ ] ¿API retorna datos? → `GET /api/pedidos/2700` en navegador
- [ ] ¿Frontend recibe datos? → Abrir modal y ver en consola JS
- [ ] ¿Logs sin errores? → `grep "Error\|error" storage/logs/laravel.log`

---

## Contacto para Soporte

Si encuentras un error que no está aquí:

1. **Tomar nota del error exacto**
2. **Revisar storage/logs/laravel.log**
3. **Ejecutar php validate-bd-relations.php 2700**
4. **Compartir:**
   - Error exacto
   - Salida del script de validación
   - Último error en log
   - Query que ejecuta la aplicación
