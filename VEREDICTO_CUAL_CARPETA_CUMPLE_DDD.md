# 📊 ANÁLISIS COMPARATIVO: ¿Cuál carpeta es mejor y cumple DDD?

## CRITERIOS DE EVALUACIÓN DDD

### 1. **Aggregate Root Pattern** 
Un Agregado debe:
- Tener una raíz (AggregateRoot)
- Encapsular lógica de dominio
- Tener límites claros
- Manejar invariantes

---

## 🔍 COMPARACIÓN DETALLADA

### **Carpeta A: `/app/Domain/Pedidos`**

####  **FORTALEZAS DDD:**
```php
// 1. Extiende AggregateRoot (correcto DDD)
class PedidoAggregate extends AggregateRoot { ... }

// 2. Usa ValueObjects (encapsulación)
private NumeroPedido $numero;
private Estado $estado;

// 3. Factory Methods (creación segura)
public static function crear(...) { ... }
public static function reconstruir(...) { ... }

// 4. Métodos de Dominio (lógica de negocio)
public function confirmar(): void { ... }
public function iniciarProduccion(): void { ... }
public function completar(): void { ... }

// 5. Valida Invariantes (no acepta estados inválidos)
if ($this->estado->esFinal()) {
    throw new \DomainException(...);
}

// 6. Contiene Entities internas
private Collection $prendas;  // PrendaPedido es una Entity interna
```

#### ⚠️ **DEBILIDADES:**
- Sencilla, puede necesitar más operaciones
- No dispara eventos de dominio (aunque tiene infrastructure para ello)
- CQRS está en Application, no en Domain (lo cual es correcto)

---

### **Carpeta B: `/app/Domain/PedidoProduccion`**

####  **FORTALEZAS DDD:**
```php
// 1. Implementa Event Sourcing
private array $uncommittedEvents = [];

// 2. Factory Method con eventos
public static function crear(...): self {
    $agregado = new self(...);
    $agregado->recordEvent(new PedidoProduccionCreado(...));
    return $agregado;
}

// 3. Valida Invariantes
if (!in_array($nuevoEstado, $estadosValidos)) {
    throw new \InvalidArgumentException(...);
}

// 4. Métodos de Dominio
public function agregarCantidad(int $cantidad): void { ... }
public function cambiarEstado(string $nuevoEstado): void { ... }
```

#### ❌ **DEBILIDADES DDD (CRÍTICAS):**
```php
// 1. NO extiende AggregateRoot
class PedidoProduccionAggregate {  // ❌ Debería extender AggregateRoot
    
// 2. Usa strings en lugar de ValueObjects
private string $numeroPedido;  // ❌ Debería ser NumeroPedido VO
private string $cliente;       // ❌ Debería ser ClienteId o Entity
private string $estado;        // ❌ Debería ser Estado VO
private string $formaPago;     // ❌ Debería ser FormaPago VO

// 3. Detalles de negocio sin encapsulación
private int $asesorId;         // Número puro, sin VO
private int $cantidadTotal;    // Número puro, sin VO

// 4. Sin getters públicos para acceder a datos
// (El agregado está hermético, no puedes leer sus valores)
// No hay: getId(), getNumeroPedido(), getEstado(), etc.

// 5. Event Sourcing incompleto
// Dispara eventos pero no tiene forma de acceder al estado
// (No hay reconstitución desde eventos)
```

---

##  TABLA COMPARATIVA

| Aspecto | Pedidos/ | PedidoProduccion/ | Ganador |
|---------|----------|-------------------|---------|
| **Extiende AggregateRoot** |  SÍ | ❌ NO | **Pedidos/** |
| **ValueObjects** |  SÍ (NumeroPedido, Estado) | ❌ NO (strings) | **Pedidos/** |
| **Factory Methods** |  SÍ |  SÍ | EMPATE |
| **Validación de Invariantes** |  SÍ |  SÍ | EMPATE |
| **Event Sourcing** | ❌ NO (pero disponible) |  SÍ | **PedidoProduccion/** |
| **CQRS** |  SÍ (en Application) |  SÍ (Commands/Queries) | EMPATE |
| **Encapsulación de Datos** |  FUERTE | ❌ DÉBIL | **Pedidos/** |
| **Getters para acceso** |  SÍ | ❌ NO | **Pedidos/** |
| **Lógica de Dominio Clara** |  SÍ |  SÍ | EMPATE |
| **Estructura Limpia** |  SÍ | ⚠️ CONFUSA | **Pedidos/** |
| **Sigue patrones Laravel** |  SÍ | ⚠️ PARCIAL | **Pedidos/** |
| **Mantenibilidad** |  ALTA | ⚠️ MEDIA | **Pedidos/** |

---

## 🏆 **VEREDICTO: `Pedidos/` es la mejor**

### Razones:

#### 1. **Cumple MEJOR con DDD**
```
PedidoAggregate:
 Extiende AggregateRoot (patrón correcto)
 Usa ValueObjects (NumeroPedido, Estado)
 Encapsula datos privados
 Expone métodos de dominio
 Respeta límites del agregado

PedidoProduccionAggregate:
❌ No extiende AggregateRoot
❌ Usa strings en lugar de ValueObjects
❌ No tiene getters públicos
❌ Datos expuestos sin encapsulación
❌ No sigue patrones DDD estándar
```

#### 2. **Mejor Encapsulación**
```php
// Pedidos/ - CORRECTO
private Estado $estado;
public function confirmar(): void { ... }  // Transición segura

// PedidoProduccion/ - INCORRECTO
public string $estado;
// Cualquiera puede hacer: $agregado->estado = "INVALID";
```

#### 3. **ValueObjects en lugar de Strings**
```php
// Pedidos/ - CORRECTO
private NumeroPedido $numero;  // Validado, tipado, seguro

// PedidoProduccion/ - INCORRECTO
private string $numeroPedido;  // String sin validación
```

#### 4. **Mejor para Evolucionar**
```php
// Si necesitas agregar validación a "Estado":
// Pedidos/ - Cambias Estado VO y todo usa la nueva lógica
// PedidoProduccion/ - Necesitas cambiar toda la lógica de strings

// Si necesitas agregar información a "NumeroPedido":
// Pedidos/ - Cambias NumeroPedido VO y está centralizado
// PedidoProduccion/ - Es un string, no puedes agregar lógica
```

#### 5. **Alineada con Laravel + DDD**
```
Pedidos/ usa:
- Illuminate\Support\Collection (Laravel)
- AggregateRoot personalizado (estándar DDD)
- ValueObjects tipados (DDD)
- Sin magia, código explícito

PedidoProduccion/ usa:
- DomainEvent sin base class
- Strings en todo
- Patrones incompletos
```

---

## ⚠️ **¿Entonces por qué existe PedidoProduccion/?**

Posible razón histórica:
1. Se creó `PedidoProduccion/` primero (Event Sourcing)
2. Luego se creó `Pedidos/` con mejor arquitectura
3. Nunca se eliminó la vieja versión
4. Ambas coexisten causando confusión

**Evidencia:** Los controllers actuales importan de **ambos**:
```php
use App\Domain\PedidoProduccion\Queries\ObtenerPedidoQuery;      // De aquí
use App\Domain\PedidoProduccion\Commands\CrearPedidoCommand;     // De aquí
use App\Application\Pedidos\UseCases\ActualizarPrendaCompletaUseCase; // De aquí
```

---

##  **RECOMENDACIÓN FINAL**

### **CONSOLIDAR TODO EN `/app/Domain/Pedidos`**

**Plan:**
1.  Mantenemos `PedidoAggregate` de `Pedidos/` (mejor implementación)
2.  Migramos `LogoPedidoAggregate` y `PrendaPedidoAggregate` de `PedidoProduccion/`
3.  Migramos **Commands, Queries, Handlers** de `PedidoProduccion/` a `Pedidos/`
4.  Migramos **Services** de `PedidoProduccion/` a `Pedidos/`
5.  Migramos **Events, Listeners, Repositories** de `PedidoProduccion/` a `Pedidos/`
6.  Eliminamos completamente `/app/Domain/PedidoProduccion/`
7.  Actualizamos TODOS los imports (Controllers, Services, Tests, etc.)

**Resultado:**
```
Domain/Pedidos/
├── Aggregates/
│   ├── PedidoAggregate.php        ( MEJOR)
│   ├── LogoPedidoAggregate.php
│   └── PrendaPedidoAggregate.php
├── Commands/                       (Movidas de PedidoProduccion)
├── CommandHandlers/
├── Queries/
├── QueryHandlers/
├── Events/
├── Listeners/
├── Services/
├── Repositories/
├── Entities/
├── Exceptions/
├── ValueObjects/
└── Validators/
```

**Beneficios:**
-  Una sola fuente de verdad
-  Arquitectura DDD correcta
-  Sin confusiones de imports
-  Fácil de mantener
-  Sigue patrones reconocidos

---

## 📝 CONCLUSIÓN

**`/app/Domain/Pedidos` es la carpeta correcta según DDD** porque:
1. Extiende AggregateRoot (patrón DDD)
2. Usa ValueObjects (encapsulación)
3. Tiene mejor estructuras de datos
4. Es más mantenible y escalable
5. Sigue convenciones estándar de DDD

**Elimina `/app/Domain/PedidoProduccion/` es lo correcto**.
