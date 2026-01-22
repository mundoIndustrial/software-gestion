# 📘 GUÍA PRÁCTICA: DDD PARA PEDIDOS

**Fecha:** 22/01/2026  
**Objetivo:** Implementar correctamente DDD solo para el módulo de Pedidos  
**Tiempo estimado:** 3-4 días de trabajo

---

## 📁 ESTRUCTURA DE CARPETAS A CREAR

```
app/
├── Domain/
│   └── Pedidos/                          ← 🆕 CREAR ESTA CARPETA
│       ├── Agregado/
│       │   └── PedidoAggregate.php       ← 🆕 Raíz del agregado
│       ├── Entities/
│       │   └── PrendaPedido.php          ← 🆕 Entidad dentro del agregado
│       ├── ValueObjects/
│       │   ├── NumeroPedido.php          ← 🆕 Valor immutable
│       │   ├── Estado.php                ← 🆕 Enum-like
│       │   └── DescripcionPrendaPedido.php
│       ├── Repositories/
│       │   └── PedidoRepository.php      ← 🆕 Interface
│       ├── Services/
│       │   └── CalculadorPedidoService.php
│       ├── Events/
│       │   ├── PedidoCreado.php          ← 🆕 Domain Event
│       │   ├── PedidoActualizado.php
│       │   └── PedidoEliminado.php
│       └── Exceptions/
│           ├── PedidoNoEncontrado.php
│           └── EstadoPedidoInvalido.php
│
├── Application/
│   └── Pedidos/                          ← 🆕 CREAR ESTA CARPETA
│       ├── UseCases/
│       │   ├── CrearPedidoUseCase.php    ← 🆕 Orquestador
│       │   ├── ActualizarPedidoUseCase.php
│       │   ├── EliminarPedidoUseCase.php
│       │   └── ObtenerPedidoUseCase.php
│       ├── DTOs/
│       │   ├── CrearPedidoDTO.php        ← 🆕 Input
│       │   ├── ActualizarPedidoDTO.php
│       │   └── PedidoResponseDTO.php     ← 🆕 Output
│       └── Listeners/
│           └── PedidoCreadoListener.php  ← 🆕 Reacciona a eventos
│
├── Infrastructure/
│   ├── Persistence/
│   │   └── Eloquent/
│   │       └── PedidoRepositoryImpl.php   ← 🆕 Implementación
│   └── Providers/
│       └── PedidoServiceProvider.php     ← 🆕 Bindings
│
└── Http/
    └── Controllers/
        └── PedidoController.php          ← Refactorizado para usar UseCases
```

---

## 🎯 PASO 1: CREAR VALUE OBJECTS

### 1.1 `app/Domain/Pedidos/ValueObjects/NumeroPedido.php`

```php
<?php

namespace App\Domain\Pedidos\ValueObjects;

/**
 * Value Object: NumeroPedido
 * 
 * Representa el número único de un pedido
 * - Immutable
 * - Validado
 * - Único en el dominio
 */
class NumeroPedido
{
    private string $valor;

    /**
     * Constructor privado - usar factory methods
     */
    private function __construct(string $valor)
    {
        $this->validar($valor);
        $this->valor = $valor;
    }

    /**
     * Factory: Crear desde string
     */
    public static function desde(string $valor): self
    {
        return new self($valor);
    }

    /**
     * Factory: Generar nuevo automáticamente
     */
    public static function generar(): self
    {
        $numero = 'PED-' . date('YmdHis') . '-' . rand(1000, 9999);
        return new self($numero);
    }

    /**
     * Validar que el número sea válido
     */
    private function validar(string $valor): void
    {
        if (empty($valor)) {
            throw new \InvalidArgumentException('Número de pedido no puede estar vacío');
        }

        if (strlen($valor) > 50) {
            throw new \InvalidArgumentException('Número de pedido muy largo (máx 50 caracteres)');
        }

        // Validar formato si lo requiere
        if (!preg_match('/^[A-Z0-9\-]+$/', $valor)) {
            throw new \InvalidArgumentException('Número de pedido contiene caracteres inválidos');
        }
    }

    /**
     * Obtener el valor
     */
    public function valor(): string
    {
        return $this->valor;
    }

    /**
     * Comparar con otro NumeroPedido
     */
    public function esIgualA(NumeroPedido $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    /**
     * Representación en string
     */
    public function __toString(): string
    {
        return $this->valor;
    }
}
```

### 1.2 `app/Domain/Pedidos/ValueObjects/Estado.php`

```php
<?php

namespace App\Domain\Pedidos\ValueObjects;

/**
 * Value Object: Estado
 * 
 * Estados válidos de un pedido
 * Define transiciones permitidas
 */
class Estado
{
    public const PENDIENTE = 'PENDIENTE';
    public const CONFIRMADO = 'CONFIRMADO';
    public const EN_PRODUCCION = 'EN_PRODUCCION';
    public const COMPLETADO = 'COMPLETADO';
    public const CANCELADO = 'CANCELADO';

    private string $valor;

    /**
     * Estados válidos permitidos
     */
    private static array $estadosValidos = [
        self::PENDIENTE,
        self::CONFIRMADO,
        self::EN_PRODUCCION,
        self::COMPLETADO,
        self::CANCELADO,
    ];

    /**
     * Transiciones permitidas
     */
    private static array $transicionesPermitidas = [
        self::PENDIENTE => [self::CONFIRMADO, self::CANCELADO],
        self::CONFIRMADO => [self::EN_PRODUCCION, self::CANCELADO],
        self::EN_PRODUCCION => [self::COMPLETADO],
        self::COMPLETADO => [],
        self::CANCELADO => [],
    ];

    private function __construct(string $valor)
    {
        $this->validar($valor);
        $this->valor = $valor;
    }

    /**
     * Factory
     */
    public static function desde(string $valor): self
    {
        return new self($valor);
    }

    /**
     * Factory: Estado inicial
     */
    public static function inicial(): self
    {
        return new self(self::PENDIENTE);
    }

    /**
     * Validar
     */
    private function validar(string $valor): void
    {
        if (!in_array($valor, self::$estadosValidos)) {
            throw new \InvalidArgumentException(
                "Estado '$valor' inválido. Válidos: " . implode(', ', self::$estadosValidos)
            );
        }
    }

    /**
     * ¿Puede transicionar a este nuevo estado?
     */
    public function puedeSeguirA(Estado $nuevoEstado): bool
    {
        $transicionesPermitidas = self::$transicionesPermitidas[$this->valor] ?? [];
        return in_array($nuevoEstado->valor, $transicionesPermitidas);
    }

    /**
     * Transicionar a nuevo estado
     */
    public function transicionarA(Estado $nuevoEstado): void
    {
        if (!$this->puedeSeguirA($nuevoEstado)) {
            throw new \DomainException(
                "No se puede pasar de {$this->valor} a {$nuevoEstado->valor}"
            );
        }
    }

    /**
     * ¿Es estado final?
     */
    public function esFinal(): bool
    {
        return $this->valor === self::COMPLETADO || $this->valor === self::CANCELADO;
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function esIgualA(Estado $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
```

---

## 🎯 PASO 2: CREAR ENTIDADES DENTRO DEL AGREGADO

### 2.1 `app/Domain/Pedidos/Entities/PrendaPedido.php`

```php
<?php

namespace App\Domain\Pedidos\Entities;

use App\Domain\Shared\Entity;

/**
 * Entidad: PrendaPedido
 * 
 * Una prenda dentro de un pedido
 * Vive dentro del agregado Pedido (no es agregado raíz)
 */
class PrendaPedido extends Entity
{
    private int $pedidoId;
    private int $prendaId;
    private string $descripcion;
    private int $cantidad;
    private ?string $observaciones;
    private array $tallas; // Relacional: ['DAMA' => ['S' => 10], 'CABALLERO' => ['32' => 5]]

    public function __construct(
        ?int $id,
        int $pedidoId,
        int $prendaId,
        string $descripcion,
        int $cantidad,
        array $tallas,
        ?string $observaciones = null
    ) {
        parent::__construct($id);
        $this->validar($cantidad, $tallas);
        $this->pedidoId = $pedidoId;
        $this->prendaId = $prendaId;
        $this->descripcion = $descripcion;
        $this->cantidad = $cantidad;
        $this->tallas = $tallas;
        $this->observaciones = $observaciones;
    }

    /**
     * Validar
     */
    private function validar(int $cantidad, array $tallas): void
    {
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('Cantidad debe ser mayor a 0');
        }

        $totalTallas = array_sum(
            array_map(fn($generos) => array_sum($generos), $tallas)
        );

        if ($totalTallas !== $cantidad) {
            throw new \InvalidArgumentException(
                "Total de tallas ($totalTallas) no coincide con cantidad ($cantidad)"
            );
        }
    }

    /**
     * Getters
     */
    public function pedidoId(): int { return $this->pedidoId; }
    public function prendaId(): int { return $this->prendaId; }
    public function descripcion(): string { return $this->descripcion; }
    public function cantidad(): int { return $this->cantidad; }
    public function tallas(): array { return $this->tallas; }
    public function observaciones(): ?string { return $this->observaciones; }

    /**
     * Para persistencia
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'pedido_id' => $this->pedidoId,
            'prenda_id' => $this->prendaId,
            'descripcion' => $this->descripcion,
            'cantidad' => $this->cantidad,
            'tallas' => json_encode($this->tallas),
            'observaciones' => $this->observaciones,
        ];
    }
}
```

---

## 🎯 PASO 3: CREAR EL AGREGADO RAÍZ

### 3.1 `app/Domain/Pedidos/Agregado/PedidoAggregate.php`

```php
<?php

namespace App\Domain\Pedidos\Agregado;

use App\Domain\Shared\AggregateRoot;
use App\Domain\Pedidos\ValueObjects\NumeroPedido;
use App\Domain\Pedidos\ValueObjects\Estado;
use App\Domain\Pedidos\Entities\PrendaPedido;
use App\Domain\Pedidos\Events\PedidoCreado;
use App\Domain\Pedidos\Events\PedidoActualizado;
use App\Domain\Pedidos\Events\EstadoPedidoCambiado;
use Illuminate\Support\Collection;

/**
 * Agregado Raíz: PedidoAggregate
 * 
 * Encapsula toda la lógica del pedido
 * - Crea pedidos válidos
 * - Gestiona estado
 * - Valida cambios
 * - Dispara eventos
 */
class PedidoAggregate extends AggregateRoot
{
    private NumeroPedido $numero;
    private int $clienteId;
    private Estado $estado;
    private string $descripcion;
    private ?string $observaciones;
    private Collection $prendas;
    private \DateTime $fechaCreacion;
    private ?\DateTime $fechaActualizacion;

    /**
     * Constructor privado - usar factory methods
     */
    private function __construct(
        ?int $id,
        NumeroPedido $numero,
        int $clienteId,
        Estado $estado,
        string $descripcion,
        Collection $prendas,
        \DateTime $fechaCreacion,
        ?string $observaciones = null,
        ?\DateTime $fechaActualizacion = null
    ) {
        parent::__construct($id);
        $this->numero = $numero;
        $this->clienteId = $clienteId;
        $this->estado = $estado;
        $this->descripcion = $descripcion;
        $this->prendas = $prendas;
        $this->observaciones = $observaciones;
        $this->fechaCreacion = $fechaCreacion;
        $this->fechaActualizacion = $fechaActualizacion ?? $fechaCreacion;
    }

    /**
     * Factory: Crear nuevo pedido
     */
    public static function crear(
        int $clienteId,
        string $descripcion,
        array $prendasData,
        ?string $observaciones = null
    ): self {
        // Validar datos básicos
        if ($clienteId <= 0) {
            throw new \InvalidArgumentException('Cliente ID inválido');
        }

        if (empty($descripcion)) {
            throw new \InvalidArgumentException('Descripción es requerida');
        }

        if (empty($prendasData)) {
            throw new \InvalidArgumentException('Pedido debe tener al menos una prenda');
        }

        // Crear agregado
        $numeroGenerado = NumeroPedido::generar();
        $estadoInicial = Estado::inicial();
        $prendas = collect();
        
        // Crear prendas
        foreach ($prendasData as $prendaData) {
            $prenda = new PrendaPedido(
                id: null,
                pedidoId: 0, // Se asignará después
                prendaId: $prendaData['prenda_id'],
                descripcion: $prendaData['descripcion'],
                cantidad: $prendaData['cantidad'],
                tallas: $prendaData['tallas'],
                observaciones: $prendaData['observaciones'] ?? null
            );
            $prendas->push($prenda);
        }

        $pedido = new self(
            id: null,
            numero: $numeroGenerado,
            clienteId: $clienteId,
            estado: $estadoInicial,
            descripcion: $descripcion,
            prendas: $prendas,
            fechaCreacion: new \DateTime(),
            observaciones: $observaciones
        );

        // Disparar evento
        $pedido->agregarEvento(
            new PedidoCreado(
                pedidoId: null, // Se asignará después
                numero: (string)$numeroGenerado,
                clienteId: $clienteId,
                descripcion: $descripcion,
                totalPrendas: $prendas->count()
            )
        );

        return $pedido;
    }

    /**
     * Factory: Reconstruir desde BD (Hydrate)
     */
    public static function reconstruir(
        int $id,
        NumeroPedido $numero,
        int $clienteId,
        Estado $estado,
        string $descripcion,
        array $prendas,
        \DateTime $fechaCreacion,
        ?string $observaciones = null,
        ?\DateTime $fechaActualizacion = null
    ): self {
        return new self(
            id: $id,
            numero: $numero,
            clienteId: $clienteId,
            estado: $estado,
            descripcion: $descripcion,
            prendas: collect($prendas),
            fechaCreacion: $fechaCreacion,
            observaciones: $observaciones,
            fechaActualizacion: $fechaActualizacion
        );
    }

    /**
     * Confirmar pedido (cambiar estado)
     */
    public function confirmar(): void
    {
        // Validar que puede confirmarse
        if ($this->estado->esFinal()) {
            throw new \DomainException('No se puede confirmar un pedido en estado final');
        }

        $nuevoEstado = Estado::desde(Estado::CONFIRMADO);
        $this->estado->transicionarA($nuevoEstado);
        $this->estado = $nuevoEstado;
        $this->fechaActualizacion = new \DateTime();

        // Evento
        $this->agregarEvento(
            new EstadoPedidoCambiado(
                pedidoId: $this->id,
                numero: (string)$this->numero,
                estadoAnterior: Estado::PENDIENTE,
                estadoNuevo: Estado::CONFIRMADO
            )
        );
    }

    /**
     * Iniciar producción
     */
    public function iniciarProduccion(): void
    {
        $nuevoEstado = Estado::desde(Estado::EN_PRODUCCION);
        $this->estado->transicionarA($nuevoEstado);
        $this->estado = $nuevoEstado;
        $this->fechaActualizacion = new \DateTime();

        $this->agregarEvento(
            new EstadoPedidoCambiado(
                pedidoId: $this->id,
                numero: (string)$this->numero,
                estadoAnterior: (string)$this->estado,
                estadoNuevo: Estado::EN_PRODUCCION
            )
        );
    }

    /**
     * Completar pedido
     */
    public function completar(): void
    {
        $nuevoEstado = Estado::desde(Estado::COMPLETADO);
        $this->estado->transicionarA($nuevoEstado);
        $this->estado = $nuevoEstado;
        $this->fechaActualizacion = new \DateTime();
    }

    /**
     * Cancelar pedido
     */
    public function cancelar(): void
    {
        $nuevoEstado = Estado::desde(Estado::CANCELADO);
        $this->estado->transicionarA($nuevoEstado);
        $this->estado = $nuevoEstado;
        $this->fechaActualizacion = new \DateTime();
    }

    /**
     * Actualizar descripción
     */
    public function actualizarDescripcion(string $nuevaDescripcion): void
    {
        if ($this->estado->esFinal()) {
            throw new \DomainException('No se puede editar pedido en estado final');
        }

        $this->descripcion = $nuevaDescripcion;
        $this->fechaActualizacion = new \DateTime();

        $this->agregarEvento(
            new PedidoActualizado(
                pedidoId: $this->id,
                numero: (string)$this->numero,
                campo: 'descripcion'
            )
        );
    }

    /**
     * Agregar observaciones
     */
    public function agregarObservaciones(string $observaciones): void
    {
        $this->observaciones = $observaciones;
        $this->fechaActualizacion = new \DateTime();
    }

    /**
     * Getters
     */
    public function id(): ?int { return $this->id; }
    public function numero(): NumeroPedido { return $this->numero; }
    public function clienteId(): int { return $this->clienteId; }
    public function estado(): Estado { return $this->estado; }
    public function descripcion(): string { return $this->descripcion; }
    public function observaciones(): ?string { return $this->observaciones; }
    public function prendas(): Collection { return $this->prendas; }
    public function fechaCreacion(): \DateTime { return $this->fechaCreacion; }
    public function fechaActualizacion(): \DateTime { return $this->fechaActualizacion; }

    /**
     * Métodos de negocio
     */
    public function totalPrendas(): int
    {
        return $this->prendas->count();
    }

    public function totalArticulos(): int
    {
        return $this->prendas->sum(fn(PrendaPedido $p) => $p->cantidad());
    }

    /**
     * Para persistencia (DB)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero' => (string)$this->numero,
            'cliente_id' => $this->clienteId,
            'estado' => $this->estado->valor(),
            'descripcion' => $this->descripcion,
            'observaciones' => $this->observaciones,
            'fecha_creacion' => $this->fechaCreacion->format('Y-m-d H:i:s'),
            'fecha_actualizacion' => $this->fechaActualizacion->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Prendas como array
     */
    public function prendasArray(): array
    {
        return $this->prendas->map(fn(PrendaPedido $p) => $p->toArray())->toArray();
    }
}
```

---

## 🎯 PASO 4: CREAR REPOSITORY INTERFACE

### 4.1 `app/Domain/Pedidos/Repositories/PedidoRepository.php`

```php
<?php

namespace App\Domain\Pedidos\Repositories;

use App\Domain\Pedidos\Agregado\PedidoAggregate;
use App\Domain\Pedidos\ValueObjects\NumeroPedido;

/**
 * Repository Interface para Pedidos
 * 
 * Define el contrato para persistencia de PedidoAggregate
 * La implementación está en Infrastructure
 */
interface PedidoRepository
{
    /**
     * Guardar un pedido (crear o actualizar)
     */
    public function guardar(PedidoAggregate $pedido): void;

    /**
     * Obtener por ID
     */
    public function porId(int $id): ?PedidoAggregate;

    /**
     * Obtener por número
     */
    public function porNumero(NumeroPedido $numero): ?PedidoAggregate;

    /**
     * Obtener todos los pedidos de un cliente
     */
    public function porClienteId(int $clienteId): array;

    /**
     * Eliminar
     */
    public function eliminar(int $id): void;

    /**
     * Obtener por estado
     */
    public function porEstado(string $estado): array;
}
```

---

## 🎯 PASO 5: CREAR REPOSITORY IMPLEMENTATION

### 5.1 `app/Infrastructure/Persistence/Eloquent/PedidoRepositoryImpl.php`

```php
<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Agregado\PedidoAggregate;
use App\Domain\Pedidos\ValueObjects\NumeroPedido;
use App\Domain\Pedidos\ValueObjects\Estado;
use App\Domain\Pedidos\Entities\PrendaPedido;
use App\Models\Pedido as PedidoModel;
use App\Models\PrendaPedido as PrendaPedidoModel;
use Illuminate\Support\Collection;

/**
 * Repository Implementation para Pedidos
 * 
 * Implementa la persistencia usando Eloquent
 * Convierte entre agregado de dominio y modelo Eloquent
 */
class PedidoRepositoryImpl implements PedidoRepository
{
    /**
     * Guardar agregado
     */
    public function guardar(PedidoAggregate $pedido): void
    {
        // Iniciar transacción
        \DB::transaction(function () use ($pedido) {
            // Guardar pedido principal
            $pedidoModel = PedidoModel::updateOrCreate(
                ['numero' => (string)$pedido->numero()],
                [
                    'cliente_id' => $pedido->clienteId(),
                    'estado' => $pedido->estado()->valor(),
                    'descripcion' => $pedido->descripcion(),
                    'observaciones' => $pedido->observaciones(),
                ]
            );

            // Si es nuevo, asignar ID
            if ($pedido->id() === null) {
                $pedido->setId($pedidoModel->id);
            }

            // Guardar prendas
            $this->guardarPrendas($pedido, $pedidoModel);

            // Publicar eventos de dominio
            $this->publicarEventos($pedido);
        });
    }

    /**
     * Obtener por ID
     */
    public function porId(int $id): ?PedidoAggregate
    {
        $pedidoModel = PedidoModel::with('prendas')->find($id);
        
        if (!$pedidoModel) {
            return null;
        }

        return $this->reconstituir($pedidoModel);
    }

    /**
     * Obtener por número
     */
    public function porNumero(NumeroPedido $numero): ?PedidoAggregate
    {
        $pedidoModel = PedidoModel::with('prendas')
            ->where('numero', (string)$numero)
            ->first();

        if (!$pedidoModel) {
            return null;
        }

        return $this->reconstituir($pedidoModel);
    }

    /**
     * Obtener por cliente
     */
    public function porClienteId(int $clienteId): array
    {
        return PedidoModel::with('prendas')
            ->where('cliente_id', $clienteId)
            ->get()
            ->map(fn($model) => $this->reconstituir($model))
            ->toArray();
    }

    /**
     * Obtener por estado
     */
    public function porEstado(string $estado): array
    {
        return PedidoModel::with('prendas')
            ->where('estado', $estado)
            ->get()
            ->map(fn($model) => $this->reconstituir($model))
            ->toArray();
    }

    /**
     * Eliminar
     */
    public function eliminar(int $id): void
    {
        \DB::transaction(function () use ($id) {
            PrendaPedidoModel::where('pedido_id', $id)->delete();
            PedidoModel::destroy($id);
        });
    }

    /**
     * Reconstruir agregado desde modelo Eloquent
     */
    private function reconstituir(PedidoModel $model): PedidoAggregate
    {
        // Reconstruir prendas
        $prendas = $model->prendas->map(function ($prendaModel) {
            return new PrendaPedido(
                id: $prendaModel->id,
                pedidoId: $prendaModel->pedido_id,
                prendaId: $prendaModel->prenda_id,
                descripcion: $prendaModel->descripcion,
                cantidad: $prendaModel->cantidad,
                tallas: json_decode($prendaModel->tallas, true) ?? [],
                observaciones: $prendaModel->observaciones
            );
        })->values();

        return PedidoAggregate::reconstruir(
            id: $model->id,
            numero: NumeroPedido::desde($model->numero),
            clienteId: $model->cliente_id,
            estado: Estado::desde($model->estado),
            descripcion: $model->descripcion,
            prendas: $prendas->toArray(),
            fechaCreacion: $model->created_at,
            observaciones: $model->observaciones,
            fechaActualizacion: $model->updated_at
        );
    }

    /**
     * Guardar prendas asociadas
     */
    private function guardarPrendas(PedidoAggregate $pedido, PedidoModel $pedidoModel): void
    {
        // Eliminar prendas existentes
        $pedidoModel->prendas()->delete();

        // Crear nuevas prendas
        foreach ($pedido->prendas() as $prenda) {
            PrendaPedidoModel::create([
                'pedido_id' => $pedidoModel->id,
                'prenda_id' => $prenda->prendaId(),
                'descripcion' => $prenda->descripcion(),
                'cantidad' => $prenda->cantidad(),
                'tallas' => json_encode($prenda->tallas()),
                'observaciones' => $prenda->observaciones(),
            ]);
        }
    }

    /**
     * Publicar eventos de dominio
     */
    private function publicarEventos(PedidoAggregate $pedido): void
    {
        foreach ($pedido->obtenerEventos() as $evento) {
            \Event::dispatch($evento);
        }

        $pedido->limpiarEventos();
    }
}
```

---

## 🎯 PASO 6: CREAR USE CASES (APPLICATION LAYER)

### 6.1 `app/Application/Pedidos/UseCases/CrearPedidoUseCase.php`

```php
<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\CrearPedidoDTO;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Agregado\PedidoAggregate;

/**
 * Use Case: Crear Pedido
 * 
 * Orquesta:
 * 1. Validar entrada (DTO)
 * 2. Crear agregado
 * 3. Persistir
 * 4. Disparar eventos
 * 5. Retornar respuesta
 */
class CrearPedidoUseCase
{
    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    /**
     * Ejecutar caso de uso
     */
    public function ejecutar(CrearPedidoDTO $dto): PedidoResponseDTO
    {
        try {
            // 1. Crear agregado (valida internamente)
            $pedido = PedidoAggregate::crear(
                clienteId: $dto->clienteId,
                descripcion: $dto->descripcion,
                prendasData: $dto->prendas,
                observaciones: $dto->observaciones
            );

            // 2. Persistir
            $this->pedidoRepository->guardar($pedido);

            // 3. Retornar respuesta
            return new PedidoResponseDTO(
                id: $pedido->id(),
                numero: (string)$pedido->numero(),
                clienteId: $pedido->clienteId(),
                estado: $pedido->estado()->valor(),
                descripcion: $pedido->descripcion(),
                totalPrendas: $pedido->totalPrendas(),
                totalArticulos: $pedido->totalArticulos(),
                mensaje: 'Pedido creado exitosamente'
            );

        } catch (\Exception $e) {
            throw new \DomainException('Error al crear pedido: ' . $e->getMessage());
        }
    }
}
```

### 6.2 `app/Application/Pedidos/UseCases/ConfirmarPedidoUseCase.php`

```php
<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;

/**
 * Use Case: Confirmar Pedido
 */
class ConfirmarPedidoUseCase
{
    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function ejecutar(int $pedidoId): PedidoResponseDTO
    {
        $pedido = $this->pedidoRepository->porId($pedidoId);

        if (!$pedido) {
            throw new \DomainException("Pedido $pedidoId no encontrado");
        }

        $pedido->confirmar();
        $this->pedidoRepository->guardar($pedido);

        return new PedidoResponseDTO(
            id: $pedido->id(),
            numero: (string)$pedido->numero(),
            clienteId: $pedido->clienteId(),
            estado: $pedido->estado()->valor(),
            descripcion: $pedido->descripcion(),
            totalPrendas: $pedido->totalPrendas(),
            totalArticulos: $pedido->totalArticulos(),
            mensaje: 'Pedido confirmado exitosamente'
        );
    }
}
```

---

## 🎯 PASO 7: CREAR DTOs

### 7.1 `app/Application/Pedidos/DTOs/CrearPedidoDTO.php`

```php
<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO: Crear Pedido
 * 
 * Desde HTTP/API hacia Application Layer
 */
class CrearPedidoDTO
{
    public function __construct(
        public int $clienteId,
        public string $descripcion,
        public array $prendas,  // [{ prenda_id, descripcion, cantidad, tallas }, ...]
        public ?string $observaciones = null
    ) {
        $this->validar();
    }

    /**
     * Factory desde request
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            clienteId: (int) $data['cliente_id'],
            descripcion: (string) $data['descripcion'],
            prendas: (array) $data['prendas'],
            observaciones: $data['observaciones'] ?? null
        );
    }

    /**
     * Validar
     */
    private function validar(): void
    {
        if ($this->clienteId <= 0) {
            throw new \InvalidArgumentException('Cliente ID inválido');
        }

        if (empty($this->descripcion)) {
            throw new \InvalidArgumentException('Descripción requerida');
        }

        if (empty($this->prendas)) {
            throw new \InvalidArgumentException('Debe haber al menos una prenda');
        }
    }
}
```

### 7.2 `app/Application/Pedidos/DTOs/PedidoResponseDTO.php`

```php
<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO: Respuesta de Pedido
 * 
 * Desde Application Layer hacia HTTP/API
 */
class PedidoResponseDTO
{
    public function __construct(
        public ?int $id,
        public string $numero,
        public int $clienteId,
        public string $estado,
        public string $descripcion,
        public int $totalPrendas,
        public int $totalArticulos,
        public string $mensaje = ''
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'cliente_id' => $this->clienteId,
            'estado' => $this->estado,
            'descripcion' => $this->descripcion,
            'total_prendas' => $this->totalPrendas,
            'total_articulos' => $this->totalArticulos,
            'mensaje' => $this->mensaje,
        ];
    }
}
```

---

## 🎯 PASO 8: CREAR DOMAIN EVENTS

### 8.1 `app/Domain/Pedidos/Events/PedidoCreado.php`

```php
<?php

namespace App\Domain\Pedidos\Events;

use App\Domain\Shared\DomainEvent;

/**
 * Domain Event: Pedido Creado
 */
class PedidoCreado extends DomainEvent
{
    public function __construct(
        public ?int $pedidoId,
        public string $numero,
        public int $clienteId,
        public string $descripcion,
        public int $totalPrendas
    ) {}
}
```

---

## 🎯 PASO 9: CREAR DOMAIN EVENT LISTENER

### 9.1 `app/Application/Pedidos/Listeners/PedidoCreadoListener.php`

```php
<?php

namespace App\Application\Pedidos\Listeners;

use App\Domain\Pedidos\Events\PedidoCreado;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Cuando se crea un Pedido
 * 
 * Reacciona a eventos de dominio
 */
class PedidoCreadoListener
{
    /**
     * Manejador del evento
     */
    public function handle(PedidoCreado $evento): void
    {
        // Log
        Log::info('Pedido creado', [
            'numero' => $evento->numero,
            'cliente_id' => $evento->clienteId,
            'total_prendas' => $evento->totalPrendas,
        ]);

        // TODO: Aquí puedes:
        // - Enviar email al cliente
        // - Actualizar caché
        // - Sincronizar con otros sistemas
        // - Crear notificaciones
    }
}
```

---

## 🎯 PASO 10: REFACTORIZAR CONTROLLER

### 10.1 `app/Http/Controllers/PedidoController.php` (REFACTORIZADO)

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\DTOs\CrearPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;

/**
 * Controller: Pedidos
 * 
 * REFACTORIZADO para usar Use Cases
 * - No tiene lógica de negocio
 * - Solo orquesta HTTP ↔ Application
 */
class PedidoController extends Controller
{
    public function __construct(
        private CrearPedidoUseCase $crearPedidoUseCase,
        private ConfirmarPedidoUseCase $confirmarPedidoUseCase,
        private PedidoRepository $pedidoRepository
    ) {}

    /**
     * Crear pedido
     * POST /api/pedidos
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validar entrada
            $validated = $request->validate([
                'cliente_id' => 'required|integer|exists:clientes,id',
                'descripcion' => 'required|string|max:1000',
                'prendas' => 'required|array|min:1',
                'prendas.*.prenda_id' => 'required|integer|exists:prendas,id',
                'prendas.*.descripcion' => 'required|string',
                'prendas.*.cantidad' => 'required|integer|min:1',
                'prendas.*.tallas' => 'required|json',
                'observaciones' => 'nullable|string|max:1000',
            ]);

            // Crear DTO
            $dto = CrearPedidoDTO::fromRequest($validated);

            // Ejecutar caso de uso
            $respuesta = $this->crearPedidoUseCase->ejecutar($dto);

            return response()->json([
                'success' => true,
                'data' => $respuesta->toArray(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtener pedido
     * GET /api/pedidos/{id}
     */
    public function show(int $id): JsonResponse
    {
        $pedido = $this->pedidoRepository->porId($id);

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pedido->id(),
                'numero' => (string)$pedido->numero(),
                'cliente_id' => $pedido->clienteId(),
                'estado' => $pedido->estado()->valor(),
                'descripcion' => $pedido->descripcion(),
                'total_prendas' => $pedido->totalPrendas(),
                'total_articulos' => $pedido->totalArticulos(),
            ],
        ]);
    }

    /**
     * Confirmar pedido
     * PATCH /api/pedidos/{id}/confirmar
     */
    public function confirmar(int $id): JsonResponse
    {
        try {
            $respuesta = $this->confirmarPedidoUseCase->ejecutar($id);

            return response()->json([
                'success' => true,
                'data' => $respuesta->toArray(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
```

---

## 🎯 PASO 11: REGISTRAR BINDINGS EN SERVICE PROVIDER

### 11.1 `app/Providers/PedidoServiceProvider.php` (CREAR)

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Infrastructure\Persistence\Eloquent\PedidoRepositoryImpl;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\Listeners\PedidoCreadoListener;
use App\Domain\Pedidos\Events\PedidoCreado;

class PedidoServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Registrar repository
        $this->app->bind(
            PedidoRepository::class,
            PedidoRepositoryImpl::class
        );

        // Registrar use cases
        $this->app->bind(
            CrearPedidoUseCase::class,
            fn($app) => new CrearPedidoUseCase(
                $app->make(PedidoRepository::class)
            )
        );

        $this->app->bind(
            ConfirmarPedidoUseCase::class,
            fn($app) => new ConfirmarPedidoUseCase(
                $app->make(PedidoRepository::class)
            )
        );
    }

    /**
     * Boot services
     */
    public function boot(): void
    {
        // Listeners para domain events
        $this->app['events']->listen(
            PedidoCreado::class,
            PedidoCreadoListener::class
        );
    }
}
```

Añadir en `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\PedidoServiceProvider::class,
],
```

---

## 🎯 PASO 12: ACTUALIZAR RUTAS

### 12.1 `routes/api.php`

```php
Route::prefix('api')->middleware('api')->group(function () {
    // Pedidos - REFACTORIZADO para DDD
    Route::post('pedidos', [PedidoController::class, 'store']);
    Route::get('pedidos/{id}', [PedidoController::class, 'show']);
    Route::patch('pedidos/{id}/confirmar', [PedidoController::class, 'confirmar']);
});
```

---

## 📋 RESUMEN: FLUJO DE EJECUCIÓN

```
Cliente HTTP (POST /api/pedidos)
        ↓
PedidoController::store()
        ↓
Validación (Form Request)
        ↓
CrearPedidoDTO::fromRequest()
        ↓
CrearPedidoUseCase::ejecutar($dto)
        ↓
PedidoAggregate::crear()  ← LÓGICA DE NEGOCIO PURA
├─ Validación de datos
├─ Creación de Prendas
├─ Asignación de Estado
└─ Disparo de eventos
        ↓
PedidoRepository::guardar($pedido)  ← PERSISTENCIA
├─ Guardar en BD (Eloquent)
├─ Publicar eventos
└─ Transacción
        ↓
PedidoCreadoListener::handle()  ← REACCIÓN A EVENTOS
├─ Log
├─ Email
└─ Notificaciones
        ↓
PedidoResponseDTO
        ↓
HTTP 201 JSON Response
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

```
□ Crear carpeta Domain/Pedidos/
□ Crear ValueObjects (NumeroPedido, Estado)
□ Crear Entidades (PrendaPedido)
□ Crear Agregado (PedidoAggregate)
□ Crear Repository Interface
□ Crear Repository Implementation
□ Crear DTOs
□ Crear Use Cases
□ Crear Domain Events
□ Crear Listeners
□ Crear Service Provider
□ Refactorizar Controller
□ Actualizar rutas
□ Registrar bindings
□ Modificar Modelos Eloquent si es necesario
□ Tests (después, con TDD)
□ Documentación
```

---

## 🚨 IMPORTANTE: MODELOS ELOQUENT

Los modelos `Pedido.php` y `PrendaPedido.php` ahora son SOLO para persistencia (mapeo a tablas). Su lógica se mueve al Agregado.

```php
// app/Models/Pedido.php (SIMPLIFICADO)
class Pedido extends Model
{
    protected $table = 'pedidos';
    protected $fillable = ['numero', 'cliente_id', 'estado', 'descripcion', 'observaciones'];

    public function prendas()
    {
        return $this->hasMany(PrendaPedido::class, 'pedido_id');
    }
}

// app/Models/PrendaPedido.php (SIMPLIFICADO)
class PrendaPedido extends Model
{
    protected $table = 'prenda_pedidos';
    protected $fillable = ['pedido_id', 'prenda_id', 'descripcion', 'cantidad', 'tallas', 'observaciones'];
}
```

---

## 🎓 BENEFICIOS DE ESTA IMPLEMENTACIÓN

✅ **Lógica de negocio aislada** en Agregado (testeable sin BD)  
✅ **Transacciones automáticas** en Repository  
✅ **Eventos de dominio** desacoplados  
✅ **DTOs** para transferencia de datos  
✅ **Use Cases** claros y reutilizables  
✅ **Escalable** (fácil añadir nuevos casos de uso)  
✅ **Mantenible** (cambios centralizados)  
✅ **Type-safe** (con PHP 8 strict types)

---

**Guía creada:** 22/01/2026  
**Versión:** 1.0  
**Estado:** Listo para implementar
