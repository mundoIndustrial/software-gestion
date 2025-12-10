<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class VerUsuarioActual extends Command
{
    protected $signature = 'ver:usuarios';
    protected $description = 'Ver todos los usuarios en la BD';

    public function handle()
    {
        $this->info('👥 USUARIOS EN LA BASE DE DATOS:');
        $this->line('');

        $usuarios = User::select('id', 'name', 'email', 'role_id', 'created_at')->get();

        if ($usuarios->count() > 0) {
            $this->table(
                ['ID', 'Nombre', 'Email', 'Role ID', 'Creado'],
                $usuarios->map(fn($u) => [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->role_id,
                    $u->created_at->format('d/m/Y H:i'),
                ])->toArray()
            );
        } else {
            $this->warn('No hay usuarios en la BD');
        }

        $this->line('');
        $this->info('💡 Para ver cotizaciones de un usuario, usa:');
        $this->line('   php artisan analizar:cotizaciones --usuario_id=ID');
    }
}
