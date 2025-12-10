<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;
use App\Application\Cotizacion\Queries\ListarCotizacionesQuery;
use App\Application\Cotizacion\Handlers\Queries\ListarCotizacionesHandler;

class DebugCotizaciones extends Command
{
    protected $signature = 'debug:cotizaciones {usuario_id : ID del usuario}';
    protected $description = 'Debuggear cotizaciones del usuario';

    public function handle()
    {
        $usuarioId = $this->argument('usuario_id');

        $this->info("🔍 DEBUGGEANDO COTIZACIONES DEL USUARIO {$usuarioId}");
        $this->line('');

        // 1. Verificar cotizaciones en BD
        $this->line('1️⃣ COTIZACIONES EN LA BD:');
        $cotizacionesBD = Cotizacion::where('asesor_id', $usuarioId)->get();
        $this->info("   Total: {$cotizacionesBD->count()}");
        
        if ($cotizacionesBD->count() > 0) {
            $this->table(
                ['ID', 'Número', 'Tipo ID', 'Cliente', 'Borrador', 'Estado'],
                $cotizacionesBD->take(5)->map(fn($c) => [
                    $c->id,
                    $c->numero_cotizacion ?? 'N/A',
                    $c->tipo_cotizacion_id,
                    substr($c->cliente, 0, 20),
                    $c->es_borrador ? 'Sí' : 'No',
                    $c->estado,
                ])->toArray()
            );
        }

        $this->line('');

        // 2. Verificar relación tipoCotizacion
        $this->line('2️⃣ RELACIÓN TIPO_COTIZACION:');
        $cotizacionConTipo = Cotizacion::where('asesor_id', $usuarioId)
            ->with('tipoCotizacion')
            ->first();

        if ($cotizacionConTipo) {
            $this->info("   Primera cotización (ID: {$cotizacionConTipo->id}):");
            $this->info("   - tipo_cotizacion_id: {$cotizacionConTipo->tipo_cotizacion_id}");
            if ($cotizacionConTipo->tipoCotizacion) {
                $this->info("   - Relación cargada: SÍ");
                $this->info("   - Código: {$cotizacionConTipo->tipoCotizacion->codigo}");
                $this->info("   - Nombre: {$cotizacionConTipo->tipoCotizacion->nombre}");
            } else {
                $this->warn("   - Relación cargada: NO");
            }
        }

        $this->line('');

        // 3. Verificar Handler
        $this->line('3️⃣ EJECUTAR HANDLER:');
        try {
            $handler = app(\App\Application\Cotizacion\Handlers\Queries\ListarCotizacionesHandler::class);
            $query = ListarCotizacionesQuery::crear(
                usuarioId: $usuarioId,
                soloEnviadas: false,
                soloBorradores: false,
                pagina: 1,
                porPagina: 100,
            );

            $dtos = $handler->handle($query);
            $this->info("   DTOs obtenidos: " . count($dtos));

            if (count($dtos) > 0) {
                $this->info("   Primer DTO:");
                $dto = $dtos[0];
                $this->info("   - ID: {$dto->id}");
                $this->info("   - Tipo: {$dto->tipo}");
                $this->info("   - Cliente: {$dto->cliente}");
                $this->info("   - Es Borrador: " . ($dto->esBorrador ? 'Sí' : 'No'));
            }
        } catch (\Exception $e) {
            $this->error("   Error: {$e->getMessage()}");
            $this->error("   Trace: {$e->getTraceAsString()}");
        }

        $this->line('');

        // 4. Verificar logs
        $this->line('4️⃣ VERIFICAR LOGS:');
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $this->info("   Archivo de log: {$logFile}");
            $this->info("   Tamaño: " . filesize($logFile) . " bytes");
            $this->info("   Últimas 10 líneas:");
            $lines = array_slice(file($logFile), -10);
            foreach ($lines as $line) {
                $this->line("   " . trim($line));
            }
        } else {
            $this->warn("   Archivo de log no encontrado");
        }

        $this->line('');
        $this->info('✅ Debug completado');
    }
}
