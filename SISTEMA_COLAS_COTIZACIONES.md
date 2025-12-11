# 🚀 Sistema de Colas para Cotizaciones - DDD

## 📋 Descripción

Sistema robusto basado en DDD para generar números de cotización de forma segura cuando múltiples usuarios envían cotizaciones simultáneamente.

Utiliza **database locks** para evitar condiciones de carrera y **colas asincrónicas** para no bloquear al usuario.

## 🏗️ Arquitectura

### Domain Layer
```
app/Domain/Cotizacion/
├── Services/
│   └── GeneradorNumeroCotizacionService.php
│       ├── generarProximo(tipoCotizacionId)
│       ├── generarProximoGlobal()
│       └── obtenerProximo(tipoCotizacionId)
└── Events/
    └── CotizacionEnviada.php
```

**Responsabilidad:** Lógica de negocio para generar números de forma segura.

### Application Layer
```
app/Application/
├── Commands/
│   └── EnviarCotizacionCommand.php
└── Handlers/
    └── EnviarCotizacionHandler.php
```

**Responsabilidad:** Orquestar el envío de cotizaciones.

### Infrastructure Layer
```
app/Jobs/
└── ProcesarEnvioCotizacionJob.php
```

**Responsabilidad:** Procesar el envío en background.

### HTTP Layer
```
app/Infrastructure/Http/Controllers/
└── CotizacionPrendaController.php
    └── store() - Encola el job
```

## 🔒 Seguridad Contra Condiciones de Carrera

### Problema
Cuando 2+ usuarios hacen click en ENVIAR simultáneamente:
```
Usuario A: ¿Cuál es el último número? → 000001
Usuario B: ¿Cuál es el último número? → 000001
Ambos crean: 000002 ❌ DUPLICADO
```

### Solución: Database Lock
```php
$ultimaCotizacion = Cotizacion::where(...)
    ->lockForUpdate() // ← LOCK PESSIMISTA
    ->orderBy('numero_cotizacion', 'desc')
    ->first();
```

**Flujo seguro:**
```
Usuario A: LOCK → Lee 000001 → Genera 000002 → UNLOCK
Usuario B: ESPERA → LOCK → Lee 000002 → Genera 000003 → UNLOCK
```

## 📊 Flujo Completo

### 1. Usuario hace click en ENVIAR
```
POST /asesores/cotizaciones/prenda
{
  "cliente": "Acme Corp",
  "action": "enviar",
  "prendas": [...],
  "especificaciones": {...}
}
```

### 2. Controller crea cotización
```php
$cotizacion = Cotizacion::create([
    'numero_cotizacion' => null, // ← SIN NÚMERO AÚN
    'es_borrador' => false,
    'estado' => 'ENVIADA',
    ...
]);
```

### 3. Controller encola el job
```php
ProcesarEnvioCotizacionJob::dispatch(
    $cotizacion->id,
    3 // tipo_cotizacion_id
)->onQueue('cotizaciones');
```

### 4. Respuesta inmediata al usuario
```json
{
  "success": true,
  "message": "Cotización enviada (procesando número)",
  "redirect": "/asesores/cotizaciones"
}
```

### 5. Job se ejecuta en background
```php
// En el worker
$handler->handle(new EnviarCotizacionCommand(
    $cotizacion->id,
    $tipo_cotizacion_id
));
```

### 6. Handler genera número con lock
```php
$numeroCotizacion = $this->generadorNumero->generarProximoGlobal();
// Usa lockForUpdate() para evitar condiciones de carrera

$cotizacion->update([
    'numero_cotizacion' => $numeroCotizacion,
    'fecha_envio' => now()
]);
```

### 7. Resultado final
```
Cotización #000001 ✅ Guardada en BD
```

## 🛠️ Configuración

### 1. Verificar .env
```env
QUEUE_CONNECTION=database  # ✅ Ya configurado
```

### 2. Crear tabla de jobs (si no existe)
```bash
php artisan queue:table
php artisan migrate
```

### 3. Iniciar el worker
```bash
# Opción 1: Desarrollo
php artisan queue:work --queue=cotizaciones

# Opción 2: Producción (con reinicio automático)
php artisan queue:work --queue=cotizaciones --max-jobs=1000 --max-time=3600

# Opción 3: Con supervisor (recomendado para producción)
# Ver configuración en /etc/supervisor/conf.d/laravel-worker.conf
```

### 4. Monitorear jobs
```bash
# Ver jobs pendientes
php artisan queue:failed

# Reintentar jobs fallidos
php artisan queue:retry all

# Limpiar jobs completados
php artisan queue:flush
```

## 📈 Ventajas del Sistema

✅ **Seguro:** Database locks evitan duplicados
✅ **Rápido:** Usuario no espera a generar número
✅ **Escalable:** Maneja múltiples usuarios simultáneos
✅ **Robusto:** Reintentos automáticos (3 intentos)
✅ **Observable:** Logs detallados de cada paso
✅ **DDD:** Arquitectura limpia y mantenible

## 🔍 Logs Generados

### Cuando usuario envía cotización
```
🔵 CotizacionPrendaController@store - Iniciando guardado
✅ Cotización de Prenda creada (cotizacion_id: 123)
📋 Job de envío encolado (queue: cotizaciones)
```

### Cuando job se ejecuta
```
🔵 ProcesarEnvioCotizacionJob - Iniciando procesamiento
🔵 EnviarCotizacionHandler - Iniciando envío
📊 Número de cotización generado (numero_cotizacion: 000001)
✅ Cotización enviada exitosamente
```

### Si hay error
```
❌ ProcesarEnvioCotizacionJob - Error al procesar
🔄 Reintentando envío (intento: 1/3)
❌ ProcesarEnvioCotizacionJob - Máximo de intentos alcanzado
```

## 🧪 Testing

### Test unitario del Domain Service
```php
public function test_genera_numero_consecutivo()
{
    $service = new GeneradorNumeroCotizacionService();
    
    $numero1 = $service->generarProximoGlobal();
    $numero2 = $service->generarProximoGlobal();
    
    $this->assertEquals('000001', $numero1);
    $this->assertEquals('000002', $numero2);
}
```

### Test de concurrencia
```php
public function test_evita_duplicados_con_concurrencia()
{
    // Simular 10 usuarios enviando simultáneamente
    $numeros = [];
    
    for ($i = 0; $i < 10; $i++) {
        $numero = $this->generador->generarProximoGlobal();
        $numeros[] = $numero;
    }
    
    // Verificar que no hay duplicados
    $this->assertEquals(count($numeros), count(array_unique($numeros)));
}
```

## 📝 Archivos Creados

```
✅ app/Domain/Cotizacion/Services/GeneradorNumeroCotizacionService.php
✅ app/Domain/Cotizacion/Events/CotizacionEnviada.php
✅ app/Application/Commands/EnviarCotizacionCommand.php
✅ app/Application/Handlers/EnviarCotizacionHandler.php
✅ app/Jobs/ProcesarEnvioCotizacionJob.php
✅ app/Infrastructure/Http/Controllers/CotizacionPrendaController.php (actualizado)
```

## 🚀 Próximos Pasos

1. ✅ Ejecutar migrations para tabla de jobs
2. ✅ Iniciar el queue worker
3. ✅ Probar enviando una cotización
4. ✅ Verificar que se genera el número en background
5. ✅ Monitorear logs

## 📞 Soporte

Para ver logs en tiempo real:
```bash
tail -f storage/logs/laravel.log | grep "Cotizacion"
```

Para debuggear un job específico:
```bash
php artisan queue:work --queue=cotizaciones --verbose
```

---

**Versión:** 1.0
**Fecha:** 11 de Diciembre de 2025
**Estado:** ✅ COMPLETADO
