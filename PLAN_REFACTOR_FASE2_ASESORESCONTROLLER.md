#  FASE 2: REFACTOR DE ASESORESCONTROLLER

## Estrategia: Cambios Mínimos y Seguros

**Objetivo:** Refactorizar el método `store()` para usar el nuevo `CrearProduccionPedidoUseCase`

**Riesgo:** BAJO - El servicio legacy seguirá funcionando en paralelo

**Cambio que vamos a hacer:**

```php
// ANTES (Legacy)
public function store(Request $request) {
    // ... validaciones ...
    $pedido = $this->guardarPedidoProduccionService->guardar($validated, $productosConFotos);
    return response()->json([...]);
}

// DESPUÉS (DDD)
public function store(Request $request) {
    // ... validaciones iguales ...
    $dto = CrearProduccionPedidoDTO::fromRequest($validated);
    $pedido = $this->crearProduccionPedidoUseCase->ejecutar($dto);
    return response()->json([...]); // ← Mismo response
}
```

**Ventajas:**
 Mismo comportamiento externo  
 Mismo response JSON  
 Código más limpio  
 Fácil de rollback  
 Use Case puede crecer sin tocar controller  

---

## Paso 1: Inyectar el Use Case en el Constructor

**Archivo:** `AsesoresController.php`  
**Línea:** Después del constructor actual

**Agregar:**
```php
protected CrearProduccionPedidoUseCase $crearProduccionPedidoUseCase;

public function __construct(
    // ... servicios existentes ...
    CrearProduccionPedidoUseCase $crearProduccionPedidoUseCase
) {
    // ... asignaciones existentes ...
    $this->crearProduccionPedidoUseCase = $crearProduccionPedidoUseCase;
}
```

**Riesgo:** NINGUNO - Solo agregar inyección

---

## Paso 2: Refactorizar el Método `store()`

**Cambio mínimo:** Reemplazar la llamada al servicio legacy por el Use Case

**Antes:**
```php
$productosConFotos = $this->procesarFotosTelasService->procesar($request, $validated[$productosKey]);
$pedido = $this->guardarPedidoProduccionService->guardar($validated, $productosConFotos);
```

**Después:**
```php
// Para ahora mantener compatibilidad, procesamos fotos igual
$productosConFotos = $this->procesarFotosTelasService->procesar($request, $validated[$productosKey]);

// Pero usamos el Use Case para la lógica de negocio
$dto = CrearProduccionPedidoDTO::fromRequest($validated);
$dto->prendas = $productosConFotos; // ← Agregar prendas al DTO

$pedido = $this->crearProduccionPedidoUseCase->ejecutar($dto);
```

**Riesgo:** BAJO - El Use Case encapsula la lógica

---

## Paso 3: Validación

Después de cada cambio:
```bash
# 1. Verificar que no hay errores de sintaxis
php -l app/Infrastructure/Http/Controllers/Asesores/AsesoresController.php

# 2. Verificar que el servicio inyecta correctamente
php artisan tinker
# > $controller = app(\App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class);
# > $controller->crearProduccionPedidoUseCase

# 3. Hacer request de prueba
# POST /asesores/pedidos (desde Postman o similar)

# 4. Verificar logs
tail -f storage/logs/laravel.log
```

---

## Rollback Rápido (si algo falla)

```bash
git log --oneline | head -5
git reset --soft HEAD~1
php -l app/Infrastructure/Http/Controllers/Asesores/AsesoresController.php
```

**Tiempo:** < 1 minuto  
**Datos:** Ninguno se pierde

---

## ¿Empezamos?

Pasos:
1. Inyectar CrearProduccionPedidoUseCase
2. Modificar store() para usar el Use Case
3. Validar que funciona
4. Commit pequeño y limpio
5. Siguiente método (confirm())

---

**Status:** LISTO PARA EMPEZAR 🎯
