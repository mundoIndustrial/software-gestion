<?php

namespace App\Application\Talleres\UseCases;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CrearTallerUseCase
{
    public function execute(array $data)
    {
        $roleTaller = Role::where('name', 'taller')->first();
        
        if (!$roleTaller) {
            return ['success' => false, 'message' => 'El rol de taller no existe en el sistema.'];
        }

        try {
            DB::beginTransaction();

            $name = $data['name'];
            $email = $data['email'] ?? 'taller_' . strtolower(str_replace(' ', '_', $name)) . '_' . time() . '@mundoindustrial.com';
            $password = $data['password'] ?? \Illuminate\Support\Str::random(12);

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'roles_ids' => [$roleTaller->id]
            ]);

            DB::commit();

            return [
                'success' => true, 
                'message' => 'Taller creado correctamente.',
                'taller' => $user
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Error al crear el taller: ' . $e->getMessage()];
        }
    }
}
