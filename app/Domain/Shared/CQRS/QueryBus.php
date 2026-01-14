<?php

namespace App\Domain\Shared\CQRS;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * QueryBus - Despachador de Queries
 * 
 * Responsabilidades:
 * - Registrar handlers para queries
 * - Resolver el handler correcto para cada query
 * - Ejecutar el handler
 * - Manejar errores
 * 
 * Patrón: Command Bus / Service Locator
 * 
 * Ejemplo:
 * $bus->register(ObtenerPedidoQuery::class, ObtenerPedidoHandler::class);
 * $resultado = $bus->execute(new ObtenerPedidoQuery(123));
 */
class QueryBus
{
    /**
     * Map de queries a handlers
     * 
     * @var array<string, string>
     */
    private array $handlers = [];

    /**
     * Container de Laravel para resolver handlers
     */
    private \Illuminate\Contracts\Container\Container $container;

    public function __construct(\Illuminate\Contracts\Container\Container $container)
    {
        $this->container = $container;
    }

    /**
     * Registrar handler para una query
     * 
     * @param string $queryClass FQCN de la query
     * @param string $handlerClass FQCN del handler
     * @return void
     */
    public function register(string $queryClass, string $handlerClass): void
    {
        $this->handlers[$queryClass] = $handlerClass;
        
        Log::debug('📋 QueryBus: Handler registrado', [
            'query' => $queryClass,
            'handler' => $handlerClass,
        ]);
    }

    /**
     * Ejecutar una query
     * 
     * @param Query $query
     * @return mixed Resultado de la query
     * @throws Exception Si no hay handler para la query
     */
    public function execute(Query $query): mixed
    {
        $queryClass = get_class($query);

        if (!isset($this->handlers[$queryClass])) {
            throw new Exception("No handler registrado para query: $queryClass");
        }

        $handlerClass = $this->handlers[$queryClass];

        try {
            Log::info('🔍 [QueryBus] Ejecutando query', [
                'query' => class_basename($query),
                'handler' => class_basename($handlerClass),
            ]);

            // Resolver el handler del contenedor
            $handler = $this->container->make($handlerClass);

            // Ejecutar el handler
            $resultado = $handler->handle($query);

            Log::info('✅ [QueryBus] Query ejecutada exitosamente', [
                'query' => class_basename($query),
                'resultado_type' => gettype($resultado),
            ]);

            return $resultado;

        } catch (Exception $e) {
            Log::error('❌ [QueryBus] Error ejecutando query', [
                'query' => $queryClass,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtener todos los handlers registrados
     * 
     * @return array<string, string>
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}
