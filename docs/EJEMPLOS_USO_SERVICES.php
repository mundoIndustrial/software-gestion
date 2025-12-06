<?php

/**
 * Ejemplos de Uso - Services de Pedidos en Diferentes Contextos
 * 
 * Los Services que creamos son reutilizables en cualquier contexto:
 * - API REST
 * - CLI Commands
 * - Jobs/Queues
 * - WebSockets
 * - GraphQL
 */

// ============================================================
// EJEMPLO 1: Usar en API REST
// ============================================================

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Pedidos\CotizacionSearchService;
use Illuminate\Http\JsonResponse;

class CotizacionApiController extends Controller
{
    public function __construct(
        private CotizacionSearchService $cotizacionSearch,
    ) {}

    /**
     * GET /api/cotizaciones
     * Obtener todas las cotizaciones como JSON
     */
    public function index(): JsonResponse
    {
        $cotizaciones = $this->cotizacionSearch->obtenerTodas();

        return response()->json([
            'data' => $cotizaciones->map(fn($cot) => $cot->toArray()),
            'total' => $cotizaciones->count(),
        ]);
    }

    /**
     * GET /api/cotizaciones/search?q=cliente
     * Buscar cotizaciones
     */
    public function search(Request $request): JsonResponse
    {
        $todas = $this->cotizacionSearch->obtenerTodas();
        $resultados = $this->cotizacionSearch->filtrarPorTermino(
            $todas,
            $request->query('q', '')
        );

        return response()->json([
            'data' => $resultados->map(fn($cot) => $cot->toArray()),
            'total' => $resultados->count(),
        ]);
    }
}

// ============================================================
// EJEMPLO 2: Usar en CLI Command
// ============================================================

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Pedidos\CotizacionSearchService;
use App\Models\Cotizacion;

class ListarCotizacionesCommand extends Command
{
    protected $signature = 'cotizaciones:listar {--asesor=}';
    protected $description = 'Lista cotizaciones por asesor';

    public function __construct(
        private CotizacionSearchService $cotizacionSearch,
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $asesorNombre = $this->option('asesor');

        if ($asesorNombre) {
            $cotizaciones = $this->cotizacionSearch->obtenerPorAsesor($asesorNombre);
            $this->info("Cotizaciones de {$asesorNombre}:");
        } else {
            $cotizaciones = $this->cotizacionSearch->obtenerTodas();
            $this->info("Todas las cotizaciones:");
        }

        $headers = ['ID', 'Número', 'Cliente', 'Asesora', 'Prendas'];
        $rows = $cotizaciones->map(fn($cot) => [
            $cot->id,
            $cot->numero,
            $cot->cliente,
            $cot->asesora,
            $cot->prendasCount,
        ])->toArray();

        $this->table($headers, $rows);
        $this->info("Total: {$cotizaciones->count()} cotizaciones");
    }
}

// Uso:
// php artisan cotizaciones:listar
// php artisan cotizaciones:listar --asesor="Juan"

// ============================================================
// EJEMPLO 3: Usar en Job (Procesamiento Async)
// ============================================================

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Pedidos\PedidoProduccionCreatorService;
use App\DTOs\CrearPedidoProduccionDTO;
use App\Mail\PedidoCreatedMail;
use Illuminate\Support\Facades\Mail;
use Log;

class CrearPedidoProduccionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $asesorId,
        private array $requestData,
    ) {}

    /**
     * Ejecuta el job de forma asincrónica
     * Se ejecuta cuando el worker procesa la queue
     */
    public function handle(PedidoProduccionCreatorService $creatorService): void
    {
        try {
            Log::info('Iniciando creación de pedido', [
                'asesor_id' => $this->asesorId,
                'cotizacion_id' => $this->requestData['cotizacion_id'] ?? null,
            ]);

            // Crear DTO desde datos
            $dto = CrearPedidoProduccionDTO::fromRequest($this->requestData);

            // Crear pedido
            $pedido = $creatorService->crear($dto, $this->asesorId);

            // Enviar email de confirmación
            Mail::queue(
                new PedidoCreatedMail($pedido)
            );

            Log::info('Pedido creado exitosamente', [
                'pedido_id' => $pedido->id,
                'asesor_id' => $this->asesorId,
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando pedido', [
                'error' => $e->getMessage(),
                'asesor_id' => $this->asesorId,
            ]);
            throw $e; // Fallar y reintentar
        }
    }
}

// Uso en controller:
// CrearPedidoProduccionJob::dispatch($asesorId, $requestData);

// ============================================================
// EJEMPLO 4: Usar en Listener (Event Listener)
// ============================================================

namespace App\Listeners;

use App\Events\CotizacionAprobada;
use App\Services\Pedidos\CotizacionSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Log;

class CrearPedidoAlAprobarCotizacion implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private CotizacionSearchService $cotizacionSearch,
    ) {}

    /**
     * Cuando se aprueba una cotización, crear automáticamente un pedido
     */
    public function handle(CotizacionAprobada $event): void
    {
        $cotizacion = $this->cotizacionSearch->obtenerPorId($event->cotizacionId);

        if ($cotizacion) {
            Log::info('Cotización aprobada, preparando para crear pedido', [
                'cotizacion_id' => $cotizacion->id,
                'cliente' => $cotizacion->cliente,
            ]);

            // Aquí podrías disparar el job o crear el pedido directamente
            // CrearPedidoProduccionJob::dispatch($event->asesorId, $requestData);
        }
    }
}

// ============================================================
// EJEMPLO 5: Usar en Dashboard / Analytics
// ============================================================

namespace App\Services\Dashboard;

use App\Services\Pedidos\CotizacionSearchService;
use Illuminate\Support\Collection;

class DashboardAnalyticsService
{
    public function __construct(
        private CotizacionSearchService $cotizacionSearch,
    ) {}

    /**
     * Obtener estadísticas de cotizaciones por asesor
     */
    public function cotizacionesPorAsesor(): Collection
    {
        $cotizaciones = $this->cotizacionSearch->obtenerTodas();

        return $cotizaciones
            ->groupBy('asesora')
            ->map(function ($grupo, $asesor) {
                return [
                    'asesor' => $asesor,
                    'total_cotizaciones' => $grupo->count(),
                    'total_prendas' => $grupo->sum('prendasCount'),
                    'clientes_unicos' => $grupo->pluck('cliente')->unique()->count(),
                ];
            });
    }

    /**
     * Top 5 asesores por cotizaciones
     */
    public function topAsesores(): Collection
    {
        return $this->cotizacionesPorAsesor()
            ->sortByDesc('total_cotizaciones')
            ->take(5);
    }
}

// Uso:
// $analytics = new DashboardAnalyticsService($cotizacionSearch);
// $topAsesores = $analytics->topAsesores();

// ============================================================
// EJEMPLO 6: Usar en GraphQL Resolver
// ============================================================

namespace App\GraphQL\Resolvers;

use App\Services\Pedidos\CotizacionSearchService;
use GraphQL\Type\Definition\ResolveInfo;

class CotizacionResolver
{
    public function __construct(
        private CotizacionSearchService $cotizacionSearch,
    ) {}

    /**
     * Query: cotizaciones
     */
    public function cotizaciones($root, array $args, $context, ResolveInfo $info)
    {
        $todas = $this->cotizacionSearch->obtenerTodas();

        // Filtrar por asesor si se proporciona
        if (!empty($args['asesor'] ?? null)) {
            $todas = $this->cotizacionSearch->filtrarPorTermino(
                $todas,
                $args['asesor']
            );
        }

        return $todas->map(fn($cot) => $cot->toArray());
    }

    /**
     * Query: cotizacion(id)
     */
    public function cotizacion($root, array $args, $context, ResolveInfo $info)
    {
        $cotizacion = $this->cotizacionSearch->obtenerPorId($args['id']);
        return $cotizacion?->toArray();
    }
}

// ============================================================
// EJEMPLO 7: Usar en WebSocket / Realtime
// ============================================================

namespace App\WebSocket\Handlers;

use App\Services\Pedidos\CotizacionSearchService;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Illuminate\Support\Facades\Cache;

class CotizacionBroadcaster implements MessageComponentInterface
{
    protected $clients;

    public function __construct(
        private CotizacionSearchService $cotizacionSearch,
    ) {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        
        // Enviar cotizaciones al cliente conectado
        $cotizaciones = $this->cotizacionSearch->obtenerTodas();
        $conn->send(json_encode([
            'type' => 'COTIZACIONES_LOADED',
            'data' => $cotizaciones->map(fn($c) => $c->toArray()),
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        if ($data['type'] === 'SEARCH') {
            $todas = $this->cotizacionSearch->obtenerTodas();
            $resultados = $this->cotizacionSearch->filtrarPorTermino(
                $todas,
                $data['query']
            );

            $from->send(json_encode([
                'type' => 'SEARCH_RESULTS',
                'data' => $resultados->map(fn($c) => $c->toArray()),
            ]));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {}
}

// ============================================================
// EJEMPLO 8: Extensión con Caché
// ============================================================

namespace App\Services\Pedidos;

use App\Models\Cotizacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Extiende CotizacionSearchService con caché (OCP)
 */
class CotizacionSearchCachedService extends CotizacionSearchService
{
    private const CACHE_KEY_TODAS = 'cotizaciones_todas';
    private const CACHE_TTL = 3600; // 1 hora

    public function obtenerTodas(): Collection
    {
        return Cache::remember(self::CACHE_KEY_TODAS, self::CACHE_TTL, function () {
            return parent::obtenerTodas();
        });
    }

    public function obtenerPorAsesor(string $nombreAsesor): Collection
    {
        $cacheKey = "cotizaciones_asesor_{$nombreAsesor}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($nombreAsesor) {
            return parent::obtenerPorAsesor($nombreAsesor);
        });
    }

    public function limpiarCache(): void
    {
        Cache::forget(self::CACHE_KEY_TODAS);
        Cache::tags('cotizaciones')->flush();
    }
}

// Usar en Service Provider:
// $this->app->bind(CotizacionSearchService::class, CotizacionSearchCachedService::class);

// ============================================================
// CONCLUSIÓN
// ============================================================

/*
 * Los Services creados son reutilizables en:
 * ✅ Controllers REST API
 * ✅ CLI Commands
 * ✅ Jobs/Queues para procesamiento async
 * ✅ Event Listeners para lógica reactiva
 * ✅ Dashboard/Analytics
 * ✅ GraphQL Resolvers
 * ✅ WebSocket Handlers
 * ✅ Extensibles con herencia (OCP)
 * ✅ Cacheables
 * ✅ Testables
 *
 * Una sola implementación, múltiples contextos.
 * ¡Eso es arquitectura SOLID!
 */
