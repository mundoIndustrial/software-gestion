# Backend con DDD - Prenda (Domain-Driven Design)

## 📁 Estructura DDD Recomendada (Laravel)

```
app/
├── Domain/
│   ├── Prenda/
│   │   ├── ValueObjects/
│   │   │   ├── PrendaId.php
│   │   │   ├── PrendaNombre.php
│   │   │   ├── Origen.php
│   │   │   ├── Genero.php
│   │   │   └── ... otros VOs
│   │   ├── Entities/
│   │   │   ├── Prenda.php (Aggregate Root)
│   │   │   ├── Tela.php
│   │   │   ├── Variacion.php
│   │   │   ├── Proceso.php
│   │   │   └── Talla.php
│   │   ├── Services/
│   │   │   ├── AplicarOrigenAutomaticoDomainService.php
│   │   │   ├── ValidarPrendaDomainService.php
│   │   │   ├── ProcesarPrendasDomainService.php
│   │   │   └── NormalizarDatosPrendaDomainService.php
│   │   ├── Repositories/
│   │   │   └── PrendaRepositoryInterface.php
│   │   └── Events/
│   │       ├── PrendaCreada.php
│   │       ├── PrendaGuardada.php
│   │       └── OrigenAplicado.php
│   │
│   └── Cotizacion/
│       ├── ValueObjects/
│       │   ├── CotizacionId.php
│       │   ├── TipoCotizacion.php
│       │   └── ...
│       └── ...
│
├── Application/
│   ├── Services/
│   │   ├── CrearPrendaApplicationService.php
│   │   ├── EditarPrendaApplicationService.php
│   │   ├── ObtenerPrendaParaEdicionApplicationService.php
│   │   ├── ValidarPrendaApplicationService.php
│   │   └── GuardarPrendaApplicationService.php
│   │
│   └── DTOs/
│       ├── ObtenerPrendaRequest.php
│       ├── ObtenerPrendaResponse.php
│       ├── GuardarPrendaRequest.php
│       └── GuardarPrendaResponse.php
│
├── Infrastructure/
│   ├── Persistence/
│   │   └── EloquentPrendaRepository.php
│   │
│   ├── Controllers/
│   │   └── PrendaController.php
│   │
│   └── Routes/
│       └── api.php
│
└── ...
```

---

## 🎯 VALUE OBJECTS - Tipos Primitivos Ricos

```php
<?php // app/Domain/Prenda/ValueObjects/Origen.php

namespace App\Domain\Prenda\ValueObjects;

class Origen
{
    private const BODEGA = 'bodega';
    private const CONFECCION = 'confeccion';
    private const OPCIONES = [self::BODEGA, self::CONFECCION];

    public function __construct(private string $valor)
    {
        if (!in_array($valor, self::OPCIONES)) {
            throw new \InvalidArgumentException(
                "Origen '{$valor}' no válido. Valores: " . implode(', ', self::OPCIONES)
            );
        }
    }

    public static function bodega(): self
    {
        return new self(self::BODEGA);
    }

    public static function confeccion(): self
    {
        return new self(self::CONFECCION);
    }

    /**
     * Crear origen según tipo de cotización
     * ✨ LÓGICA DE NEGOCIO AQUÍ
     */
    public static function desdeTypoCotizacion(TipoCotizacion $tipoCotizacion): self
    {
        // Si es Reflectivo o Logo → FUERZA bodega
        if ($tipoCotizacion->esReflectivo() || $tipoCotizacion->esLogo()) {
            return self::bodega();
        }

        // Para otros, default confección
        return self::confeccion();
    }

    public function esBodega(): bool
    {
        return $this->valor === self::BODEGA;
    }

    public function valor(): string
    {
        return $this->valor;
    }
}
```

```php
<?php // app/Domain/Prenda/ValueObjects/TipoCotizacion.php

namespace App\Domain\Prenda\ValueObjects;

class TipoCotizacion
{
    private const REFLECTIVO = 'Reflectivo';
    private const LOGO = 'Logo';
    private const BORDADO = 'Bordado';
    private const PRENDA = 'Prenda';

    public function __construct(private string $nombre)
    {
    }

    public function esReflectivo(): bool
    {
        return $this->nombre === self::REFLECTIVO;
    }

    public function esLogo(): bool
    {
        return $this->nombre === self::LOGO;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }
}
```

```php
<?php // app/Domain/Prenda/ValueObjects/Genero.php

namespace App\Domain\Prenda\ValueObjects;

class Genero
{
    private const DAMA = 1;
    private const CABALLERO = 2;
    private const UNISEX = 3;

    public function __construct(private int $id)
    {
        if (!in_array($id, [self::DAMA, self::CABALLERO, self::UNISEX])) {
            throw new \InvalidArgumentException("Género inválido: {$id}");
        }
    }

    public static function DAMA(): self
    {
        return new self(self::DAMA);
    }

    public static function CABALLERO(): self
    {
        return new self(self::CABALLERO);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return match($this->id) {
            self::DAMA => 'DAMA',
            self::CABALLERO => 'CABALLERO',
            self::UNISEX => 'UNISEX',
        };
    }
}
```

---

## 🏗️ AGGREGATE ROOT - Prenda

```php
<?php // app/Domain/Prenda/Entities/Prenda.php

namespace App\Domain\Prenda\Entities;

use App\Domain\Prenda\ValueObjects\{PrendaId, Origen, Genero, TipoCotizacion};
use App\Domain\Prenda\Events\PrendaCreada;

class Prenda
{
    private PrendaId $id;
    private string $nombre;
    private string $descripcion;
    private Origen $origen;
    private array $telas = [];
    private array $variaciones = [];
    private array $procesos = [];
    private array $tallas = [];
    private array $imagenesIds = [];
    private array $domainEvents = [];

    private function __construct(
        PrendaId $id,
        string $nombre,
        Origen $origen
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->origen = $origen;
    }

    /**
     * CREAR nueva prenda
     * ✨ Lógica de negocio: asignar origen según cotización
     */
    public static function crearParaCotizacion(
        PrendaId $id,
        string $nombre,
        string $descripcion,
        TipoCotizacion $tipoCotizacion,
        ?TipoCotizacion $cotizacion = null
    ): self {
        // LÓGICA: Decidir origen según tipo de cotización
        $origen = Origen::desdeTypoCotizacion($tipoCotizacion);

        $prenda = new self($id, $nombre, $origen);
        $prenda->descripcion = $descripcion;

        // Registrar evento de dominio
        $prenda->agregarEvento(
            new PrendaCreada($id, $nombre, $origen->valor())
        );

        return $prenda;
    }

    /**
     * Validar que prenda es consistente
     * ✨ Lógica de validación de negocio
     */
    public function validar(): array
    {
        $errores = [];

        if (empty($this->nombre)) {
            $errores[] = 'El nombre de la prenda es obligatorio';
        }

        if (empty($this->telas)) {
            $errores[] = 'Debe agregar al menos una tela';
        }

        // Validación específica: si es bodega, debe tener procesos
        if ($this->origen->esBodega() && empty($this->procesos)) {
            $errores[] = 'Las prendas de bodega deben tener procesos asociados';
        }

        return $errores;
    }

    /**
     * Agregar tela
     */
    public function agregarTela(Tela $tela): void
    {
        $this->telas[] = $tela;
    }

    /**
     * Establecer variaciones
     */
    public function establecerVariaciones(array $variaciones): void
    {
        $this->variaciones = $variaciones;
    }

    /**
     * Establecer procesos
     */
    public function establecerProcesos(array $procesos): void
    {
        $this->procesos = $procesos;
    }

    /**
     * Registrar evento de dominio
     */
    private function agregarEvento(object $evento): void
    {
        $this->domainEvents[] = $evento;
    }

    // Getters para presentar datos
    public function id(): PrendaId { return $this->id; }
    public function nombre(): string { return $this->nombre; }
    public function descripcion(): string { return $this->descripcion; }
    public function origen(): Origen { return $this->origen; }
    public function telas(): array { return $this->telas; }
    public function variaciones(): array { return $this->variaciones; }
    public function procesos(): array { return $this->procesos; }
    public function tallas(): array { return $this->tallas; }
    public function obtenerEventos(): array { return $this->domainEvents; }
}
```

---

## 🛠️ DOMAIN SERVICES - Lógica de Negocio

```php
<?php // app/Domain/Prenda/Services/AplicarOrigenAutomaticoDomainService.php

namespace App\Domain\Prenda\Services;

use App\Domain\Prenda\ValueObjects\{Origen, TipoCotizacion};
use App\Domain\Prenda\Entities\Prenda;

class AplicarOrigenAutomaticoDomainService
{
    /**
     * Aplicar origen automático a una prenda según la cotización
     * 
     * REGLA DE NEGOCIO:
     * - Si cotización es Reflectivo → origen = bodega
     * - Si cotización es Logo → origen = bodega
     * - Para el resto → origen = confección
     */
    public function ejecutar(
        Prenda $prenda,
        TipoCotizacion $tipoCotizacion
    ): Origen {
        $origen = Origen::desdeTypoCotizacion($tipoCotizacion);

        // Aquí podría haber más lógica de negocio
        // Por ejemplo: verificar disponibilidad en bodega
        // if ($origin->esBodega()) {
        //     $disponible = $this->bodejaRepository->verificarDisponibilidad($prenda);
        //     if (!$disponible) {
        //         throw new \DomainException("No hay disponibilidad en bodega");
        //     }
        // }

        return $origen;
    }
}
```

```php
<?php // app/Domain/Prenda/Services/ValidarPrendaDomainService.php

namespace App\Domain\Prenda\Services;

use App\Domain\Prenda\Entities\Prenda;

class ValidarPrendaDomainService
{
    /**
     * Validar que una prenda es válida
     * 
     * REGLAS DE NEGOCIO:
     * 1. Nombre es obligatorio
     * 2. Origen es obligatorio
     * 3. Debe tener al menos 1 tela
     * 4. Si origen = bodega → debe tener procesos
     * 5. Tallas deben ser coherentes
     */
    public function validar(Prenda $prenda): array
    {
        $errores = [];

        // Regla 1
        if (empty($prenda->nombre())) {
            $errores[] = 'El nombre de la prenda es obligatorio';
        }

        // Regla 2
        if (!$prenda->origen()) {
            $errores[] = 'El origen de la prenda es obligatorio';
        }

        // Regla 3
        if (empty($prenda->telas())) {
            $errores[] = 'Debe agregar al menos una tela';
        }

        // Regla 4
        if ($prenda->origen()->esBodega() && empty($prenda->procesos())) {
            $errores[] = 'Las prendas de bodega deben tener procesos asociados';
        }

        // Regla 5
        if (!$this->tallasValidas($prenda->tallas())) {
            $errores[] = 'Las tallas no son válidas';
        }

        return $errores;
    }

    private function tallasValidas(array $tallas): bool
    {
        // Lógica: verificar que tallas tienen estructura correcta
        if (empty($tallas)) return true;

        foreach ($tallas as $genero => $tallaData) {
            if (!isset($tallaData['DAMA'], $tallaData['CABALLERO'])) {
                return false;
            }
        }

        return true;
    }
}
```

```php
<?php // app/Domain/Prenda/Services/NormalizarDatosPrendaDomainService.php

namespace App\Domain\Prenda\Services;

use App\Domain\Prenda\Entities\Prenda;

class NormalizarDatosPrendaDomainService
{
    /**
     * Normalizar datos de prenda para presentar en frontend
     * 
     * Responsabilidades:
     * 1. Asegurar estructura consistente
     * 2. Transformar procesos (de objetos a array)
     * 3. Procesar imágenes
     * 4. Formatear tallas
     * 5. etc
     */
    public function normalizar(Prenda $prenda): array
    {
        return [
            'id' => $prenda->id()->valor(),
            'nombre_prenda' => $prenda->nombre(),
            'descripcion' => $prenda->descripcion(),
            'origen' => $prenda->origen()->valor(),
            'de_bodega' => $prenda->origen()->esBodega() ? 1 : 0,

            // Telas normalizadas
            'telasAgregadas' => $this->normalizarTelas($prenda->telas()),

            // Variaciones normalizadas
            'variacionesActuales' => $this->normalizarVariaciones($prenda->variaciones()),

            // Procesos normalizados
            'procesosSeleccionados' => $this->normalizarProcesos($prenda->procesos()),

            // Tallas en formato esperado
            'tallasRelacionales' => $this->normalizarTallas($prenda->tallas()),

            // Imágenes
            'imagenes' => $prenda->obtenerImagenes(),
        ];
    }

    private function normalizarTelas(array $telas): array
    {
        return array_map(function(Tela $tela) {
            return [
                'nombre_tela' => $tela->nombre(),
                'color' => $tela->color(),
                'referencia' => $tela->referencia(),
                'fotos' => $tela->obtenerFotoUrls(),
                'origen' => 'backend'
            ];
        }, $telas);
    }

    private function normalizarVariaciones(array $variaciones): array
    {
        // Transformar variaciones a formato esperado por frontend
        return [
            'genero_id' => $variaciones['genero']?->id(),
            'tipo_manga' => $variaciones['manga']?->nombre(),
            'obs_manga' => $variaciones['manga']?->observacion(),
            'tipo_broche' => $variaciones['broche']?->nombre(),
            'obs_broche' => $variaciones['broche']?->observacion(),
            'obs_bolsillos' => $variaciones['bolsillos']?->observacion(),
            'tiene_reflectivo' => $variaciones['reflectivo']?->aplicado() ?? false,
            'obs_reflectivo' => $variaciones['reflectivo']?->observacion(),
        ];
    }

    private function normalizarProcesos(array $procesos): array
    {
        // Convertir procesos a formato esperado
        $normalizados = [];
        foreach ($procesos as $proceso) {
            $normalizados[$proceso->slug()] = [
                'datos' => [
                    'id' => $proceso->id(),
                    'tipo' => $proceso->slug(),
                    'nombre' => $proceso->nombre(),
                    'ubicaciones' => $proceso->ubicaciones(),
                    'tallas' => $proceso->tallas(),
                    'imagenes' => $proceso->imagenes(),
                ]
            ];
        }
        return $normalizados;
    }

    private function normalizarTallas(array $tallas): array
    {
        return [
            'DAMA' => $tallas['dama'] ?? [],
            'CABALLERO' => $tallas['caballero'] ?? [],
            'UNISEX' => $tallas['unisex'] ?? [],
        ];
    }
}
```

---

## ⚙️ APPLICATION SERVICES - Orquestación CQS/CQRS

```php
<?php // app/Application/Services/ObtenerPrendaParaEdicionApplicationService.php

namespace App\Application\Services;

use App\Domain\Prenda\Repositories\PrendaRepositoryInterface;
use App\Domain\Prenda\Services\NormalizarDatosPrendaDomainService;
use App\Application\DTOs\ObtenerPrendaResponse;

class ObtenerPrendaParaEdicionApplicationService
{
    public function __construct(
        private PrendaRepositoryInterface $prendaRepository,
        private NormalizarDatosPrendaDomainService $normalizador
    ) {}

    /**
     * ORQUESTAR: Obtener prenda lista para edición
     * 
     * Pasos:
     * 1. Obtener prenda del repositorio (BD)
     * 2. Cargar relaciones (telas, procesos, etc)
     * 3. Normalizar usando domain service
     * 4. Retornar DTO
     */
    public function ejecutar(int $prendaId): ObtenerPrendaResponse
    {
        // Paso 1: Obtener del repositorio
        $prenda = $this->prendaRepository->porId($prendaId);

        if (!$prenda) {
            throw new \DomainException("Prenda {$prendaId} no encontrada");
        }

        // Paso 2: Cargar relaciones (Eager loading)
        $prenda->cargarTelas();
        $prenda->cargarProcesos();
        $prenda->cargarVariaciones();
        $prenda->cargarTallas();

        // Paso 3: Normalizar con domain service
        $prendaNormalizada = $this->normalizador->normalizar($prenda);

        // Paso 4: Retornar como DTO
        return new ObtenerPrendaResponse(
            exito: true,
            datos: $prendaNormalizada
        );
    }
}
```

```php
<?php // app/Application/Services/GuardarPrendaApplicationService.php

namespace App\Application\Services;

use App\Domain\Prenda\Repositories\PrendaRepositoryInterface;
use App\Domain\Prenda\Services\{ValidarPrendaDomainService, AplicarOrigenAutomaticoDomainService};
use App\Application\DTOs\{GuardarPrendaRequest, GuardarPrendaResponse};

class GuardarPrendaApplicationService
{
    public function __construct(
        private PrendaRepositoryInterface $prendaRepository,
        private ValidarPrendaDomainService $validador,
        private AplicarOrigenAutomaticoDomainService $aplicarOrigen
    ) {}

    /**
     * ORQUESTAR: Guardar prenda (crear o actualizar)
     * 
     * Pasos:
     * 1. Validación básica (datos no nulos)
     * 2. Crear/obtener entidad de dominio
     * 3. Aplicar lógica de negocio (origen automático, etc)
     * 4. Validar usando domain service
     * 5. Guardar en repositorio
     * 6. Publicar domain events
     */
    public function ejecutar(GuardarPrendaRequest $request): GuardarPrendaResponse
    {
        try {
            // Paso 1: Validación básica
            if (empty($request->nombre_prenda)) {
                return GuardarPrendaResponse::error('El nombre es obligatorio');
            }

            // Paso 2: Obtener o crear prenda
            if ($request->prendaId) {
                $prenda = $this->prendaRepository->porId($request->prendaId);
                if (!$prenda) {
                    return GuardarPrendaResponse::error('Prenda no encontrada');
                }
            } else {
                $prenda = Prenda::crearNueva(
                    PrendaId::generar(),
                    $request->nombre_prenda,
                    $request->descripcion
                );
            }

            // Paso 3: Aplicar lógica de negocio
            if ($request->cotizacion) {
                $tipoCotizacion = new TipoCotizacion($request->cotizacion->tipo);
                $origen = $this->aplicarOrigen->ejecutar($prenda, $tipoCotizacion);
                $prenda->establecerOrigen($origen);
            }

            // Paso 4: Validar
            $erroresValidacion = $this->validador->validar($prenda);
            if (!empty($erroresValidacion)) {
                return GuardarPrendaResponse::conErrores($erroresValidacion);
            }

            // Paso 5: Guardar en repositorio
            $this->prendaRepository->guardar($prenda);

            // Paso 6: Publicar eventos (pueden enviarse a message bus, etc)
            $this->publicarEventos($prenda);

            return GuardarPrendaResponse::exito('Prenda guardada correctamente', $prenda->id());

        } catch (\Exception $e) {
            \Log::error('Error guardando prenda: ' . $e->getMessage());
            return GuardarPrendaResponse::error('Error inesperado: ' . $e->getMessage());
        }
    }

    private function publicarEventos(Prenda $prenda): void
    {
        foreach ($prenda->obtenerEventos() as $evento) {
            // event($evento); // Laravel event dispatcher
        }
    }
}
```

---

## 🎛️ CONTROLLER - Punto de entrada HTTP

```php
<?php // app/Infrastructure/Controllers/PrendaController.php

namespace App\Infrastructure\Controllers;

use Illuminate\Http\Request;
use App\Application\Services\{
    ObtenerPrendaParaEdicionApplicationService,
    GuardarPrendaApplicationService
};
use App\Application\DTOs\GuardarPrendaRequest;

class PrendaController
{
    public function __construct(
        private ObtenerPrendaParaEdicionApplicationService $obtenerService,
        private GuardarPrendaApplicationService $guardarService
    ) {}

    /**
     * GET /api/prendas/{id}
     * 
     * Frontend llama: api.obtenerPrendaParaEdicion(id)
     * Backend retorna: PRENDA COMPLETAMENTE PROCESADA
     */
    public function show($id)
    {
        $response = $this->obtenerService->ejecutar($id);

        return response()->json($response->toArray(), $response->statusCode());
    }

    /**
     * POST /api/prendas
     * 
     * Frontend llama: api.guardarPrenda(datos)
     * Backend retorna: { exito, mensaje, errores }
     */
    public function store(Request $request)
    {
        $requestDTO = new GuardarPrendaRequest(
            nombre_prenda: $request->input('nombre_prenda'),
            descripcion: $request->input('descripcion'),
            origen: $request->input('origen'),
            telasAgregadas: $request->input('telasAgregadas', []),
            procesosSeleccionados: $request->input('procesosSeleccionados', []),
            variacionesActuales: $request->input('variacionesActuales', []),
            tallasRelacionales: $request->input('tallasRelacionales', []),
            cotizacion: $request->input('cotizacion'),
            prendaId: $request->input('prendaId')
        );

        $response = $this->guardarService->ejecutar($requestDTO);

        return response()->json($response->toArray(), $response->statusCode());
    }
}
```

---

## 📍 Routes

```php
<?php // routes/api.php

Route::prefix('api')->group(function () {
    // Obtener prenda para edición (TODO procesado)
    Route::get('/prendas/{id}', [PrendaController::class, 'show']);

    // Guardar prenda (validar + negocio + guardar)
    Route::post('/prendas', [PrendaController::class, 'store']);

    // Obtener prendas de cotización
    Route::get('/cotizaciones/{id}/prendas', [CotizacionController::class, 'prendas']);
});
```

---

## 🔄 Flujo Completo

### From Frontend:
```javascript
// Frontend (PrendaEditorOrchestrator)
const prenda = await api.obtenerPrendaParaEdicion(id);
// Backend maneja TODA la lógica
```

### Backend DDD:
```
1. Controller recibe request
   ↓
2. Application Service orquesta:
   - Repository obtiene entidad
   - Domain Service aplica lógica
   - Domain Service valida
   - Repository guarda
   ↓
3. Domain Models contienen:
   - Value Objects (tipos primitivos ricos)
   - Business rules (reglas de negocio)
   - Domain Events (eventos importantes)
   ↓
4. Controller retorna Response (DTO)
```

### Response ejemplo:
```json
{
  "exito": true,
  "datos": {
    "id": 1,
    "nombre_prenda": "Camisa Corporativa",
    "origen": "bodega",
    "telasAgregadas": [...],
    "variacionesActuales": {...},
    "procesosSeleccionados": {...},
    "tallasRelacionales": {...}
  }
}
```

---

## ✨ Ventajas DDD

| Aspecto | Beneficio |
|--------|-----------|
| **Single Source of Truth** | Lógica en Backend, usable desde API, Mobile, etc |
| **Testeable** | Domain Services únicamente con datos, sin HTTP |
| **Mantenible** | Cada clase una responsabilidad |
| **Escalable** | Agregar nuevas reglas sin afectar existentes |
| **Event Sourcing** | Domain Events para auditoría/integraciones |
| **Type Safe** | Value Objects garantizan tipos válidos |

