# 🚀 INSTRUCCIONES: PASAR DE ANTIGUO A NUEVO FLUJO

**Guía paso a paso para migrar el sistema existente al nuevo flujo JSON → BD**

---

##  PRE-REQUISITOS

- [ ] Base de datos actual funcionando
- [ ] Laravel 10+
- [ ] PHP 8.1+
- [ ] Storage accessible

---

## 🔄 MIGRACIÓN (3 pasos)

### PASO 1: Ejecutar migraciones BD

```bash
# Aplicar migraciones de procesos y tablas relacionadas
php artisan migrate

# Verificar que las tablas existan
php artisan tinker
# En tinker:
>>> Schema::getTables();
# Buscar: pedidos_procesos_prenda_detalles, pedidos_procesos_imagenes, etc.
```

### PASO 2: Actualizar modelos

**Archivo:** `app/Models/PrendaPedido.php`

Ya está actualizado con:
```php
public function fotos(): HasMany { ... }
public function fotosTelas(): HasMany { ... }
public function procesos(): HasMany { ... }
```

Verificar que exista:
```bash
php artisan tinker
>>> $prenda = \App\Models\PrendaPedido::first();
>>> $prenda->variantes;
>>> $prenda->fotos;
>>> $prenda->fotosTelas;
>>> $prenda->procesos;
```

### PASO 3: Registrar servicio en container

**Archivo:** `app/Providers/AppServiceProvider.php`

Agregar en `register()`:
```php
$this->app->singleton(
    \App\Domain\PedidoProduccion\Services\GuardarPedidoDesdeJSONService::class,
    function ($app) {
        return new \App\Domain\PedidoProduccion\Services\GuardarPedidoDesdeJSONService(
            $app->make(\App\Domain\PedidoProduccion\Services\ImagenService::class),
        );
    }
);
```

---

## 🧪 TESTING BÁSICO

### Test 1: Guardar pedido simple

```bash
php artisan tinker
```

```php
// 1. Crear pedido de producción
$pedido = \App\Models\PedidoProduccion::create([
    'numero_pedido' => 'TEST-001',
    'cliente' => 'Cliente Test',
    'asesor_id' => 1,
    'forma_de_pago' => 'contado',
    'estado' => 'pendiente',
]);

// 2. Preparar JSON
$datosJSON = [
    'pedido_produccion_id' => $pedido->id,
    'prendas' => [
        [
            'nombre_prenda' => 'Polo Test',
            'descripcion' => 'Polo de prueba',
            'genero' => 'dama',
            'de_bodega' => true,
            'fotos_prenda' => [],
            'fotos_tela' => [],
            'variantes' => [
                [
                    'talla' => 'S',
                    'cantidad' => 20,
                    'color_id' => null,
                    'tela_id' => null,
                    'tipo_manga_id' => null,
                    'manga_obs' => '',
                    'tipo_broche_boton_id' => null,
                    'broche_boton_obs' => '',
                    'tiene_bolsillos' => false,
                    'bolsillos_obs' => ''
                ]
            ],
            'procesos' => []
        ]
    ]
];

// 3. Validar
$validator = \App\Domain\PedidoProduccion\Validators\PedidoJSONValidator::validar($datosJSON);
echo $validator['valid'] ? " Válido\n" : " Inválido\n";

// 4. Guardar
$servicio = app(\App\Domain\PedidoProduccion\Services\GuardarPedidoDesdeJSONService::class);
$resultado = $servicio->guardar($pedido->id, $datosJSON['prendas']);

// 5. Verificar
echo " Resultado:\n";
dump($resultado);
```

**Salida esperada:**
```
 Válido
 Resultado:
{
  "success": true,
  "message": "Pedido guardado correctamente",
  "pedido_id": 1,
  "numero_pedido": "TEST-001",
  "cantidad_prendas": 1,
  "cantidad_items": 20
}
```

---

## 🌐 DESDE FRONTEND

### Opción 1: Con fetch directo

```javascript
// 1. Preparar JSON
const datosJSON = {
    pedido_produccion_id: 1,
    prendas: [...]
};

// 2. Crear FormData
const formData = new FormData();
formData.append('pedido_produccion_id', datosJSON.pedido_produccion_id);
formData.append('prendas', JSON.stringify(datosJSON.prendas));

// 3. Enviar
fetch('/api/pedidos/guardar-desde-json', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: formData
})
.then(res => res.json())
.then(data => {
    if (data.success) {
        console.log(' Pedido guardado:', data.numero_pedido);
    } else {
        console.error(' Error:', data.message);
    }
})
.catch(err => console.error('Error:', err));
```

### Opción 2: Con clase ClientePedidosJSON

```javascript
// 1. Copiar archivo
// public/js/ejemplos/ejemplo-envio-pedido-json.js

// 2. Incluir en view
<script src="{{ asset('js/ejemplos/ejemplo-envio-pedido-json.js') }}"></script>

// 3. Usar
const cliente = new ClientePedidosJSON(csrfToken);
await cliente.ejemplo1_PrendaSimple();
```

---

## 🔄 REEMPLAZAR FLUJO ANTIGUO

### Antiguo flujo (DESCARTAR):
```php
//  NO USAR
$this->pedidoPrendaService->guardarPrendasEnPedido($pedido, $prendas);
```

### Nuevo flujo (USAR):
```php
//  USAR
$guardarService = app(GuardarPedidoDesdeJSONService::class);
$resultado = $guardarService->guardar($pedidoId, $prendas);
```

---

## 📝 CAMBIOS EN RUTAS

### Antigua ruta (DESACTIVADA):
```php
POST /asesores/pedidos-editable/crear
```

### Nueva ruta (ACTIVADA):
```php
POST /api/pedidos/guardar-desde-json
POST /api/pedidos/validar-json
```

---

## 🐛 TROUBLESHOOTING

### Error: "Servicio no encontrado"
**Solución:** Registrar en AppServiceProvider:
```php
// En app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton(
        GuardarPedidoDesdeJSONService::class,
        fn($app) => new GuardarPedidoDesdeJSONService(
            $app->make(ImagenService::class),
        )
    );
}
```

### Error: "Tabla no existe"
**Solución:** Ejecutar migraciones:
```bash
php artisan migrate
```

### Error: "Validación fallida"
**Solución:** Revisar logs:
```bash
tail -f storage/logs/laravel.log
```

### Imágenes no se guardan
**Solución:** Verificar permisos:
```bash
chmod -R 775 storage/
php artisan storage:link
```

---

##  VERIFICACIÓN FINAL

```bash
# 1. Migraciones ejecutadas
php artisan migrate:status | grep procesos

# 2. Modelos actualizados
php artisan tinker
>>> $prenda = \App\Models\PrendaPedido::first();
>>> $prenda->procesos->count();

# 3. Rutas registradas
php artisan route:list | grep api/pedidos

# 4. Servicio disponible
php artisan tinker
>>> app(\App\Domain\PedidoProduccion\Services\GuardarPedidoDesdeJSONService::class);

# 5. Test unitario
php artisan test --filter GuardarPedidoTest
```

---

## 📊 COMPARATIVA

| Aspecto | Antiguo | Nuevo |
|---------|---------|-------|
| Transacciones |  No garantizadas |  Automáticas |
| Validación |  Básica |  Exhaustiva |
| Logging |  Mínimo |  Detallado |
| Imágenes |  Sin conversión |  WebP automático |
| Rollback |  Manual |  Automático |
| Documentación |  Mínima |  Completa |
| Testing |  Difícil |  Fácil |

---

## 🎯 RESUMEN

**Antes:**
```
Frontend → Controller → Service (sin transacción) → BD 
```

**Después:**
```
Frontend → Controller → Validador  → Servicio (transacción) → BD 
```

---

## 📞 SOPORTE

Si encuentra problemas:

1. **Revisar logs:** `storage/logs/laravel.log`
2. **Ejecutar test:** `php artisan tinker`
3. **Verificar BD:** `php artisan tinker` → Ver relaciones
4. **Consultar documentación:** `GUIA_FLUJO_JSON_BD.md`

---

**¡Migración completada!** 

