# 🏗️ SOLUCIÓN ARQUITECTÓNICA DDD - UNIFICACIÓN DE DOMINIOS

> **Arquitectura propuesta por:** Senior Software Architect  
> **Fecha:** 2026-01-24  
> **Problema:** Conflicto entre `App\Domain\Pedidos` y `App\Domain\PedidoProduccion`  
> **Solución:** Bounded Context único con Aggregate Root unificado

---

## 📋 ÍNDICE

1. [Diagnóstico del Problema](#diagnóstico-del-problema)
2. [Decisión Arquitectónica](#decisión-arquitectónica)
3. [Diseño del Aggregate Root Unificado](#diseño-del-aggregate-root-unificado)
4. [Estructura de Directorios](#estructura-de-directorios)
5. [Commands y Handlers](#commands-y-handlers)
6. [Plan de Migración](#plan-de-migración)
7. [Código de Implementación](#código-de-implementación)

---

## 🔍 DIAGNÓSTICO DEL PROBLEMA

### Problemas Actuales

```plaintext
❌ PROBLEMA 1: Dominios Duplicados
├── App\Domain\Pedidos\Commands\CrearPedidoCommand
└── App\Domain\PedidoProduccion\Commands\CrearPedidoCommand
    └── ERROR: "No handler registrado para command: App\Domain\Pedidos\Commands\CrearPedidoCommand"

❌ PROBLEMA 2: Namespace Confusion
├── CQRSServiceProvider registra: PedidoProduccion\Commands\CrearPedidoCommand
├── CrearPedidoCompletoHandler usa: Pedidos\Commands\CrearPedidoCommand
└── CommandBus NO encuentra el handler porque busca en namespace equivocado

❌ PROBLEMA 3: Violación de DDD
├── Un solo concepto de negocio (Pedido)
├── Representado en dos Bounded Contexts diferentes
└── Genera ambigüedad, duplicación y errores en tiempo de ejecución

❌ PROBLEMA 4: Violación de Single Responsibility
├── PedidoProduccion contiene lógica de creación
├── Pedidos también contiene lógica de creación
└── No hay un único Aggregate Root que controle invariantes
```

### Análisis de Logs

```log
[2026-01-24 10:06:31] CommandBus: Handler registrado 
  - command: "App\\Domain\\PedidoProduccion\\Commands\\CrearPedidoCommand"
  - handler: "App\\Domain\\PedidoProduccion\\CommandHandlers\\CrearPedidoHandler"

[2026-01-24 10:06:31] CommandBus: Handler registrado 
  - command: "App\\Domain\\Pedidos\\Commands\\CrearPedidoCompletoCommand"
  - handler: "App\\Domain\\Pedidos\\CommandHandlers\\CrearPedidoCompletoHandler"

[2026-01-24 10:06:31] ERROR: ❌ [CrearPedidoCompletoHandler] Error
  - error: "No handler registrado para command: App\\Domain\\Pedidos\\Commands\\CrearPedidoCommand"
```

**CAUSA RAÍZ:**  
`CrearPedidoCompletoHandler` (namespace `Pedidos`) intenta ejecutar `CrearPedidoCommand` (namespace `Pedidos`), pero el CommandBus solo tiene registrado `CrearPedidoCommand` del namespace `PedidoProduccion`.

---

## ✅ DECISIÓN ARQUITECTÓNICA

### 1️⃣ ¿Un Solo Dominio o Submódulos?

**DECISIÓN: UN SOLO BOUNDED CONTEXT LLAMADO `Pedidos`**

#### Justificación DDD

| Criterio | Análisis | Decisión |
|----------|----------|----------|
| **Ubiquitous Language** | El negocio habla de "UN pedido" que pasa por estados | ✅ Un solo dominio |
| **Transactional Boundary** | Pedido y Producción deben ser consistentes en la misma transacción | ✅ Mismo Aggregate |
| **Invariants** | Las reglas de negocio del pedido comercial afectan la producción | ✅ Un solo Aggregate Root |
| **Lifecycle** | Un pedido NUNCA existe sin su información productiva | ✅ Un solo ciclo de vida |
| **Domain Events** | Los eventos (PedidoCreado, PedidoEnProduccion) pertenecen al mismo contexto | ✅ Un solo dominio |

#### Contexto de Negocio Real

```plaintext
FLUJO DE NEGOCIO (ÚNICO):
┌────────────────────────────────────────────────────────────┐
│ 1. Asesora toma pedido del cliente                        │
│    └─> Estado: "cotizado"                                 │
│                                                            │
│ 2. Cliente aprueba cotización                             │
│    └─> Estado: "aprobado"                                 │
│                                                            │
│ 3. Pedido entra a producción                              │
│    └─> Estado: "en_produccion"                            │
│    └─> Se asignan procesos (corte, bordado, estampado)    │
│                                                            │
│ 4. Producción se completa                                 │
│    └─> Estado: "produccion_completada"                    │
│                                                            │
│ 5. Se despacha al cliente                                 │
│    └─> Estado: "despachado"                               │
└────────────────────────────────────────────────────────────┘

⚠️ ES UN SOLO PEDIDO EN DIFERENTES ESTADOS, NO DOS PEDIDOS DISTINTOS
```

**CONCLUSIÓN:**  
No tiene sentido separar `Pedidos` de `PedidoProduccion` porque:
- No son dos entidades de negocio distintas
- No tienen lifecycles independientes
- No pueden existir de forma aislada
- Comparten el mismo conjunto de invariantes

---

## 🎯 DISEÑO DEL AGGREGATE ROOT UNIFICADO

### Aggregate Root: `Pedido`

```php
<?php

namespace App\Domain\Pedidos\Aggregates;

use App\Domain\Pedidos\ValueObjects\NumeroPedido;
use App\Domain\Pedidos\ValueObjects\Estado;
use App\Domain\Pedidos\Entities\Prenda;
use App\Domain\Pedidos\Entities\Epp;
use App\Domain\Pedidos\Events\PedidoCreado;
use App\Domain\Pedidos\Events\PedidoAprobado;
use App\Domain\Pedidos\Events\PedidoEnProduccion;
use App\Domain\Pedidos\Exceptions\EstadoInvalidoException;
use App\Domain\Shared\AggregateRoot;

/**
 * Pedido - Aggregate Root
 * 
 * Responsabilidades:
 * - Controlar TODOS los invariantes del pedido
 * - Gestionar ciclo de vida completo (comercial → producción → despacho)
 * - Proteger consistencia de prendas, tallas, variaciones
 * - Emitir Domain Events
 * - Garantizar reglas de negocio (estados válidos, cantidades, etc.)
 * 
 * Entidades Hijas (dentro del Aggregate):
 * - Prenda (Value Object compuesto)
 * - Talla (Value Object)
 * - Variacion (Value Object)
 * - ProcesoProductivo (Entity)
 * - Epp (Entity)
 * 
 * Estados del Pedido:
 * - cotizado → aprobado → en_produccion → produccion_completada → despachado
 */
class Pedido extends AggregateRoot
{
    private NumeroPedido $numeroPedido;
    private Estado $estado;
    private int $clienteId;
    private string $formaPago;
    private int $asesorId;
    private array $prendas = []; // Prenda[]
    private array $epps = [];    // Epp[]
    private int $cantidadTotal = 0;
    private ?\DateTimeImmutable $fechaAprobacion = null;
    private ?\DateTimeImmutable $fechaInicioProduccion = null;
    private ?\DateTimeImmutable $fechaFinProduccion = null;
    private ?\DateTimeImmutable $fechaDespacho = null;

    /**
     * Constructor privado - usar Named Constructors
     */
    private function __construct(
        NumeroPedido $numeroPedido,
        int $clienteId,
        string $formaPago,
        int $asesorId,
    ) {
        $this->numeroPedido = $numeroPedido;
        $this->estado = Estado::cotizado();
        $this->clienteId = $clienteId;
        $this->formaPago = $formaPago;
        $this->asesorId = $asesorId;
        
        // Emitir evento de dominio
        $this->recordEvent(new PedidoCreado(
            numeroPedido: $this->numeroPedido->valor(),
            clienteId: $clienteId,
            asesorId: $asesorId,
        ));
    }

    /**
     * Named Constructor: Crear nuevo pedido
     */
    public static function crear(
        NumeroPedido $numeroPedido,
        int $clienteId,
        string $formaPago,
        int $asesorId,
    ): self {
        return new self($numeroPedido, $clienteId, $formaPago, $asesorId);
    }

    /**
     * Agregar prenda al pedido
     * 
     * INVARIANTE: Solo se pueden agregar prendas si estado es "cotizado"
     */
    public function agregarPrenda(Prenda $prenda): void
    {
        if (!$this->estado->esCotizado() && !$this->estado->esAprobado()) {
            throw EstadoInvalidoException::noPuedeAgregarPrendas($this->estado);
        }

        $this->prendas[] = $prenda;
        $this->recalcularCantidadTotal();
    }

    /**
     * Aprobar pedido
     * 
     * INVARIANTE: Solo se puede aprobar si hay al menos una prenda
     * INVARIANTE: Solo se puede aprobar desde estado "cotizado"
     */
    public function aprobar(): void
    {
        if ($this->estado->esAprobado()) {
            throw EstadoInvalidoException::yaEstaAprobado();
        }

        if (!$this->estado->esCotizado()) {
            throw EstadoInvalidoException::noPuedeAprobar($this->estado);
        }

        if (empty($this->prendas)) {
            throw new \DomainException('No se puede aprobar un pedido sin prendas');
        }

        $this->estado = Estado::aprobado();
        $this->fechaAprobacion = new \DateTimeImmutable();
        
        $this->recordEvent(new PedidoAprobado(
            numeroPedido: $this->numeroPedido->valor(),
            fechaAprobacion: $this->fechaAprobacion,
        ));
    }

    /**
     * Iniciar producción
     * 
     * INVARIANTE: Solo desde estado "aprobado"
     */
    public function iniciarProduccion(): void
    {
        if (!$this->estado->esAprobado()) {
            throw EstadoInvalidoException::noPuedeIniciarProduccion($this->estado);
        }

        $this->estado = Estado::enProduccion();
        $this->fechaInicioProduccion = new \DateTimeImmutable();
        
        $this->recordEvent(new PedidoEnProduccion(
            numeroPedido: $this->numeroPedido->valor(),
            fechaInicio: $this->fechaInicioProduccion,
            prendas: $this->prendas,
        ));
    }

    /**
     * Completar producción
     */
    public function completarProduccion(): void
    {
        if (!$this->estado->esEnProduccion()) {
            throw EstadoInvalidoException::noPuedeCompletarProduccion($this->estado);
        }

        $this->estado = Estado::produccionCompletada();
        $this->fechaFinProduccion = new \DateTimeImmutable();
    }

    /**
     * Despachar pedido
     */
    public function despachar(): void
    {
        if (!$this->estado->esProduccionCompletada()) {
            throw EstadoInvalidoException::noPuedeDespachar($this->estado);
        }

        $this->estado = Estado::despachado();
        $this->fechaDespacho = new \DateTimeImmutable();
    }

    /**
     * Agregar EPP al pedido
     */
    public function agregarEpp(Epp $epp): void
    {
        if ($this->estado->esDespachado()) {
            throw EstadoInvalidoException::noPuedeModificarPedidoDespachado();
        }

        $this->epps[] = $epp;
    }

    /**
     * Recalcular cantidad total de prendas
     */
    private function recalcularCantidadTotal(): void
    {
        $this->cantidadTotal = array_reduce(
            $this->prendas,
            fn($total, $prenda) => $total + $prenda->cantidadTotal(),
            0
        );
    }

    // ===== GETTERS =====
    
    public function numeroPedido(): string
    {
        return $this->numeroPedido->valor();
    }

    public function estado(): Estado
    {
        return $this->estado;
    }

    public function clienteId(): int
    {
        return $this->clienteId;
    }

    public function prendas(): array
    {
        return $this->prendas;
    }

    public function epps(): array
    {
        return $this->epps;
    }

    public function cantidadTotal(): int
    {
        return $this->cantidadTotal;
    }

    public function estaCotizado(): bool
    {
        return $this->estado->esCotizado();
    }

    public function estaAprobado(): bool
    {
        return $this->estado->esAprobado();
    }

    public function estaEnProduccion(): bool
    {
        return $this->estado->esEnProduccion();
    }

    public function estaDespachado(): bool
    {
        return $this->estado->esDespachado();
    }
}
```

---

## 📁 ESTRUCTURA DE DIRECTORIOS UNIFICADA

```plaintext
app/Domain/Pedidos/
├── Aggregates/
│   └── Pedido.php ⭐ (ÚNICO AGGREGATE ROOT)
│
├── Entities/
│   ├── Prenda.php
│   ├── ProcesoProductivo.php
│   └── Epp.php
│
├── ValueObjects/
│   ├── NumeroPedido.php
│   ├── Estado.php
│   ├── Talla.php
│   ├── Variacion.php
│   ├── TipoPrenda.php
│   └── FormaPago.php
│
├── Commands/
│   ├── CrearPedidoCommand.php ⭐ (ÚNICO)
│   ├── AgregarPrendaCommand.php
│   ├── AprobarPedidoCommand.php
│   ├── IniciarProduccionCommand.php
│   ├── CompletarProduccionCommand.php
│   ├── DespacharPedidoCommand.php
│   └── AgregarEppCommand.php
│
├── CommandHandlers/
│   ├── CrearPedidoHandler.php ⭐ (ÚNICO)
│   ├── AgregarPrendaHandler.php
│   ├── AprobarPedidoHandler.php
│   ├── IniciarProduccionHandler.php
│   ├── CompletarProduccionHandler.php
│   ├── DespacharPedidoHandler.php
│   └── AgregarEppHandler.php
│
├── Queries/
│   ├── ObtenerPedidoQuery.php
│   ├── ListarPedidosQuery.php
│   ├── FiltrarPorEstadoQuery.php
│   └── BuscarPorNumeroQuery.php
│
├── QueryHandlers/
│   ├── ObtenerPedidoHandler.php
│   ├── ListarPedidosHandler.php
│   ├── FiltrarPorEstadoHandler.php
│   └── BuscarPorNumeroHandler.php
│
├── Events/
│   ├── PedidoCreado.php
│   ├── PedidoAprobado.php
│   ├── PedidoEnProduccion.php
│   ├── PedidoCompletado.php
│   └── PedidoDespachado.php
│
├── Repositories/
│   └── PedidoRepository.php
│
├── Services/
│   ├── GeneradorNumeroPedido.php
│   └── CalculadorCostoProduccion.php
│
├── Strategies/ (Mantener las existentes)
│   ├── CreacionPrendaSinCtaStrategy.php
│   └── CreacionPrendaReflectivoStrategy.php
│
├── Validators/
│   ├── PedidoValidator.php
│   └── PrendaValidator.php
│
└── Exceptions/
    ├── PedidoNoEncontrado.php
    ├── EstadoInvalidoException.php
    └── PrendaInvalidaException.php

❌ ELIMINAR COMPLETAMENTE:
app/Domain/PedidoProduccion/ (TODO EL DIRECTORIO)
```

---

## 🔧 COMMANDS Y HANDLERS UNIFICADOS

### Command: CrearPedidoCommand (ÚNICO)

```php
<?php

namespace App\Domain\Pedidos\Commands;

use App\Domain\Shared\CQRS\Command;

/**
 * CrearPedidoCommand
 * 
 * Command para crear un nuevo pedido completo
 * 
 * Este es el ÚNICO command de creación de pedidos en el sistema
 * Reemplaza tanto CrearPedidoCommand como CrearPedidoCompletoCommand
 */
class CrearPedidoCommand implements Command
{
    public function __construct(
        private int $clienteId,
        private string $formaPago,
        private int $asesorId,
        private array $prendas = [],      // Array de datos de prendas
        private array $epps = [],         // Array de EPPs opcionales
        private ?string $observaciones = null,
    ) {}

    public function clienteId(): int
    {
        return $this->clienteId;
    }

    public function formaPago(): string
    {
        return $this->formaPago;
    }

    public function asesorId(): int
    {
        return $this->asesorId;
    }

    public function prendas(): array
    {
        return $this->prendas;
    }

    public function epps(): array
    {
        return $this->epps;
    }

    public function observaciones(): ?string
    {
        return $this->observaciones;
    }
}
```

### Handler: CrearPedidoHandler (ÚNICO)

```php
<?php

namespace App\Domain\Pedidos\CommandHandlers;

use App\Domain\Shared\CQRS\Command;
use App\Domain\Shared\CQRS\CommandHandler;
use App\Domain\Pedidos\Commands\CrearPedidoCommand;
use App\Domain\Pedidos\Aggregates\Pedido;
use App\Domain\Pedidos\Entities\Prenda;
use App\Domain\Pedidos\ValueObjects\NumeroPedido;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Services\GeneradorNumeroPedido;
use App\Domain\Pedidos\Strategies\CreacionPrendaStrategyFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CrearPedidoHandler
 * 
 * Handler ÚNICO para creación de pedidos
 * 
 * Responsabilidades:
 * - Generar número de pedido único
 * - Crear Aggregate Root Pedido
 * - Agregar prendas usando Strategies
 * - Persistir todo en una transacción
 * - Emitir eventos de dominio
 */
class CrearPedidoHandler implements CommandHandler
{
    public function __construct(
        private PedidoRepository $pedidoRepository,
        private GeneradorNumeroPedido $generadorNumero,
        private CreacionPrendaStrategyFactory $strategyFactory,
    ) {}

    public function handle(Command $command): mixed
    {
        if (!$command instanceof CrearPedidoCommand) {
            throw new \InvalidArgumentException('Command debe ser CrearPedidoCommand');
        }

        return DB::transaction(function () use ($command) {
            Log::info('⚡ [CrearPedidoHandler] Iniciando creación de pedido', [
                'cliente_id' => $command->clienteId(),
                'prendas_count' => count($command->prendas()),
            ]);

            // 1. Generar número de pedido
            $numeroPedido = $this->generadorNumero->generar();
            
            // 2. Crear Aggregate Root
            $pedido = Pedido::crear(
                numeroPedido: new NumeroPedido($numeroPedido),
                clienteId: $command->clienteId(),
                formaPago: $command->formaPago(),
                asesorId: $command->asesorId(),
            );

            // 3. Agregar prendas usando Strategy Pattern
            foreach ($command->prendas() as $prendaData) {
                $strategy = $this->strategyFactory->crear($prendaData);
                $prenda = $strategy->crearPrenda($prendaData);
                $pedido->agregarPrenda($prenda);
            }

            // 4. Agregar EPPs si existen
            foreach ($command->epps() as $eppData) {
                // Lógica para EPPs
            }

            // 5. Persistir Aggregate Root
            $pedidoGuardado = $this->pedidoRepository->guardar($pedido);

            // 6. Despachar eventos de dominio
            $this->dispatchEvents($pedido->releaseEvents());

            Log::info('✅ [CrearPedidoHandler] Pedido creado exitosamente', [
                'numero_pedido' => $pedido->numeroPedido(),
                'id' => $pedidoGuardado->id,
            ]);

            return $pedidoGuardado;
        });
    }

    private function dispatchEvents(array $events): void
    {
        foreach ($events as $event) {
            event($event);
        }
    }
}
```

---

## 🔄 PLAN DE MIGRACIÓN (6 FASES)

### FASE 1: Preparación (1-2 días)

✅ **Objetivo:** Crear nueva estructura sin romper lo existente

```bash
# 1. Crear rama de migración
git checkout -b refactor/unificar-dominio-pedidos

# 2. Backup de archivos actuales
cp -r app/Domain/Pedidos app/Domain/Pedidos.backup
cp -r app/Domain/PedidoProduccion app/Domain/PedidoProduccion.backup

# 3. Crear nuevo Aggregate Root
touch app/Domain/Pedidos/Aggregates/Pedido.php

# 4. Crear nuevos Commands unificados
touch app/Domain/Pedidos/Commands/CrearPedidoCommand.php
```

### FASE 2: Implementación del Aggregate Root (2-3 días)

✅ Implementar `Pedido` Aggregate Root completo
✅ Implementar Value Objects (`Estado`, `NumeroPedido`, etc.)
✅ Implementar Entities (`Prenda`, `ProcesoProductivo`)
✅ Crear tests unitarios del Aggregate

### FASE 3: Migración de Commands y Handlers (2-3 días)

✅ Crear nuevo `CrearPedidoCommand` unificado
✅ Crear nuevo `CrearPedidoHandler` unificado
✅ Migrar lógica de Strategies (ya existentes, reusar)
✅ Actualizar `CQRSServiceProvider`

### FASE 4: Migración de Controllers (1 día)

✅ Actualizar `CrearPedidoEditableController`
✅ Cambiar referencias de namespace
✅ Eliminar uso de Commands antiguos

### FASE 5: Testing Completo (2 días)

✅ Tests unitarios del Aggregate
✅ Tests de integración de Commands
✅ Tests E2E de creación de pedidos
✅ Verificar que no hay regressions

### FASE 6: Limpieza y Eliminación (1 día)

✅ Eliminar `app/Domain/PedidoProduccion/`
✅ Eliminar Commands duplicados en `Pedidos/`
✅ Actualizar imports en todo el proyecto
✅ Limpiar ServiceProvider

---

## 💻 CÓDIGO DE IMPLEMENTACIÓN INMEDIATA

### CQRSServiceProvider - Versión Corregida

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Shared\CQRS\QueryBus;
use App\Domain\Shared\CQRS\CommandBus;

// ===== PEDIDOS DOMAIN =====
use App\Domain\Pedidos\Commands\CrearPedidoCommand;
use App\Domain\Pedidos\Commands\AprobarPedidoCommand;
use App\Domain\Pedidos\Commands\IniciarProduccionCommand;
use App\Domain\Pedidos\Commands\AgregarPrendaCommand;

use App\Domain\Pedidos\CommandHandlers\CrearPedidoHandler;
use App\Domain\Pedidos\CommandHandlers\AprobarPedidoHandler;
use App\Domain\Pedidos\CommandHandlers\IniciarProduccionHandler;
use App\Domain\Pedidos\CommandHandlers\AgregarPrendaHandler;

use App\Domain\Pedidos\Queries\ObtenerPedidoQuery;
use App\Domain\Pedidos\Queries\ListarPedidosQuery;

use App\Domain\Pedidos\QueryHandlers\ObtenerPedidoHandler;
use App\Domain\Pedidos\QueryHandlers\ListarPedidosHandler;

// ===== EPP DOMAIN =====
use App\Domain\Epp\Commands\AgregarEppCommand;
use App\Domain\Epp\CommandHandlers\AgregarEppHandler;

class CQRSServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // QueryBus
        $this->app->singleton(QueryBus::class, function ($app) {
            return new QueryBus($app);
        });

        // CommandBus
        $this->app->singleton(CommandBus::class, function ($app) {
            return new CommandBus($app);
        });
    }

    public function boot(): void
    {
        $queryBus = $this->app->make(QueryBus::class);
        $commandBus = $this->app->make(CommandBus::class);

        $this->registerQueries($queryBus);
        $this->registerCommands($commandBus);
    }

    private function registerCommands(CommandBus $commandBus): void
    {
        // PEDIDOS - ÚNICO NAMESPACE
        $commandBus->register(
            CrearPedidoCommand::class,
            CrearPedidoHandler::class
        );

        $commandBus->register(
            AprobarPedidoCommand::class,
            AprobarPedidoHandler::class
        );

        $commandBus->register(
            IniciarProduccionCommand::class,
            IniciarProduccionHandler::class
        );

        // EPP
        $commandBus->register(
            AgregarEppCommand::class,
            AgregarEppHandler::class
        );
    }

    private function registerQueries(QueryBus $queryBus): void
    {
        $queryBus->register(
            ObtenerPedidoQuery::class,
            ObtenerPedidoHandler::class
        );

        $queryBus->register(
            ListarPedidosQuery::class,
            ListarPedidosHandler::class
        );
    }
}
```

---

## 🎯 RESUMEN EJECUTIVO

### ✅ Decisión Final

**UN SOLO BOUNDED CONTEXT:** `App\Domain\Pedidos`

**RAZONES:**
1. ✅ Un pedido comercial y un pedido productivo son EL MISMO PEDIDO
2. ✅ Comparten ciclo de vida, invariantes y transacciones
3. ✅ Evita duplicación de Commands/Handlers
4. ✅ Simplifica mantenimiento
5. ✅ Respeta DDD (un Aggregate Root por concepto de negocio)

### ✅ Aggregate Root Único

```plaintext
Pedido (Aggregate Root)
├── NumeroPedido (Value Object)
├── Estado (Value Object: cotizado → aprobado → en_produccion → despachado)
├── Cliente (referencia)
├── Asesor (referencia)
├── Prendas[] (Entities dentro del Aggregate)
├── EPPs[] (Entities dentro del Aggregate)
└── Eventos (PedidoCreado, PedidoAprobado, etc.)
```

### ✅ Eliminación

```plaintext
❌ ELIMINAR:
   - app/Domain/PedidoProduccion/ (COMPLETO)
   - app/Domain/Pedidos/Commands/CrearPedidoCompletoCommand.php
   - app/Domain/Pedidos/CommandHandlers/CrearPedidoCompletoHandler.php

✅ MANTENER Y UNIFICAR:
   - app/Domain/Pedidos/ (ÚNICO DOMINIO)
   - CrearPedidoCommand (ÚNICO)
   - CrearPedidoHandler (ÚNICO)
```

---

## 📞 PRÓXIMOS PASOS

1. ¿Apruebas este diseño arquitectónico?
2. ¿Quieres que implemente el código completo del Aggregate Root?
3. ¿Necesitas ayuda con la migración de datos existentes?
4. ¿Requieres documentación adicional de algún componente?

---

**FIN DEL DOCUMENTO**
