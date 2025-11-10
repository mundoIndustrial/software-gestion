# Violaciones SOLID y DDD - Análisis Detallado

**Proyecto:** Mundo Industrial v4.0  
**Fecha:** 10 Noviembre 2025

---

## 🎯 Resumen Ejecutivo

### Problemas Críticos
- ❌ **TablerosController**: 1691 líneas (God Object)
- ❌ **Sin Service Layer**: Lógica de negocio en controladores
- ❌ **Modelos anémicos**: Sin comportamiento de dominio
- ❌ **Sin Bounded Contexts**: Arquitectura monolítica sin separación
- ❌ **Alto acoplamiento**: Dependencias concretas en lugar de abstracciones

---

## 🔴 Violaciones SOLID

### 1. Single Responsibility (SRP) - CRÍTICO

**Problema:** `TablerosController` tiene 10+ responsabilidades

```php
// ❌ MAL: God Object
class TablerosController {
    // Vistas, CRUD producción, CRUD corte, gestión operarios,
    // gestión máquinas, gestión telas, cálculos, filtros,
    // dashboards, valores únicos...
}
```

**Solución:**
```php
// ✅ BIEN: Separar responsabilidades
class TablerosController { /* Solo HTTP */ }
class ProduccionService { /* Lógica negocio */ }
class ProduccionRepository { /* Acceso datos */ }
class CalculadoraProduccion { /* Cálculos */ }
class OperarioController { /* Gestión operarios */ }
class MaquinaController { /* Gestión máquinas */ }
```

### 2. Open/Closed (OCP)

**Problema:** Switch/match hardcodeado
```php
// ❌ MAL: Requiere modificación para agregar tipos
$registros = match($section) {
    'produccion' => RegistroPisoProduccion::all(),
    'polos' => RegistroPisoPolo::all(),
    'corte' => RegistroPisoCorte::all(),
};
```

**Solución:** Strategy Pattern
```php
// ✅ BIEN: Extensible sin modificación
interface ProduccionStrategy {
    public function getRegistros(): Collection;
}
$strategy = app(StrategyFactory::class)->create($section);
$registros = $strategy->getRegistros();
```

### 3. Dependency Inversion (DIP)

**Problema:** Dependencias concretas
```php
// ❌ MAL
class EntregaController {
    public function store() {
        EntregaPedidoCostura::create($data); // Concreto
        event(new EntregaRegistrada($e)); // Concreto
    }
}
```

**Solución:** Inyección de dependencias
```php
// ✅ BIEN
class EntregaController {
    public function __construct(
        private EntregaRepositoryInterface $repo,
        private EventDispatcherInterface $events
    ) {}
}
```

---

## 🏗️ Problemas DDD

### 1. Sin Bounded Contexts

**Actual:** Todo mezclado
```
app/Models/  # ❌ 22 modelos sin organización
app/Controllers/  # ❌ Controladores monolíticos
```

**Propuesto:** Contexts claros
```
app/Domain/
├── Ordenes/     # Context 1
├── Produccion/  # Context 2
├── Corte/       # Context 3
├── Entregas/    # Context 4
├── Balanceo/    # Context 5
└── Shared/      # Kernel compartido
```

### 2. Modelos Anémicos

**Problema:** Sin comportamiento
```php
// ❌ MAL: Solo datos
class TablaOriginal extends Model {
    protected $guarded = [];
}

// Lógica en controlador
if ($orden->estado === 'Entregado') {
    // calcular días...
}
```

**Solución:** Rich Domain Model
```php
// ✅ BIEN: Con comportamiento
class Orden extends Model {
    public function aprobar(User $user): void {
        if (!$this->puedeSerAprobada()) {
            throw new OrdenNoAprobableException();
        }
        $this->estado = EstadoOrden::aprobada();
        $this->raise(new OrdenAprobada($this));
    }
    
    public function calcularDiasHabiles(): int {
        return $this->fechaCreacion->diasHabilesHasta(now());
    }
}
```

### 3. Sin Aggregates

**Problema:** Modificación directa de entidades
```php
// ❌ MAL: Viola invariantes
$item = ItemOrden::find(1);
$item->cantidad = 100;
$item->save();
```

**Solución:** Aggregate Root
```php
// ✅ BIEN: A través del agregado
class Orden {  // Aggregate Root
    public function modificarCantidadItem(int $id, int cantidad): void {
        $item = $this->items->find($id);
        $item->actualizarCantidad($cantidad);
        $this->recalcularTotal();
    }
}
```

---

## 📊 Impacto por Severidad

| Problema | Severidad | Archivos | Impacto |
|----------|-----------|----------|---------|
| God Object | 🔴 Crítico | TablerosController | Imposible mantener |
| Sin Service Layer | 🔴 Crítico | Todos | No testeable |
| Modelos anémicos | 🔴 Crítico | Todos | Lógica dispersa |
| Sin Bounded Contexts | 🟡 Alto | Arquitectura | Difícil escalar |
| Acoplamiento alto | 🔴 Crítico | Controllers | Cambios riesgosos |

---

## ✅ Recomendaciones Prioritarias

### ALTA Prioridad
1. **Refactorizar TablerosController**
   - Crear Service Layer
   - Separar en controladores específicos
   - Implementar Repository Pattern

2. **Implementar Service Layer**
   - ProduccionService
   - EntregaService
   - BalanceoService

3. **Agregar Value Objects**
   - EstadoOrden
   - NumeroOrden
   - Eficiencia

### MEDIA Prioridad
4. **Definir Bounded Contexts**
5. **Implementar Rich Domain Models**
6. **Crear Aggregate Roots**

**Siguiente:** `03-ANALISIS-CONTROLADORES.md`
