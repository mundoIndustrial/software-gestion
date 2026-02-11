<?php

namespace App\Application\Bodega\CQRS\Queries;

/**
 * Query Bus - Despacha queries a sus handlers correspondientes
 * Optimizado para operaciones de lectura con caching opcional
 */
class QueryBus
{
    private array $handlers = [];
    private array $cache = [];
    private int $cacheTtl;

    public function __construct(int $cacheTtl = 300) // 5 minutos por defecto
    {
        $this->cacheTtl = $cacheTtl;
    }

    /**
     * Registrar un handler para un tipo de query
     */
    public function register(string $queryClass, callable $handler): void
    {
        $this->handlers[$queryClass] = $handler;
    }

    /**
     * Despachar una query a su handler
     */
    public function ask(QueryInterface $query): mixed
    {
        $queryClass = get_class($query);
        $queryId = $query->getQueryId();

        // Verificar cache primero
        if ($this->isCached($queryId)) {
            return $this->getCached($queryId);
        }

        if (!isset($this->handlers[$queryClass])) {
            throw new \InvalidArgumentException("No handler registered for query: {$queryClass}");
        }

        // Validar la query antes de despachar
        $query->validate();

        // Ejecutar el handler
        $result = ($this->handlers[$queryClass])($query);

        // Cache el resultado
        $this->cache($queryId, $result);

        return $result;
    }

    /**
     * Verificar si un resultado está en cache
     */
    private function isCached(string $queryId): bool
    {
        if (!isset($this->cache[$queryId])) {
            return false;
        }

        $cached = $this->cache[$queryId];
        return (time() - $cached['timestamp']) < $this->cacheTtl;
    }

    /**
     * Obtener resultado desde cache
     */
    private function getCached(string $queryId): mixed
    {
        return $this->cache[$queryId]['data'];
    }

    /**
     * Guardar resultado en cache
     */
    private function cache(string $queryId, mixed $data): void
    {
        $this->cache[$queryId] = [
            'data' => $data,
            'timestamp' => time()
        ];
    }

    /**
     * Limpiar cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Limpiar cache específica
     */
    public function clearCacheFor(string $queryId): void
    {
        unset($this->cache[$queryId]);
    }

    /**
     * Verificar si hay un handler registrado
     */
    public function hasHandler(string $queryClass): bool
    {
        return isset($this->handlers[$queryClass]);
    }

    /**
     * Obtener todos los handlers registrados
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}
