<?php

namespace App\Domain\Shared\CQRS;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * CommandBus - Despachador de Commands
 * 
 * Responsabilidades:
 * - Registrar handlers para commands
 * - Resolver el handler correcto para cada command
 * - Ejecutar el handler en transacción
 * - Emitir eventos después de ejecutar
 * - Manejar errores
 * 
 * Patrón: Command Bus / Service Locator
 * 
 * Ejemplo:
 * $bus->register(CrearPedidoCommand::class, CrearPedidoHandler::class);
 * $resultado = $bus->execute(new CrearPedidoCommand(...));
 */
class CommandBus
{
    /**
     * Map de commands a handlers
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
     * Registrar handler para un command
     * 
     * @param string $commandClass FQCN del command
     * @param string $handlerClass FQCN del handler
     * @return void
     */
    public function register(string $commandClass, string $handlerClass): void
    {
        $this->handlers[$commandClass] = $handlerClass;
        
        Log::debug('📝 CommandBus: Handler registrado', [
            'command' => $commandClass,
            'handler' => $handlerClass,
        ]);
    }

    /**
     * Ejecutar un command
     * 
     * @param Command $command
     * @return mixed Resultado del command (usualmente el agregado modificado)
     * @throws Exception Si no hay handler para el command
     */
    public function execute(Command $command): mixed
    {
        $commandClass = get_class($command);

        if (!isset($this->handlers[$commandClass])) {
            throw new Exception("No handler registrado para command: $commandClass");
        }

        $handlerClass = $this->handlers[$commandClass];

        try {
            Log::info('⚡ [CommandBus] Ejecutando command', [
                'command' => class_basename($command),
                'handler' => class_basename($handlerClass),
            ]);

            // Ejecutar en transacción
            $resultado = \DB::transaction(function () use ($handlerClass, $command) {
                // Resolver el handler del contenedor
                $handler = $this->container->make($handlerClass);

                // Ejecutar el handler
                return $handler->handle($command);
            });

            Log::info('✅ [CommandBus] Command ejecutado exitosamente', [
                'command' => class_basename($command),
                'resultado_type' => gettype($resultado),
            ]);

            return $resultado;

        } catch (Exception $e) {
            Log::error('❌ [CommandBus] Error ejecutando command', [
                'command' => $commandClass,
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
