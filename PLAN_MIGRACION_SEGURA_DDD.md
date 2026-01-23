# 🛡️ PLAN DE MIGRACIÓN SEGURA Y PROGRESIVA A DDD

**Objetivo:** Migrar TODO el código legacy de Pedidos a DDD sin tumbar el sistema  
**Estrategia:** Incrementalismo con rollback strategy  
**Timeline:** 2-3 semanas (trabajo gradual)  
**Risk:** BAJO (cada paso validado)

---

## 🎯 PRINCIPIOS DE MIGRACIÓN SEGURA

### 1. **No Romper Nada**
- Cada cambio es pequeño y testeable
- Sistema funciona en cada paso
- Si algo falla, rollback es fácil (1 commit atrás)

### 2. **Testing Primero**
- Tests ANTES de cambios
- Coverage de funcionalidad critical
- Validación después de cada fase

### 3. **Migración de Adentro Hacia Afuera**
```
Servicios Legacy → Domain Layer
         ↓
      Use Cases (orquestadores)
         ↓
    Controllers (HTTP)
```

### 4. **Paralelismo: Viejo + Nuevo**
- Viejo código sigue funcionando
- Nuevo código se prueba en paralelo
- Se cambian poco a poco los consumers

---

## 📋 PLAN DETALLADO POR FASES

### ⏱️ TOTAL: 3-4 SEMANAS (2-3 horas/día)

---

## FASE 0: PREPARACIÓN (1-2 días)

### Paso 0.1: Setup de Testing
```bash
# Crear tests para funcionalidad crítica
# Sin romper nada, solo validar que funciona
1. Unit tests para servicios legacy clave
2. Feature tests para flujos de pedidos
3. Validar que todo pasa
```

**Tiempo:** 2-3 horas  
**Riesgo:** NINGUNO (solo lectura)

---

## FASE 1A: DOMAIN LAYER (3-4 días)

### Paso 1A.1: Crear Agregados de Producción

**Archivo a crear:**
```php
app/Domain/PedidoProduccion/Agregado/PedidoProduccionAggregate.php
```

**Qué es:**
- Raíz del agregado para producción
- Encapsula reglas de negocio
- NO toca BD, solo lógica

**Cómo:**
1. Extrae lógica de `PedidosProduccionController.php` (métodos de creación, actualización)
2. Crea métodos en el agregado
3. Inyecta servicios necesarios
4. Tests unitarios del agregado

**Rollback:** Si falla, simplemente no se usa

**Tiempo:** 2-3 horas

---

### Paso 1A.2: Crear Value Objects de Producción

**Archivos:**
```php
app/Domain/PedidoProduccion/ValueObjects/EstadoProduccion.php
app/Domain/PedidoProduccion/ValueObjects/NumeroPrendaProduccion.php
app/Domain/PedidoProduccion/ValueObjects/DescripcionProduccion.php
```

**Qué es:**
- Valores inmutables y validados
- Encapsulan reglas simples
- Reutilizables en agregados

**Tiempo:** 1-2 horas

---

### Paso 1A.3: Crear Entities de Producción

**Archivo:**
```php
app/Domain/PedidoProduccion/Entities/PrendaProduccionEntity.php
```

**Tiempo:** 1 hora

---

## FASE 1B: USE CASES (4-5 días)

### Paso 1B.1: Crear Use Cases para Producción

**Usar patrón:**
```php
// ✅ Los Use Cases USAN servicios legacy por ahora
class CrearProduccionPedidoUseCase {
    public function __construct(
        private PedidoProduccionAggregate $agregado,
        private PedidoCreationService $servicioLegacy,  // ← Usando legacy
        private PedidoRepository $repository
    ) {}
    
    public function ejecutar(array $datos) {
        // 1. Crear agregado con lógica DDD
        $pedido = PedidoProduccionAggregate::crear($datos);
        
        // 2. Usar servicio legacy para lo que no podemos cambiar aún
        $this->servicioLegacy->procesarImagenes($pedido);
        
        // 3. Persistir
        $this->repository->guardar($pedido);
        
        return $pedido;
    }
}
```

**Use Cases a crear:**
```
✓ CrearProduccionPedidoUseCase
✓ ActualizarProduccionPedidoUseCase
✓ ConfirmarProduccionPedidoUseCase
✓ ObtenerProduccionPedidoUseCase
✓ ListarProduccionPedidosUseCase
✓ AnularProduccionPedidoUseCase
✓ CambiarEstadoProduccionUseCase
```

**Ventaja:**
- ✅ No rompe servicios legacy
- ✅ Agregado + lógica nueva funciona
- ✅ Fácil de rollback
- ✅ Los servicios legacy se pueden eliminar después

**Tiempo:** 3-4 horas

---

### Paso 1B.2: Registrar Use Cases en Service Provider

```php
// DomainServiceProvider.php
$this->app->singleton(CrearProduccionPedidoUseCase::class);
$this->app->singleton(ActualizarProduccionPedidoUseCase::class);
// ... etc
```

**Tiempo:** 30 min

---

## FASE 2: REFACTORIZAR CONTROLLERS (5-7 días)

### ⚠️ CRÍTICO: Cambios graduales, sin romper rutas

### Paso 2.1: Refactorizar AsesoresController

**Estrategia: Método por método**

```php
// ANTES (Legacy)
public function store(Request $request) {
    $validated = $request->validate([...]);
    $pedido = PedidoProduccion::create($validated);
    $this->servicioLegacy->procesarPrenda($pedido);
    return redirect()->back()->with('success', 'Pedido creado');
}

// PASO 1: Extraer a DTO + Use Case (sin cambiar endpoint)
public function store(Request $request) {
    $dto = CrearProduccionDTO::fromRequest($request);
    $pedido = $this->crearProduccionUseCase->ejecutar($dto);
    return redirect()->back()->with('success', 'Pedido creado');
}

// RESULTADO: Mismo comportamiento, código nuevo
```

**Métodos en orden:**
1. `store()` - Crear (CRÍTICO)
2. `confirm()` - Confirmar
3. `update()` - Actualizar
4. `show()` - Obtener
5. `index()` - Listar
6. `destroy()` - Anular
7. `getNextPedido()` - Siguiente

**Por cada método:**
- Crear Use Case
- Crear DTOs
- Cambiar método (1 línea a la vez)
- Validar que funciona
- Commit

**Tiempo:** 1 día/método = 5-7 días

---

### Paso 2.2: Refactorizar AsesoresAPIController

**Estrategia: Reutilizar Use Cases de 2.1**

```php
// ApiController también usa los mismos Use Cases
public function store(Request $request) {
    $dto = CrearProduccionDTO::fromRequest($request);
    $pedido = $this->crearProduccionUseCase->ejecutar($dto);
    return response()->json($pedido->toArray(), 201);
}
```

**Ventaja:**
- Mismo Use Case = Mismo comportamiento
- Elimina duplicación
- Fácil de mantener

**Tiempo:** 2-3 horas

---

## FASE 3: VALIDACIÓN Y TESTING (3-4 días)

### Paso 3.1: Unit Tests de Use Cases

```bash
# Para cada Use Case
tests/Unit/Application/PedidoProduccion/CrearProduccionPedidoUseCaseTest.php
tests/Unit/Application/PedidoProduccion/ActualizarProduccionPedidoUseCaseTest.php
# ... etc
```

**Qué tesitear:**
- Validaciones
- Casos de error
- Agregados creados correctamente
- Persistencia

**Tiempo:** 1-2 días

---

### Paso 3.2: Feature Tests de Endpoints

```bash
tests/Feature/Pedidos/CrearPedidoTest.php
tests/Feature/Pedidos/ActualizarPedidoTest.php
# ... etc
```

**Qué testear:**
- Endpoint responde correctamente
- Datos guardados en BD
- Comportamiento end-to-end

**Tiempo:** 1 día

---

### Paso 3.3: Validación Manual

```
✓ Crear pedido desde UI (AsesoresController)
✓ Crear pedido desde API (AsesoresAPIController)
✓ Actualizar pedido
✓ Confirmar pedido
✓ Anular pedido
✓ Obtener historial
✓ Cambiar estado
```

**Tiempo:** 2-3 horas

---

## FASE 4: LIMPIAR LEGACY (3-5 días)

### Paso 4.1: Eliminar Servicios Legacy (Gradualmente)

**SOLO después que probamos Use Cases:**

```php
// ❌ Eliminar (porque ya está en agregado)
app/Services/Pedidos/EnriquecerDatosService.php

// ✅ Mantener (todavía usado)
app/Services/PedidoEppService.php (si se usa)
```

**Tiempo:** 1-2 días

---

### Paso 4.2: Migrar Endpoints Restantes

**Controladores sin DDD:**
- PedidoEstadoController
- RegistroBodegaController (parcial)
- SupervisorPedidosController

**Mismo patrón:**
- Use Cases → DTOs → Cambio gradual

**Tiempo:** 2-3 días

---

## 🛡️ ROLLBACK STRATEGY

### Si algo falla en cualquier momento:

```bash
# Ver qué paso está fallando
git log --oneline | head -20

# Rollback seguro (1 commit atrás)
git reset --soft HEAD~1

# Prueba nuevamente
php artisan test

# Si funciona el commit anterior, continúa desde ahí
```

### Estructura de commits:

```
[SAFE] Paso 1A.1: Crear PedidoProduccionAggregate ✓
[SAFE] Paso 1B.1: Crear CrearProduccionPedidoUseCase ✓
[SAFE] Paso 2.1a: Refactorizar AsesoresController::store() ✓
[SAFE] Paso 2.1b: Refactorizar AsesoresController::confirm() ✓
...
```

Cada paso es **reversible en 1 comando**.

---

## 📊 TIMELINE REALISTA

| Fase | Duración | Riesgo | Status |
|------|----------|--------|--------|
| 0: Setup | 2-3h | BAJO | Preparación |
| 1A: Domain | 4-6h | BAJO | Sin tocar controllers |
| 1B: Use Cases | 3-4h | BAJO | Paralelo a servicios |
| 2: Controllers | 5-7 días | MEDIO | Cambios HTTP (validables) |
| 3: Testing | 3-4 días | BAJO | Puro testing |
| 4: Limpieza | 3-5 días | BAJO | Eliminar legacy |
| **TOTAL** | **2-3 semanas** | **BAJO** | **Seguro** |

---

## ✅ CHECKLIST DE VALIDACIÓN

Después de cada fase:

```
□ Todos los tests pasan
□ Sistema funciona en localhost
□ No hay errores en logs
□ Endpoints responden igual
□ BD se actualiza correctamente
□ Usuarios no reportan cambios
□ Código está limpio
□ Tests cubren 80%+
```

---

## 🚀 EMPEZAMOS HOY

**Próximo paso:**
1. ¿Empezamos con Fase 0 (Setup)?
2. ¿Hago el boilerplate de Domain Layer?
3. ¿Creamos los primeros Use Cases?

**¿Listo?** 🎯

---

## 📌 NOTAS IMPORTANTES

- **Cada paso toma 30 min a 2 horas**
- **Sistema funciona 100% en cada paso**
- **Rollback es fácil si falla**
- **Tests nos dan confianza**
- **No hay presión de "terminar rápido"**
- **Mejor lento y bien que rápido y roto**

---

**¿Empezamos?** 🚀
