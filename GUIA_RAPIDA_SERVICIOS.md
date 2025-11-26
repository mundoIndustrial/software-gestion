# Guía Rápida - Refactorización de Cotizaciones

## 🎯 Objetivo Alcanzado
Separar responsabilidades del CotizacionesController en servicios especializados siguiendo SOLID principles.

---

## 📁 Archivos Nuevos

```
app/
  Services/
    ├── CotizacionService.php      (233 líneas)
    └── PrendaService.php          (280+ líneas)
  
  DTOs/
    ├── CotizacionDTO.php          (180 líneas)
    └── VarianteDTO.php            (95 líneas)
```

---

## 🔄 Cómo Usar los Servicios

### CotizacionService

```php
use App\Services\CotizacionService;

class MyClass {
    public function __construct(
        private CotizacionService $service
    ) {}
    
    public function ejemplos() {
        // Crear cotización
        $cotizacion = $this->service->crear(
            datosFormulario: $datos,
            tipo: 'borrador',
            tipoCodigo: 'M'
        );
        
        // Actualizar borrador
        $this->service->actualizarBorrador($cotizacion, $datosBorrador);
        
        // Cambiar estado
        $this->service->cambiarEstado($cotizacion, 'enviada');
        
        // Registrar en historial
        $this->service->registrarEnHistorial(
            $cotizacion, 
            'cambio_estado', 
            'Estado cambió a enviada'
        );
        
        // Crear logo
        $this->service->crearLogoCotizacion($cotizacion, $datosLogo);
        
        // Generar número
        $numero = $this->service->generarNumeroCotizacion();
        
        // Eliminar
        $this->service->eliminar($cotizacion);
    }
}
```

### PrendaService

```php
use App\Services\PrendaService;

class MyClass {
    public function __construct(
        private PrendaService $service
    ) {}
    
    public function ejemplos() {
        // Crear batch de prendas
        $this->service->crearPrendasCotizacion($cotizacion, $productos);
        
        // Crear prenda individual
        $prenda = $this->service->crearPrenda(
            $cotizacion, 
            $productoData, 
            0
        );
        
        // Guardar variantes
        $this->service->guardarVariantes($prenda, $productoData);
        
        // Detectar tipo
        $tipo = $this->service->detectarTipoPrenda('Jean Premium');
        // Retorna: ['esJeanPantalon' => true]
        
        // Heredar variantes a pedido
        $this->service->heredarVariantesDePrendaPedido(
            $cotizacion, 
            $prendaPedido, 
            0
        );
    }
}
```

### ImagenCotizacionService (Ya Existente)

```php
use App\Services\ImagenCotizacionService;

class MyClass {
    public function __construct(
        private ImagenCotizacionService $service
    ) {}
    
    public function ejemplos() {
        // Guardar imagen
        $ruta = $this->service->guardarImagen(
            $cotizacionId,
            $archivo,
            'tecniques'
        );
        
        // Guardar múltiples
        $rutas = $this->service->guardarMultiples(
            $cotizacionId,
            $archivos,
            'logos'
        );
        
        // Obtener imágenes
        $imagenes = $this->service->obtenerImagenes($cotizacionId);
        
        // Eliminar
        $this->service->eliminarImagen($rutaStorage);
        
        // Eliminar todas
        $this->service->eliminarTodasLasImagenes($cotizacionId);
    }
}
```

---

## 📊 Flujo de Datos

### Crear Cotización

```
Usuario relleña formulario
       ↓
StoreCotizacionRequest (validación)
       ↓
CotizacionesController::guardar($request)
       ├─→ $datosFormulario = [...datos procesados...]
       ├─→ CotizacionService::crear($datosFormulario, 'borrador')
       │    └─→ Cotizacion::create() ← BD
       ├─→ PrendaService::crearPrendasCotizacion($cotizacion, $productos)
       │    └─→ Por cada prenda:
       │         └─→ PrendaCotizacionFriendly::create() ← BD
       │         └─→ VariantePrenda::create() ← BD
       ├─→ CotizacionService::crearLogoCotizacion($cotizacion, $datosLogo)
       │    └─→ LogoCotizacion::create() ← BD
       └─→ response()->json(['success' => true])
              ↓
           Usuario
```

### Eliminar Cotización (Transacción)

```
Usuario solicita eliminar
       ↓
CotizacionesController::destroy($id)
       ├─→ Verifica autorización
       ├─→ Verifica sea_borrador
       └─→ CotizacionService::eliminar($cotizacion)
            ├─→ DB::beginTransaction()
            ├─→ ImagenCotizacionService::eliminarTodasLasImagenes()
            │    └─→ Storage::delete() ← Archivos
            ├─→ VariantePrenda::delete() ← BD
            ├─→ PrendaCotizacionFriendly::delete() ← BD
            ├─→ LogoCotizacion::delete() ← BD
            ├─→ HistorialCotizacion::delete() ← BD
            ├─→ Cotizacion::delete() ← BD
            ├─→ DB::commit() o DB::rollBack()
            └─→ response()->json(['success' => true])
```

---

## 🧪 Testing Manual

### Test 1: Crear y Guardar Borrador
```bash
POST /asesores/cotizaciones/guardar
Content-Type: application/json

{
  "cliente": "Mi Cliente",
  "tipo": "borrador",
  "productos": [
    {
      "nombre_producto": "Jean Premium",
      "cantidad": 100,
      "tallas": ["S", "M", "L"],
      "variantes": {
        "color": "Azul",
        "tela": "Denim",
        "tipo_manga": "N/A"
      }
    }
  ]
}

Esperado: 
{
  "success": true,
  "message": "Cotización guardada en borradores",
  "cotizacion_id": 1
}
```

### Test 2: Enviar Cotización
```bash
POST /asesores/cotizaciones/1/estado/enviada

Esperado:
{
  "success": true,
  "message": "Estado actualizado correctamente"
}

Verificar:
- numero_cotizacion se generó (COT-00001)
- HistorialCotizacion tiene tipo_cambio='envio'
```

### Test 3: Eliminar Borrador
```bash
DELETE /asesores/cotizaciones/1

Esperado:
{
  "success": true,
  "message": "Borrador eliminado completamente..."
}

Verificar:
- Cotizacion no existe en BD
- VariantePrenda no existen
- LogoCotizacion no existe
- Imágenes borradas del storage
```

---

## 🔍 Debugging

### Verificar Logs
```bash
# Todos los eventos se loguean
tail -f storage/logs/laravel.log | grep -i "cotizacion"

# Buscar errores
tail -f storage/logs/laravel.log | grep -i "error"
```

### Estructura de Logs

```
[timestamp] local.INFO: Cotización creada exitosamente [
  "id" => 1
  "numero_cotizacion" => null (borrador)
  "es_borrador" => true
]

[timestamp] local.INFO: Estado cambiado a: enviada [
  "cotizacion_id" => 1
  "tipo_cambio" => "envio"
  "usuario" => "Ana Martínez"
  "ip" => "192.168.1.100"
]

[timestamp] local.ERROR: Error al eliminar cotización [
  "cotizacion_id" => 999
  "error" => "Cotización no encontrada"
]
```

---

## 📋 Responsabilidades por Clase

### CotizacionesController
```php
✓ Recibir requests HTTP
✓ Validar autorización
✓ Delegar a servicios
✓ Retornar respuestas JSON
✓ Manejar excepciones globales

✗ Crear cotizaciones
✗ Generar números
✗ Guardar variantes
✗ Gestionar imágenes
✗ Registrar historial
```

### CotizacionService
```php
✓ Crear cotizaciones
✓ Actualizar borradores
✓ Cambiar estados
✓ Generar números
✓ Registrar historial
✓ Crear logo/bordado
✓ Eliminar con transacción

✗ Recibir requests
✗ Gestionar imágenes
✗ Crear prendas
✗ Guardar variantes
```

### PrendaService
```php
✓ Crear prendas batch
✓ Crear prendas individuales
✓ Guardar variantes
✓ Detectar tipos
✓ Heredar variantes

✗ Recibir requests
✗ Gestionar cotización padre
✗ Crear transacciones
✗ Gestionar imágenes
```

### ImagenCotizacionService
```php
✓ Guardar imágenes
✓ Obtener imágenes
✓ Eliminar imágenes
✓ Redimensionar
✓ Validar archivos
✓ Convertir a WebP

✗ Crear cotizaciones
✗ Gestionar prendas
✗ Registrar historial
```

---

## 🚀 Próximas Fases

### Fase II: Refactorización Completa
- [ ] Refactorizar aceptarCotizacion()
- [ ] Crear PedidoService
- [ ] Limpiar métodos auxiliares del controller
- [ ] Eliminar crearPrendasCotizacion() del controller

### Fase III: Testing
- [ ] Tests unitarios para CotizacionService
- [ ] Tests unitarios para PrendaService
- [ ] Tests de integración de flujos
- [ ] Tests de transacciones

### Fase IV: API & Optimización
- [ ] API REST v2 usando servicios
- [ ] Cache para tipos de prenda
- [ ] Batch operations optimizadas
- [ ] Performance testing

---

## 📞 Soporte

Si encuentras problemas:

1. **Verifica logs**: `storage/logs/laravel.log`
2. **Verifica BD**: Cotización existe pero no prendas?
3. **Verifica autorización**: El usuario_id coincide?
4. **Verifica transacción**: ¿Se completó todo o se revirtió?
5. **Verifica storage**: ¿Las imágenes se guardaron?

---

## 📚 Referencias

- [REFACTORIZACION_SERVICIOS_COMPLETA.md](./REFACTORIZACION_SERVICIOS_COMPLETA.md) - Documentación completa
- [VALIDACION_FINAL_REFACTORIZACION.md](./VALIDACION_FINAL_REFACTORIZACION.md) - Validación y checklist
- `app/Services/CotizacionService.php` - Código del servicio
- `app/Services/PrendaService.php` - Código del servicio
- `app/Http/Controllers/Asesores/CotizacionesController.php` - Controller refactorizado

---

**Estado**: ✅ COMPLETADO
**Última actualización**: 2024
**Mantenedor**: Equipo de Desarrollo
