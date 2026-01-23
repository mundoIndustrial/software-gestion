# 🎬 GUÍA: CÓMO REFACTORIZAR CONTROLLERS A DDD (Fase 2)

**Objetivo:** Refactorizar `AsesoresController.php` método por método  
**Tiempo estimado:** 5-7 días (1 método/día)  
**Riesgo:** BAJO (cambios pequeños, validables)

---

## 🔍 ANÁLISIS ACTUAL DEL ASESORESCONTROLLER

**Archivo:** `app/Http/Controllers/Asesores/AsesoresController.php`  
**Líneas:** ~640  
**Métodos a refactorizar:** 7

```php
1. index()           - Listar pedidos
2. create()          - Mostrar formulario crear
3. store()           - GUARDAR pedido (CRÍTICO)
4. confirm()         - CONFIRMAR pedido (CRÍTICO)
5. show()            - Ver detalle pedido
6. edit()            - Mostrar formulario editar
7. update()          - Actualizar pedido
8. destroy()         - Anular pedido
```

---

## ⚡ PATRÓN DE REFACTORIZACIÓN (Reutilizable)

### ANTES (Legacy - Mezcla HTTP + Lógica)

```php
public function store(Request $request)
{
    // 1. Validar
    $validated = $request->validate([
        'numero_pedido' => 'required|unique:pedidos',
        'cliente' => 'required',
        'prendas' => 'required|array',
    ]);

    // 2. Crear en BD
    $pedido = PedidoProduccion::create($validated);

    // 3. Procesar prendas (lógica compleja aquí)
    foreach ($validated['prendas'] as $prenda) {
        $this->servicioLegacy->procesarPrenda($pedido, $prenda);
    }

    // 4. Retornar
    return redirect()->back()->with('success', 'Pedido creado');
}
```

**Problemas:**
- ❌ Lógica de negocio en controller
- ❌ Validaciones esparcidas
- ❌ Difícil de testear
- ❌ Difícil de reutilizar

---

### DESPUÉS (DDD - Separación de responsabilidades)

```php
public function store(Request $request)
{
    // 1. Validar HTTP (Laravel Validation)
    $request->validate([
        'numero_pedido' => 'required|unique:pedidos',
        'cliente' => 'required',
        'prendas' => 'required|array',
    ]);

    // 2. Crear DTO (validaciones de dominio)
    $dto = CrearProduccionPedidoDTO::fromRequest($request->all());

    // 3. Ejecutar caso de uso (TODO: inyectar en constructor)
    $pedido = $this->crearProduccionUseCase->ejecutar($dto);

    // 4. Persistir (TODO: agregar en use case)
    // $this->pedidoRepository->guardar($pedido);

    // 5. Retornar
    return redirect()->back()->with('success', 'Pedido creado');
}
```

**Beneficios:**
- ✅ Controller solo orquesta
- ✅ Lógica en agregado (testeable)
- ✅ DTOs validan entrada
- ✅ Reutilizable en API también

---

## 📝 PASO A PASO: Refactorizar `store()`

### Paso 1: Leer el método actual

**Archivo:** `app/Http/Controllers/Asesores/AsesoresController.php`

```bash
# Lee el método store() completo
1. Identifica qué valida
2. Identifica qué hace con BD
3. Identifica qué lógica es de negocio
4. Toma nota de excepciones
```

### Paso 2: Crear test para validar que funciona

```php
// tests/Feature/AsesoresController/StoreTest.php
namespace Tests\Feature\AsesoresController;

use Tests\TestCase;

class StoreTest extends TestCase
{
    /**
     * @test
     * Validar que crear pedido funciona como antes
     */
    public function puede_crear_pedido()
    {
        $response = $this->post('/asesores/pedidos', [
            'numero_pedido' => 'PED-2024-001',
            'cliente' => 'Cliente Test',
            'prendas' => [
                ['numero' => '001', 'cantidad' => 10],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pedidos', [
            'numero_pedido' => 'PED-2024-001',
        ]);
    }

    /**
     * @test
     * Validar que valida número_pedido requerido
     */
    public function rechaza_sin_numero_pedido()
    {
        $response = $this->post('/asesores/pedidos', [
            'cliente' => 'Cliente Test',
        ]);

        $response->assertSessionHasErrors('numero_pedido');
    }
}
```

**Ejecutar:**
```bash
php artisan test tests/Feature/AsesoresController/StoreTest.php

# Debe pasar con código ACTUAL
# Si falla, significa que el código actual está roto
```

### Paso 3: Inyectar Use Case en controller

```php
namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use Illuminate\Routing\Controller;

class AsesoresController extends Controller
{
    // Inyectar Use Case
    public function __construct(
        private CrearProduccionPedidoUseCase $crearProduccionUseCase,
    ) {
    }

    // ... resto del código
}
```

### Paso 4: Refactorizar método `store()`

**ANTES:**
```php
public function store(Request $request)
{
    $validated = $request->validate([
        'numero_pedido' => 'required|unique:pedidos',
        'cliente' => 'required',
        'prendas' => 'required|array',
    ]);

    $pedido = PedidoProduccion::create($validated);
    
    foreach ($validated['prendas'] as $prenda) {
        $this->servicioLegacy->procesarPrenda($pedido, $prenda);
    }

    return redirect()->back()->with('success', 'Pedido creado');
}
```

**DESPUÉS:**
```php
public function store(Request $request)
{
    // 1. Validar HTTP
    $request->validate([
        'numero_pedido' => 'required|unique:pedidos',
        'cliente' => 'required',
        'prendas' => 'required|array',
    ]);

    // 2. Crear DTO (encapsula validaciones de dominio)
    $dto = CrearProduccionPedidoDTO::fromRequest($request->all());

    // 3. Ejecutar use case (orquestación)
    try {
        $pedido = $this->crearProduccionUseCase->ejecutar($dto);

        // TODO: Cuando tengamos repositorio, guardar aquí:
        // $this->pedidoRepository->guardar($pedido);

        return redirect()->back()->with('success', 'Pedido creado exitosamente');
    } catch (Exception $e) {
        return redirect()->back()
            ->withErrors(['error' => $e->getMessage()])
            ->withInput();
    }
}
```

### Paso 5: Ejecutar tests

```bash
# Ejecutar test del método
php artisan test tests/Feature/AsesoresController/StoreTest.php

# Debe pasar igual que antes
```

**Si pasa:** ✅ Método refactorizado correctamente

### Paso 6: Hacer commit

```bash
git add app/Http/Controllers/Asesores/AsesoresController.php
git add tests/Feature/AsesoresController/StoreTest.php
git commit -m "[CONTROLLER] Refactorizar AsesoresController::store() → Use Case"
```

---

## 🔄 ORDEN DE REFACTORIZACIÓN (Recomendado)

```
DÍA 1: store()    - CRÍTICO (crear pedido)
DÍA 2: confirm()  - CRÍTICO (confirmar pedido)
DÍA 3: update()   - Actualizar pedido
DÍA 4: destroy()  - Anular pedido
DÍA 5: show()     - Obtener detalle
DÍA 6: index()    - Listar pedidos
DÍA 7: create()   - Formulario crear
```

**Por qué este orden:**
1. Métodos críticos primero (store, confirm)
2. Luego métodos que modifican (update, destroy)
3. Luego métodos de lectura (show, index)
4. Métodos de formulario al final (create)

---

## 🛡️ VALIDACIONES ANTES DE CADA COMMIT

Después de refactorizar cada método:

```
✓ Test específico del método PASA
✓ Tests de otros métodos siguen pasando
✓ Sin errores de sintaxis (php -l)
✓ Sistema funciona en local
✓ Base de datos se actualiza correctamente
```

**Script de validación:**
```bash
# Ejecutar todo
php artisan test
php -l app/Http/Controllers/Asesores/AsesoresController.php

# Si todo pasa → git commit
```

---

## 🚨 PROBLEMAS COMUNES Y SOLUCIONES

### Problema 1: "Use Case no implementado"

**Error:**
```
Call to undefined method CrearProduccionPedidoUseCase::ejecutar()
```

**Solución:**
- Verifica que el Use Case está importado
- Verifica que el Use Case tiene el método `ejecutar()`
- Verifica que está registrado en Service Provider

---

### Problema 2: "DTO valida diferente al controller"

**Ejemplo:**
```php
// Controller valida
$request->validate(['numero' => 'required']);

// Pero DTO valida diferente
class CrearProduccionPedidoDTO {
    if (strlen($numero) > 50) throw InvalidArgumentException;
}
```

**Solución:**
- Las validaciones HTTP van en controller
- Las validaciones de dominio van en DTO/Agregado
- Si hay conflicto, usar "reglas más estrictas"

---

### Problema 3: "Método usa $this->servicioLegacy"

**Ejemplo:**
```php
public function store(Request $request)
{
    // ...
    $this->servicioLegacy->procesarImagenes($pedido);
}
```

**Solución:**
- Por ahora, seguir usando el servicio legacy
- Inyectarlo en Use Case
- Más adelante, migrar a agregado

```php
// Use Case (versión mejorada)
public function __construct(
    private CrearProduccionPedidoUseCase $useCase,
    private ImagenService $imagenService,  // Legacy
) {
}

public function store(Request $request)
{
    // ...
    $pedido = $this->crearProduccionUseCase->ejecutar($dto);
    $this->imagenService->procesarImagenes($pedido); // Legacy
}
```

---

## 📊 MATRIZ DE MÉTODOS

| Método | Complejidad | Criticidad | Tests Necesarios | Dependencias |
|--------|------------|-----------|-----------------|--------------|
| store() | Alta | CRÍTICA | 5+ | ImagenService |
| confirm() | Media | CRÍTICA | 3+ | EstadoService |
| update() | Alta | Alta | 4+ | PrendaService |
| destroy() | Baja | Media | 2+ | - |
| show() | Baja | Baja | 1+ | Repository |
| index() | Media | Baja | 2+ | Repository |
| create() | Baja | Baja | 0+ | - |

---

## 🎯 CHECKLIST PARA REFACTORIZAR CADA MÉTODO

```
□ Leer método actual y entender lógica
□ Crear test que valida comportamiento actual
□ Ejecutar test (debe pasar)
□ Crear Use Case si no existe
□ Crear DTO si no existe
□ Inyectar Use Case en controller
□ Reescribir método usando Use Case
□ Ejecutar test (debe seguir pasando)
□ Validar que no rompe otros métodos
□ Hacer commit pequeño y descriptivo
□ Documentar cambios en SEGUIMIENTO_MIGRACION_DDD.md
```

---

## 🚀 COMANDO RÁPIDO: REFACTOR LOOP

```bash
# Script que automatiza el ciclo

#!/bin/bash
# refactor.sh

METHOD=$1
TEST_FILE="tests/Feature/AsesoresController/${METHOD}Test.php"

echo "1. Ejecutar test previo..."
php artisan test $TEST_FILE

echo "2. Refactorizar método..."
# (hacer cambios aquí)

echo "3. Ejecutar tests nuevamente..."
php artisan test $TEST_FILE

echo "4. Hacer commit..."
git add -A
git commit -m "[CONTROLLER] Refactorizar AsesoresController::${METHOD}()"

echo "✅ Listo!"
```

**Uso:**
```bash
chmod +x refactor.sh
./refactor.sh store
./refactor.sh confirm
./refactor.sh update
```

---

## 📌 NOTAS IMPORTANTES

- **No refactorizar todo a la vez**  
  Cambio pequeño = Riesgo bajo = Rollback fácil

- **Tests ANTES de cambiar**  
  Que el test pase CON el código actual

- **Commit por cada método**  
  No agrupar múltiples métodos en un commit

- **Validar en local**  
  Antes de cada commit, probar manualmente

- **Keep legacy working**  
  Si un método falla, no afecta a otros

---

## 🎬 EJEMPLO COMPLETO: Refactorizar `confirm()`

### Paso 1: Leer código actual

```php
// app/Http/Controllers/Asesores/AsesoresController.php
public function confirm(Request $request)
{
    $pedido = PedidoProduccion::find($request->pedido_id);
    
    if (!$pedido) {
        return back()->withErrors('Pedido no existe');
    }

    $pedido->estado = 'confirmado';
    $pedido->fecha_confirmacion = now();
    $pedido->save();

    // Notificar supervisores
    $this->servicioLegacy->notificarSupervisores($pedido);

    return back()->with('success', 'Pedido confirmado');
}
```

### Paso 2: Crear test

```php
// tests/Feature/AsesoresController/ConfirmTest.php
public function puede_confirmar_pedido()
{
    $pedido = PedidoProduccion::factory()->create();

    $response = $this->post("/asesores/pedidos/{$pedido->id}/confirm");

    $response->assertRedirect();
    $this->assertDatabaseHas('pedidos', [
        'id' => $pedido->id,
        'estado' => 'confirmado',
    ]);
}
```

### Paso 3: Refactorizar

```php
public function confirm(Request $request)
{
    // 1. Obtener pedido
    $pedidoModel = PedidoProduccion::find($request->pedido_id);
    if (!$pedidoModel) {
        return back()->withErrors('Pedido no existe');
    }

    // 2. Crear DTO
    $dto = ConfirmarProduccionPedidoDTO::fromRequest(
        (string)$pedidoModel->id,
        $request->all()
    );

    // 3. Ejecutar Use Case
    try {
        $pedido = $this->confirmarProduccionUseCase->ejecutar($dto);

        // 4. Actualizar modelo (TEMPORAL - hasta migrar a DDD)
        $pedidoModel->estado = $pedido->getEstado();
        $pedidoModel->fecha_confirmacion = $pedido->getFechaConfirmacion();
        $pedidoModel->save();

        // 5. Notificar (legacy)
        $this->servicioLegacy->notificarSupervisores($pedidoModel);

        return back()->with('success', 'Pedido confirmado');

    } catch (Exception $e) {
        return back()->withErrors(['error' => $e->getMessage()]);
    }
}
```

### Paso 4: Commit

```bash
git commit -m "[CONTROLLER] Refactorizar AsesoresController::confirm() → Use Case"
```

---

**¿Listo para empezar?** 🚀

Próximo paso: Leer el método actual de `store()` en AsesoresController y crear el test.
