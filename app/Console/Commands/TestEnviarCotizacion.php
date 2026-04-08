<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Application\Cotizacion\Commands\CrearCotizacionCommand;
use App\Application\Cotizacion\DTOs\CrearCotizacionDTO;
use App\Application\Cotizacion\Handlers\Commands\CrearCotizacionHandler;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCotizacionRepository;
use App\Application\Services\CotizacionPrendaService;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TestEnviarCotizacion extends Command
{
    protected $signature = 'test:enviar-cotizacion';
    protected $description = 'Test full flow: save draft and send with numero_cotizacion';

    public function handle()
    {
        $this->info(' TEST: Enviar cotización con número secuencial');
        
        // Obtener un usuario (asesor)
        $usuario = User::first();
        if (!$usuario) {
            $this->error(' No hay usuarios en la BD');
            return;
        }
        
        $this->info("Usuario: {$usuario->name} (ID: {$usuario->id})");
        
        // 1. GUARDAR COMO BORRADOR
        $this->info("\n PASO 1: Guardar como BORRADOR...");
        
        $dtoEnvio = CrearCotizacionDTO::desdeArray([
            'usuario_id' => $usuario->id,
            'tipo' => 'P',
            'cliente_id' => null,
            'tipo_venta' => 'M',
            'es_borrador' => true,
            'estado' => 'BORRADOR',
            'numero_cotizacion' => null,
            'especificaciones' => [],
            'prendas' => [],
            'logo' => [],
        ]);
        
        $comando = CrearCotizacionCommand::crear($dtoEnvio);
        $handler = app(CrearCotizacionHandler::class);
        
        try {
            $result = $handler->handle($comando);
            $this->info(" Borrador guardado: " . $result->toArray()['id']);
        } catch (\Exception $e) {
            $this->error(" Error al guardar borrador: " . $e->getMessage());
            return;
        }
        
        // 2. ENVIAR COTIZACIÓN - Simular lo que hace el Controller
        $this->info("\n📨 PASO 2: Enviar cotización (generar número como hace Controller)...");
        
        // Buscar el último número (como hace el Controller)
        $ultimaCotizacion = \App\Models\Cotizacion::whereNotNull('numero_cotizacion')
            ->orderBy('numero_cotizacion', 'desc')
            ->first();
        
        $ultimoSecuencial = 0;
        if ($ultimaCotizacion) {
            if (preg_match('/COT-(\d+)/', $ultimaCotizacion->numero_cotizacion, $matches)) {
                $ultimoSecuencial = (int)$matches[1];
            }
        }
        
        $nuevoSecuencial = $ultimoSecuencial + 1;
        $this->info("   Último secuencial: {$ultimoSecuencial}, Nuevo: {$nuevoSecuencial}");
        
        // Crear DTO CON el número generado (como hace el Controller)
        $dtoEnvio = CrearCotizacionDTO::desdeArray([
            'usuario_id' => $usuario->id,
            'tipo' => 'P',
            'cliente_id' => null,
            'tipo_venta' => 'M',
            'es_borrador' => false,
            'estado' => 'ENVIADA_CONTADOR',
            'numero_cotizacion' => $nuevoSecuencial, // ← El número generado por el Controller
            'especificaciones' => [],
            'prendas' => [],
            'logo' => [],
        ]);
        
        $this->info("   DTO numeroCotizacion: " . ($dtoEnvio->numeroCotizacion ?? 'NULL'));
        
        $comando = CrearCotizacionCommand::crear($dtoEnvio);
        
        try {
            $result = $handler->handle($comando);
            $resultArray = $result->toArray();
            
            $this->info(" Cotización enviada:");
            $this->info("   ID: {$resultArray['id']}");
            $this->info("   Estado: {$resultArray['estado']}");
            $this->info("   Número: {$resultArray['numero_cotizacion']}");
            $this->info("   EsBorrador: " . ($resultArray['es_borrador'] ? 'sí' : 'no'));
            
            // Verificar en BD
            $cotizacion = \App\Models\Cotizacion::find($resultArray['id']);
            $this->info("\n Verificación en BD:");
            $this->info("   ID: {$cotizacion->id}");
            $this->info("   Número: {$cotizacion->numero_cotizacion}");
            $this->info("   Estado: {$cotizacion->estado}");
            $this->info("   EsBorrador: " . ($cotizacion->es_borrador ? 'sí' : 'no'));
            
        } catch (\Exception $e) {
            $this->error(" Error al enviar: " . $e->getMessage());
            Log::error('Error test enviar:', ['error' => $e]);
            return;
        }
        
        // 3. VERIFICAR NÚMEROS
        $this->info("\n VERIFICACIÓN DE NÚMEROS:");
        $cotizaciones = \App\Models\Cotizacion::whereNotNull('numero_cotizacion')
            ->orderBy('numero_cotizacion', 'desc')
            ->limit(5)
            ->get(['id', 'numero_cotizacion', 'estado']);
        
        foreach ($cotizaciones as $cot) {
            $this->line("   - ID: {$cot->id}, Número: {$cot->numero_cotizacion}, Estado: {$cot->estado}");
        }
        
        $this->info("\n TEST COMPLETADO");
    }
}
