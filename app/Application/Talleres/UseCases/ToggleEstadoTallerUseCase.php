<?php

namespace App\Application\Talleres\UseCases;

use Illuminate\Support\Facades\DB;
use App\Models\User;

class ToggleEstadoTallerUseCase
{
    public function execute(int $userId)
    {
        $user = User::findOrFail($userId);
        
        $config = DB::table('taller_config')
            ->where('user_id', $userId)
            ->first();
        
        if ($config) {
            $nuevoEstado = $config->activo ? 0 : 1;
            DB::table('taller_config')
                ->where('user_id', $userId)
                ->update([
                    'activo' => $nuevoEstado,
                    'updated_at' => now()
                ]);
        } else {
            $nuevoEstado = 0; // Si no existía, el default era 1, así que lo ponemos en 0
            DB::table('taller_config')->insert([
                'user_id' => $userId,
                'activo' => $nuevoEstado,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return [
            'success' => true,
            'activo' => (bool)$nuevoEstado,
            'message' => $nuevoEstado ? 'Taller activado correctamente' : 'Taller desactivado correctamente'
        ];
    }
}
