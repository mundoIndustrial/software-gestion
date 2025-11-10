# Plan de Mejoras y Refactorización

**Proyecto:** Mundo Industrial v4.0  
**Fecha:** 10 Noviembre 2025

---

## 📋 Índice

1. [Estrategia General](#estrategia-general)
2. [Roadmap de Implementación](#roadmap-de-implementación)
3. [Arquitectura Propuesta](#arquitectura-propuesta)
4. [Migraciones de Base de Datos](#migraciones-de-base-de-datos)
5. [Refactorización de Código](#refactorización-de-código)
6. [Testing](#testing)
7. [Deployment](#deployment)

---

## 🎯 Estrategia General

### Principios de Refactorización

1. **Incremental**: Cambios pequeños y frecuentes
2. **No Breaking**: Mantener compatibilidad durante transición
3. **Test-Driven**: Tests antes de refactorizar
4. **Documentado**: Cada cambio debe estar documentado
5. **Reversible**: Poder hacer rollback si es necesario

### Enfoque: Strangler Fig Pattern

```
Sistema Actual (Monolito)
    ↓
Crear nueva arquitectura en paralelo
    ↓
Migrar funcionalidad gradualmente
    ↓
Deprecar código antiguo
    ↓
Eliminar código legacy
```

---

## 🗓️ Roadmap de Implementación

### Fase 1: Preparación (Semanas 1-2)

#### Semana 1: Setup y Análisis
- [x] Análisis completo de arquitectura actual
- [ ] Configurar entorno de testing
- [ ] Crear branch de refactorización
- [ ] Setup CI/CD pipeline
- [ ] Documentar APIs actuales

#### Semana 2: Fundamentos
- [ ] Crear estructura de carpetas para nueva arquitectura
- [ ] Configurar autoloading PSR-4 para módulos
- [ ] Implementar Service Provider para DI
- [ ] Crear interfaces base
- [ ] Setup de logging mejorado

**Entregables:**
- Estructura de carpetas `app/Domain/`
- Tests de integración básicos
- Documentación de arquitectura

---

### Fase 2: Base de Datos (Semanas 3-6)

#### Semana 3: Normalización Crítica
**Prioridad:** 🔴 ALTA

```sql
-- Migración 1: Crear tabla clientes
CREATE TABLE clientes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    nit VARCHAR(50),
    telefono VARCHAR(50),
    email VARCHAR(255),
    direccion TEXT,
    ciudad VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_nombre (nombre)
);

-- Migración 2: Crear tabla modulos
CREATE TABLE modulos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    tipo ENUM('Produccion', 'Polo', 'Ambos') DEFAULT 'Ambos',
    capacidad_operarios INT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Migración 3: Migrar datos de clientes
INSERT INTO clientes (nombre, created_at)
SELECT DISTINCT cliente, NOW()
FROM tabla_original
WHERE cliente IS NOT NULL;

-- Migración 4: Agregar cliente_id a tabla_original
ALTER TABLE tabla_original
ADD COLUMN cliente_id BIGINT UNSIGNED AFTER cliente,
ADD FOREIGN KEY (cliente_id) REFERENCES clientes(id);

-- Migración 5: Actualizar cliente_id
UPDATE tabla_original t
JOIN clientes c ON t.cliente = c.nombre
SET t.cliente_id = c.id;
```

#### Semana 4: Unificar Tablas de Producción
**Prioridad:** 🔴 ALTA

```sql
-- Migración 6: Crear tabla unificada
CREATE TABLE registros_produccion (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    fecha DATE NOT NULL,
    tipo_produccion ENUM('Produccion', 'Polo') NOT NULL,
    modulo_id BIGINT UNSIGNED NOT NULL,
    orden_id BIGINT UNSIGNED NOT NULL,
    hora_id BIGINT UNSIGNED NOT NULL,
    tiempo_ciclo DECIMAL(8,2) NOT NULL,
    porcion_tiempo DECIMAL(8,2) NOT NULL,
    cantidad INT NOT NULL,
    numero_operarios INT NOT NULL,
    paradas_programadas VARCHAR(255),
    paradas_no_programadas VARCHAR(255),
    tiempo_parada_no_programada DECIMAL(8,2),
    tiempo_para_programada DECIMAL(8,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Columnas virtuales
    tiempo_disponible DECIMAL(10,2) GENERATED ALWAYS AS (
        (3600 * porcion_tiempo * numero_operarios) - 
        (COALESCE(tiempo_parada_no_programada, 0) + tiempo_para_programada)
    ) VIRTUAL,
    
    meta DECIMAL(10,2) GENERATED ALWAYS AS (
        tiempo_disponible / tiempo_ciclo
    ) VIRTUAL,
    
    eficiencia DECIMAL(5,2) GENERATED ALWAYS AS (
        (cantidad / NULLIF(meta, 0)) * 100
    ) VIRTUAL,
    
    FOREIGN KEY (modulo_id) REFERENCES modulos(id),
    FOREIGN KEY (hora_id) REFERENCES horas(id),
    INDEX idx_fecha_tipo (fecha, tipo_produccion),
    INDEX idx_modulo (modulo_id)
);

-- Migración 7: Migrar datos de producción
INSERT INTO registros_produccion (
    fecha, tipo_produccion, modulo_id, orden_id, hora_id,
    tiempo_ciclo, porcion_tiempo, cantidad, numero_operarios,
    paradas_programadas, paradas_no_programadas,
    tiempo_parada_no_programada, tiempo_para_programada,
    created_at, updated_at
)
SELECT 
    fecha, 'Produccion', 
    (SELECT id FROM modulos WHERE nombre = modulo LIMIT 1),
    -- ... mapeo de campos
FROM registro_piso_produccion;

-- Migración 8: Migrar datos de polos
INSERT INTO registros_produccion (/* ... */)
SELECT /* ... */ FROM registro_piso_polo;
```

#### Semana 5: Unificar Entregas
**Prioridad:** 🟡 MEDIA

```sql
-- Migración 9: Crear tabla unificada de entregas
CREATE TABLE entregas (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('Pedido', 'Bodega') NOT NULL,
    area ENUM('Costura', 'Corte') NOT NULL,
    pedido INT NOT NULL,
    item_orden_id BIGINT UNSIGNED NOT NULL,
    cantidad_entregada INT NOT NULL,
    fecha_entrega DATE NOT NULL,
    responsable_id BIGINT UNSIGNED NOT NULL,
    mes_ano VARCHAR(7) NOT NULL,
    observaciones TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (item_orden_id) REFERENCES items_orden(id),
    FOREIGN KEY (responsable_id) REFERENCES users(id),
    INDEX idx_tipo_area (tipo, area),
    INDEX idx_fecha_entrega (fecha_entrega),
    INDEX idx_pedido (pedido)
);
```

#### Semana 6: Normalizar Órdenes
**Prioridad:** 🔴 ALTA

```sql
-- Migración 10: Crear nueva estructura de órdenes
CREATE TABLE ordenes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    pedido INT UNIQUE NOT NULL,
    cliente_id BIGINT UNSIGNED NOT NULL,
    asesora_id BIGINT UNSIGNED NOT NULL,
    estado ENUM('Borrador', 'Aprobada', 'En Producción', 'Completada', 'Cancelada'),
    descripcion TEXT,
    cantidad_total INT NOT NULL,
    forma_pago VARCHAR(100),
    fecha_creacion DATE NOT NULL,
    fecha_despacho DATE,
    novedades TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (asesora_id) REFERENCES users(id)
);

CREATE TABLE items_orden (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    orden_id BIGINT UNSIGNED NOT NULL,
    prenda_id BIGINT UNSIGNED,
    descripcion TEXT,
    talla VARCHAR(50),
    cantidad_solicitada INT NOT NULL,
    cantidad_producida INT DEFAULT 0,
    cantidad_entregada INT DEFAULT 0,
    estado ENUM('Pendiente', 'En Producción', 'Completado'),
    created_at TIMESTAMP,
    FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE,
    FOREIGN KEY (prenda_id) REFERENCES prendas(id)
);

CREATE TABLE etapas_orden (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    orden_id BIGINT UNSIGNED NOT NULL,
    etapa ENUM('Orden', 'Inventario', 'Insumos', 'Corte', 'Bordado', 
               'Estampado', 'Costura', 'Reflectivo', 'Lavandería', 
               'Arreglos', 'Marras', 'Calidad', 'Entrega'),
    fecha_inicio DATE,
    fecha_fin DATE,
    estado ENUM('Pendiente', 'En Proceso', 'Completado'),
    observaciones TEXT,
    FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE
);
```

**Entregables Fase 2:**
- Base de datos normalizada
- Scripts de migración de datos
- Tests de integridad de datos
- Documentación de cambios en esquema

---

### Fase 3: Service Layer (Semanas 7-10)

#### Semana 7: Servicios Core

```php
// app/Domain/Shared/Services/BaseService.php
abstract class BaseService
{
    protected function beginTransaction(): void
    {
        DB::beginTransaction();
    }
    
    protected function commit(): void
    {
        DB::commit();
    }
    
    protected function rollback(): void
    {
        DB::rollBack();
    }
}

// app/Domain/Ordenes/Services/OrdenService.php
class OrdenService extends BaseService
{
    public function __construct(
        private OrdenRepository $ordenRepo,
        private ClienteRepository $clienteRepo,
        private ValidadorOrden $validador,
        private EventDispatcher $events
    ) {}
    
    public function crear(array $data): Orden
    {
        $this->beginTransaction();
        
        try {
            // Validar
            $this->validador->validarCreacion($data);
            
            // Crear orden
            $orden = $this->ordenRepo->create($data);
            
            // Crear items
            foreach ($data['items'] as $itemData) {
                $orden->agregarItem($itemData);
            }
            
            // Evento
            $this->events->dispatch(new OrdenCreada($orden));
            
            $this->commit();
            return $orden;
            
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    public function aprobar(int $ordenId, int $aprobadorId): Orden
    {
        $orden = $this->ordenRepo->findOrFail($ordenId);
        $aprobador = User::findOrFail($aprobadorId);
        
        $orden->aprobar($aprobador);
        
        $this->ordenRepo->save($orden);
        $this->events->dispatch(new OrdenAprobada($orden));
        
        return $orden;
    }
}
```

#### Semana 8: Repositories

```php
// app/Domain/Ordenes/Repositories/OrdenRepository.php
class OrdenRepository implements OrdenRepositoryInterface
{
    public function findOrFail(int $id): Orden
    {
        return Orden::with(['cliente', 'items', 'etapas'])
            ->findOrFail($id);
    }
    
    public function findWithFilters(array $filters): Collection
    {
        $query = Orden::query();
        
        if (isset($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }
        
        if (isset($filters['fecha_desde'])) {
            $query->whereDate('fecha_creacion', '>=', $filters['fecha_desde']);
        }
        
        if (isset($filters['cliente_id'])) {
            $query->where('cliente_id', $filters['cliente_id']);
        }
        
        return $query->with(['cliente', 'asesora'])
            ->orderBy('fecha_creacion', 'desc')
            ->get();
    }
    
    public function save(Orden $orden): void
    {
        DB::transaction(function () use ($orden) {
            $orden->save();
            
            // Guardar items del agregado
            foreach ($orden->items as $item) {
                $item->orden_id = $orden->id;
                $item->save();
            }
            
            // Guardar etapas
            foreach ($orden->etapas as $etapa) {
                $etapa->orden_id = $orden->id;
                $etapa->save();
            }
        });
    }
}
```

#### Semana 9: Value Objects

```php
// app/Domain/Shared/ValueObjects/EstadoOrden.php
final class EstadoOrden
{
    private const BORRADOR = 'Borrador';
    private const APROBADA = 'Aprobada';
    private const EN_PRODUCCION = 'En Producción';
    private const COMPLETADA = 'Completada';
    private const CANCELADA = 'Cancelada';
    
    private function __construct(private string $valor)
    {
        if (!in_array($valor, $this->valoresPermitidos())) {
            throw new \InvalidArgumentException("Estado inválido: {$valor}");
        }
    }
    
    public static function borrador(): self
    {
        return new self(self::BORRADOR);
    }
    
    public static function fromString(string $valor): self
    {
        return new self($valor);
    }
    
    public function esBorrador(): bool
    {
        return $this->valor === self::BORRADOR;
    }
    
    public function puedeSerAprobada(): bool
    {
        return $this->esBorrador();
    }
    
    public function toString(): string
    {
        return $this->valor;
    }
    
    private function valoresPermitidos(): array
    {
        return [
            self::BORRADOR,
            self::APROBADA,
            self::EN_PRODUCCION,
            self::COMPLETADA,
            self::CANCELADA,
        ];
    }
}
```

#### Semana 10: Events y Listeners

```php
// app/Domain/Ordenes/Events/OrdenCreada.php
class OrdenCreada
{
    public function __construct(public readonly Orden $orden) {}
}

// app/Domain/Ordenes/Listeners/NotificarProduccion.php
class NotificarProduccion
{
    public function handle(OrdenCreada $event): void
    {
        $orden = $event->orden;
        
        // Notificar a producción
        Notification::send(
            User::role('produccion')->get(),
            new NuevaOrdenNotification($orden)
        );
        
        // Crear registro en news
        News::create([
            'message' => "Nueva orden #{$orden->pedido} creada",
            'type' => 'orden_creada',
            'data' => ['orden_id' => $orden->id]
        ]);
    }
}

// app/Domain/Ordenes/Listeners/ActualizarInventario.php
class ActualizarInventario
{
    public function __construct(
        private InventarioService $inventarioService
    ) {}
    
    public function handle(OrdenCreada $event): void
    {
        $orden = $event->orden;
        
        // Reservar materiales
        foreach ($orden->items as $item) {
            $this->inventarioService->reservarMateriales($item);
        }
    }
}
```

**Entregables Fase 3:**
- Service Layer completo
- Repository Pattern implementado
- Value Objects principales
- Sistema de eventos

---

### Fase 4: Refactorización de Controladores (Semanas 11-14)

#### Semana 11: Dividir TablerosController

```php
// Antes: 1 controlador de 1691 líneas
TablerosController.php (1691 líneas)

// Después: 10 controladores especializados
app/Http/Controllers/
├── Tableros/
│   ├── TablerosController.php (80 líneas)
│   ├── ProduccionController.php (120 líneas)
│   ├── CorteController.php (100 líneas)
│   ├── OperarioController.php (60 líneas)
│   ├── MaquinaController.php (60 líneas)
│   ├── TelaController.php (60 líneas)
│   ├── ProduccionDashboardController.php (90 líneas)
│   ├── CorteDashboardController.php (80 líneas)
│   ├── FiltroController.php (70 líneas)
│   └── UtilController.php (50 líneas)
```

#### Semana 12: Form Requests

```php
// app/Http/Requests/Produccion/StoreProduccionRequest.php
class StoreProduccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crear_produccion');
    }
    
    public function rules(): array
    {
        return [
            'fecha' => 'required|date',
            'modulo_id' => 'required|exists:modulos,id',
            'orden_id' => 'required|exists:ordenes,id',
            'hora_id' => 'required|exists:horas,id',
            'tiempo_ciclo' => 'required|numeric|min:0',
            'cantidad' => 'required|integer|min:1',
            'numero_operarios' => 'required|integer|min:1',
        ];
    }
    
    public function messages(): array
    {
        return [
            'modulo_id.required' => 'El módulo es obligatorio',
            'cantidad.min' => 'La cantidad debe ser mayor a 0',
        ];
    }
}
```

#### Semana 13: API Resources

```php
// app/Http/Resources/OrdenResource.php
class OrdenResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'pedido' => $this->pedido,
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
            'asesora' => new UserResource($this->whenLoaded('asesora')),
            'estado' => $this->estado->toString(),
            'cantidad_total' => $this->cantidad_total,
            'fecha_creacion' => $this->fecha_creacion->format('Y-m-d'),
            'dias_habiles' => $this->calcularDiasHabiles(),
            'items' => ItemOrdenResource::collection($this->whenLoaded('items')),
            'etapas' => EtapaOrdenResource::collection($this->whenLoaded('etapas')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

#### Semana 14: Exception Handling

```php
// app/Exceptions/Handler.php
class Handler extends ExceptionHandler
{
    protected $dontReport = [
        OrdenNoEncontradaException::class,
        OrdenNoAprobableException::class,
    ];
    
    public function register()
    {
        $this->renderable(function (OrdenNoEncontradaException $e, $request) {
            return response()->json([
                'error' => 'Orden no encontrada',
                'code' => 'ORDEN_NOT_FOUND'
            ], 404);
        });
        
        $this->renderable(function (OrdenNoAprobableException $e, $request) {
            return response()->json([
                'error' => $e->getMessage(),
                'code' => 'ORDEN_NO_APROBABLE'
            ], 422);
        });
    }
}
```

**Entregables Fase 4:**
- Controladores refactorizados
- Form Requests implementados
- API Resources creados
- Exception handling centralizado

---

### Fase 5: Testing (Semanas 15-16)

#### Tests Unitarios

```php
// tests/Unit/Domain/Ordenes/OrdenTest.php
class OrdenTest extends TestCase
{
    /** @test */
    public function puede_aprobar_orden_en_borrador()
    {
        $orden = Orden::factory()->borrador()->create();
        $aprobador = User::factory()->create();
        
        $orden->aprobar($aprobador);
        
        $this->assertTrue($orden->estado->esAprobada());
        $this->assertEquals($aprobador->id, $orden->aprobador_id);
    }
    
    /** @test */
    public function no_puede_aprobar_orden_ya_aprobada()
    {
        $this->expectException(OrdenNoAprobableException::class);
        
        $orden = Orden::factory()->aprobada()->create();
        $aprobador = User::factory()->create();
        
        $orden->aprobar($aprobador);
    }
}
```

#### Tests de Integración

```php
// tests/Feature/Ordenes/CrearOrdenTest.php
class CrearOrdenTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function puede_crear_orden_con_items()
    {
        $asesora = User::factory()->asesora()->create();
        $cliente = Cliente::factory()->create();
        
        $response = $this->actingAs($asesora)
            ->postJson('/api/ordenes', [
                'cliente_id' => $cliente->id,
                'descripcion' => 'Orden de prueba',
                'items' => [
                    [
                        'prenda_id' => Prenda::factory()->create()->id,
                        'talla' => 'M',
                        'cantidad' => 100,
                    ]
                ]
            ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('ordenes', [
            'cliente_id' => $cliente->id,
        ]);
        $this->assertDatabaseHas('items_orden', [
            'talla' => 'M',
            'cantidad_solicitada' => 100,
        ]);
    }
}
```

**Entregables Fase 5:**
- 80%+ cobertura de tests
- Tests unitarios para lógica de negocio
- Tests de integración para APIs
- Tests de feature para flujos completos

---

## 📊 Métricas de Éxito

| Métrica | Antes | Meta | Beneficio |
|---------|-------|------|-----------|
| Líneas por controlador | 1691 | <200 | Mantenibilidad |
| Complejidad ciclomática | 250 | <10 | Comprensibilidad |
| Cobertura de tests | 0% | 80%+ | Confiabilidad |
| Tiempo de build | N/A | <5min | Productividad |
| Tablas normalizadas | 30% | 95% | Integridad datos |
| Duplicación de código | Alta | <5% | Mantenibilidad |

---

## ✅ Checklist de Implementación

### Base de Datos
- [ ] Crear tabla clientes
- [ ] Crear tabla modulos
- [ ] Unificar registros_produccion
- [ ] Unificar entregas
- [ ] Normalizar ordenes
- [ ] Migrar datos existentes
- [ ] Verificar integridad referencial

### Código
- [ ] Crear estructura Domain/
- [ ] Implementar Service Layer
- [ ] Implementar Repository Pattern
- [ ] Crear Value Objects
- [ ] Dividir TablerosController
- [ ] Crear Form Requests
- [ ] Implementar API Resources
- [ ] Centralizar exception handling

### Testing
- [ ] Setup PHPUnit
- [ ] Tests unitarios (80%+)
- [ ] Tests de integración
- [ ] Tests de feature
- [ ] Tests de regresión

### Documentación
- [ ] Documentar nueva arquitectura
- [ ] Actualizar README
- [ ] Documentar APIs
- [ ] Guías de migración
- [ ] Changelog detallado

**Siguiente:** `00-INDICE-PRINCIPAL.md`
