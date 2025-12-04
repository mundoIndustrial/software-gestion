# REFERENCIA RÁPIDA: ESTADOS COTIZACIONES Y PEDIDOS

## 🎯 RESUMEN EJECUTIVO

Sistema completo de gestión de estados para cotizaciones y pedidos con:
- ✅ 6 estados para cotizaciones
- ✅ 4 estados para pedidos
- ✅ Auditoría completa de cambios
- ✅ Asignación automática de números vía colas
- ✅ Validación de transiciones
- ✅ Manejo de concurrencia
- ✅ APIs JSON

**Archivos Creados**: 20+ archivos (migraciones, modelos, servicios, jobs, controllers)
**Líneas de Código**: ~2000+ líneas implementadas
**Tiempo de Implementación**: ~2 horas

---

## 📁 ESTRUCTURA DE CARPETAS

```
proyecto/
├── app/
│   ├── Enums/
│   │   ├── EstadoCotizacion.php ✨
│   │   └── EstadoPedido.php ✨
│   ├── Jobs/
│   │   ├── AsignarNumeroCotizacionJob.php ✨
│   │   ├── EnviarCotizacionAContadorJob.php ✨
│   │   ├── EnviarCotizacionAAprobadorJob.php ✨
│   │   └── AsignarNumeroPedidoJob.php ✨
│   ├── Models/
│   │   ├── HistorialCambiosCotizacion.php ✨
│   │   ├── HistorialCambiosPedido.php ✨
│   │   ├── Cotizacion.php (actualizado)
│   │   └── PedidoProduccion.php (actualizado)
│   ├── Services/
│   │   ├── CotizacionEstadoService.php ✨
│   │   └── PedidoEstadoService.php ✨
│   └── Http/Controllers/
│       ├── CotizacionEstadoController.php ✨
│       └── PedidoEstadoController.php ✨
├── database/migrations/
│   ├── 2025_12_04_000001_add_estado_to_cotizaciones.php ✨
│   ├── 2025_12_04_000002_add_estado_to_pedidos_produccion.php ✨
│   ├── 2025_12_04_000003_create_historial_cambios_cotizaciones_table.php ✨
│   └── 2025_12_04_000004_create_historial_cambios_pedidos_table.php ✨
└── routes/web.php (actualizado)
```

✨ = Nuevo archivo

---

## 🔄 FLUJOS EN 30 SEGUNDOS

### Cotización
```
BORRADOR → ENVIADA_CONTADOR → APROBADA_CONTADOR 
→ APROBADA_COTIZACIONES → CONVERTIDA_PEDIDO → FINALIZADA
```

### Pedido
```
PENDIENTE_SUPERVISOR → APROBADO_SUPERVISOR 
→ EN_PRODUCCION → FINALIZADO
```

---

## 🎬 ACCIONES PRINCIPALES

### Por Asesor

| Acción | Endpoint | Status Code |
|--------|----------|-------------|
| Crear cotización | (Crear modelo) | - |
| Enviar a contador | `POST /cotizaciones/{id}/enviar` | 200 |
| Ver seguimiento | `GET /cotizaciones/{id}/seguimiento` | 200 |
| Ver historial | `GET /cotizaciones/{id}/historial` | 200 |
| Crear pedido | (Desde cotización) | - |

### Por Contador

| Acción | Endpoint | Status Code |
|--------|----------|-------------|
| Aprobar | `POST /cotizaciones/{id}/aprobar-contador` | 200 |
| Ver historial | `GET /cotizaciones/{id}/historial` | 200 |

### Por Aprobador

| Acción | Endpoint | Status Code |
|--------|----------|-------------|
| Aprobar | `POST /cotizaciones/{id}/aprobar-aprobador` | 200 |
| Ver historial | `GET /cotizaciones/{id}/historial` | 200 |

### Por Supervisor

| Acción | Endpoint | Status Code |
|--------|----------|-------------|
| Aprobar | `POST /pedidos/{id}/aprobar-supervisor` | 200 |
| Ver seguimiento | `GET /pedidos/{id}/seguimiento` | 200 |
| Ver historial | `GET /pedidos/{id}/historial` | 200 |

---

## 💻 CÓDIGO DE EJEMPLO

### Usar Servicio Directamente

```php
use App\Services\CotizacionEstadoService;
use App\Models\Cotizacion;

$cotizacion = Cotizacion::find(1);
$service = new CotizacionEstadoService();

// Enviar a contador
$service->enviarACOntador($cotizacion);

// Obtener estado actual
$estado = $service->obtenerEstadoActual($cotizacion); // "ENVIADA_CONTADOR"

// Obtener historial
$historial = $service->obtenerHistorial($cotizacion);

// Validar transición
$puede = $service->validarTransicion(
    $cotizacion, 
    EstadoCotizacion::APROBADA_CONTADOR
); // true o false
```

### Usar Controller

```php
// En un view o JavaScript
fetch('/cotizaciones/1/enviar', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
})
.then(r => r.json())
.then(data => console.log(data));

// Response:
// {
//   "success": true,
//   "message": "Cotización enviada a contador exitosamente",
//   "cotizacion": { "id": 1, "estado": "ENVIADA_CONTADOR", ... }
// }
```

### Usar Enums

```php
use App\Enums\EstadoCotizacion;

$estado = EstadoCotizacion::BORRADOR;

echo $estado->label();   // "Borrador"
echo $estado->color();   // "gray"
echo $estado->icon();    // "document"

// Validar transición
if ($estado->puedePasar(EstadoCotizacion::ENVIADA_CONTADOR)) {
    echo "✓ Puede pasar";
}
```

---

## 🚀 INICIO RÁPIDO

1. **Ejecutar migraciones**
   ```bash
   php artisan migrate
   ```

2. **Iniciar queue worker** (en terminal separada)
   ```bash
   php artisan queue:work
   ```

3. **Probar API**
   ```bash
   curl -X POST http://localhost:8000/cotizaciones/1/enviar \
     -H "Authorization: Bearer TOKEN"
   ```

---

## 🔑 VARIABLES PRINCIPALES

### Cotizacion Model
```php
$cotizacion->numero_cotizacion;        // int|null (asignado en cola)
$cotizacion->estado;                   // EstadoCotizacion enum
$cotizacion->aprobada_por_contador_en; // timestamp
$cotizacion->aprobada_por_aprobador_en; // timestamp
$cotizacion->historialCambios();       // relación many
```

### PedidoProduccion Model
```php
$pedido->numero_pedido;                // int|null (asignado en cola)
$pedido->estado;                       // EstadoPedido enum
$pedido->aprobado_por_supervisor_en;   // timestamp
$pedido->historialCambios();           // relación many
```

---

## 📊 TABLAS BASE DE DATOS

### cotizaciones
```sql
ALTER TABLE cotizaciones ADD:
- numero_cotizacion INT UNSIGNED UNIQUE NULL
- estado ENUM(6 valores) DEFAULT 'BORRADOR'
- aprobada_por_contador_en TIMESTAMP NULL
- aprobada_por_aprobador_en TIMESTAMP NULL
```

### pedidos_produccion
```sql
ALTER TABLE pedidos_produccion ADD:
- numero_pedido INT UNSIGNED UNIQUE NULL
- estado ENUM(4 valores) DEFAULT 'PENDIENTE_SUPERVISOR'
- aprobado_por_supervisor_en TIMESTAMP NULL
```

### historial_cambios_cotizaciones (NEW)
```sql
CREATE TABLE:
- id BIGINT PRIMARY
- cotizacion_id BIGINT (FK)
- estado_anterior VARCHAR(50) NULL
- estado_nuevo VARCHAR(50)
- usuario_id BIGINT (FK)
- usuario_nombre VARCHAR(255)
- rol_usuario VARCHAR(100)
- razon_cambio TEXT
- ip_address VARCHAR(45)
- user_agent TEXT
- datos_adicionales JSON
- created_at TIMESTAMP
```

### historial_cambios_pedidos (NEW)
```sql
CREATE TABLE:
- Misma estructura que historial_cambios_cotizaciones
- Pero con pedido_id en lugar de cotizacion_id
```

---

## 🎛️ CONFIGURACIÓN

### .env
```env
QUEUE_CONNECTION=database
QUEUE_FAILED_TABLE=failed_jobs
```

### database/config.php
```php
'connections' => [
    'database' => [
        'driver' => 'database',
        'connection' => 'mysql',
        'table' => 'jobs',
    ],
],
```

---

## 🐛 DEBUGGING QUICK COMMANDS

```bash
# Ver jobs en cola
php artisan queue:failed
php artisan queue:monitor

# Procesar jobs
php artisan queue:work --once

# Ver historial en BD
php artisan tinker
DB::table('historial_cambios_cotizaciones')->latest()->first();

# Ver logs
tail -f storage/logs/laravel.log
```

---

## 🔐 GATES A IMPLEMENTAR

```php
// En AuthServiceProvider
Gate::define('isContador', fn(User $u) => $u->hasRole('contador'));
Gate::define('isAprobadorCotizaciones', fn(User $u) => $u->hasRole('aprobador_cotizaciones'));
Gate::define('isSupervisorPedidos', fn(User $u) => $u->hasRole('supervisor_pedidos'));
```

---

## ❌ ERRORES COMUNES

| Error | Causa | Solución |
|-------|-------|----------|
| `Jobs no se procesan` | Worker no corriendo | `php artisan queue:work` |
| `Table 'jobs' doesn't exist` | Migraciones no corridas | `php artisan migrate` |
| `403 Forbidden` | Gates no implementados | Implementar en AuthServiceProvider |
| `numero_cotizacion es NULL` | Job no ejecutado | Esperar a que worker procese |
| `Transición inválida` | Estado incorrecto | Ver enum transicionesPermitidas() |

---

## 📚 DOCUMENTOS RELACIONADOS

- `PLAN-ESTADOS-COTIZACIONES-PEDIDOS.md` - Plan detallado
- `IMPLEMENTACION-ESTADOS-COMPLETADA.md` - Documentación técnica
- `DIAGRAMA-FLUJOS-ESTADOS.md` - Diagramas visuales
- `INSTRUCCIONES-EJECUTAR-ESTADOS.md` - Guía de implementación

---

## ✅ CHECKLIST DE VALIDACIÓN

- [ ] Migraciones ejecutadas (`php artisan migrate`)
- [ ] Queue worker corriendo (`php artisan queue:work`)
- [ ] Modelos creados y relaciones funcionando
- [ ] Servicios inyectados correctamente
- [ ] Controllers respondiendo
- [ ] Rutas registradas
- [ ] Gates implementados
- [ ] Notificaciones creadas (próxima fase)
- [ ] Vistas creadas (próxima fase)
- [ ] Tests escritos

---

## 📞 CONTACTO / SOPORTE

Para preguntas:
1. Revisa los documentos de la carpeta raíz
2. Verifica los logs: `storage/logs/laravel.log`
3. Usa `php artisan tinker` para debugging manual
4. Consulta la documentación de Laravel Queue

---

**Estado**: ✅ IMPLEMENTADO Y LISTO PARA TESTING

**Próxima Fase**: Crear vistas, componentes Blade y notificaciones
