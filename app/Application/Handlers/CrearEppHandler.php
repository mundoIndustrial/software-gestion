<?php

namespace App\Application\Handlers;

use App\Application\Commands\CrearEppCommand;
use App\Models\Epp;
use App\Models\EppCategoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CrearEppHandler
 *
 * Application Handler que procesa el comando CrearEppCommand.
 * Coordina la lógica de aplicación para crear un nuevo EPP.
 *
 * Responsabilidad: Orquestar la creación de un EPP usando el modelo
 */
class CrearEppHandler
{
    /**
     * Maneja el comando de creación de EPP
     *
     * @param CrearEppCommand $command
     * @return array EPP creado con su data
     * @throws \Exception Si hay error al crear
     */
    public function handle(CrearEppCommand $command): array
    {
        try {
            Log::info('CrearEppHandler - Iniciando', [
                'nombre' => $command->nombre,
                'categoria' => $command->categoria,
                'codigo' => $command->codigo
            ]);

            // Buscar o crear categoría - buscar por nombre o por código
            Log::info('CrearEppHandler - Buscando categoría: ' . $command->categoria);
            
            $codigoCat = strtoupper(str_replace(' ', '_', $command->categoria));
            
            // Buscar por nombre primero, luego por código
            $categoria = EppCategoria::where('nombre', $command->categoria)
                ->orWhere('codigo', $codigoCat)
                ->first();
            
            if (!$categoria) {
                Log::info('CrearEppHandler - Categoría no encontrada, creando nueva');
                try {
                    $categoria = EppCategoria::create([
                        'nombre' => $command->categoria,
                        'codigo' => $codigoCat,
                        'activo' => true
                    ]);
                    Log::info('CrearEppHandler - Categoría creada', ['id' => $categoria->id]);
                } catch (\Exception $e) {
                    // Si falla por duplicate, buscar nuevamente
                    Log::warning('CrearEppHandler - Error creando categoría, buscando nuevamente', ['error' => $e->getMessage()]);
                    $categoria = EppCategoria::where('nombre', $command->categoria)
                        ->orWhere('codigo', $codigoCat)
                        ->first();
                    
                    if (!$categoria) {
                        throw $e;
                    }
                }
            } else {
                Log::info('CrearEppHandler - Categoría encontrada', ['id' => $categoria->id]);
            }

            // Crear el EPP
            Log::info('CrearEppHandler - Creando EPP con categoria_id: ' . $categoria->id);
            
            $epp = Epp::create([
                'nombre' => $command->nombre,
                'categoria_id' => $categoria->id,
                'codigo' => $command->codigo,
                'descripcion' => $command->descripcion,
                'activo' => true
            ]);

            Log::info('CrearEppHandler - EPP creado', ['epp_id' => $epp->id]);

            return [
                'id' => $epp->id,
                'nombre' => $epp->nombre,
                'categoria' => $command->categoria,
                'codigo' => $epp->codigo,
                'descripcion' => $epp->descripcion,
                'activo' => $epp->activo,
                'imagenes' => []
            ];

        } catch (\Exception $e) {
            Log::error('CrearEppHandler - Error fatal', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
