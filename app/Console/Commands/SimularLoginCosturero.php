<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class SimularLoginCosturero extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simulate:login-costurero {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simular login de un usuario costurero';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? 'yus55@gmail.com';
        
        $this->line('');
        $this->line('=== SIMULANDO LOGIN PARA COSTURERO ===');
        $this->line('');

        // Buscar usuario
        $this->info("Buscando usuario: {$email}");
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error(" Usuario NO encontrado: {$email}");
            return;
        }

        $this->line(" Usuario encontrado: {$user->name} (ID: {$user->id})");
        $this->line('');

        // Verificar roles
        $this->info('Verificando roles:');
        $this->line("   - hasRole('costurero'): " . ($user->hasRole('costurero') ? ' true' : ' false'));
        $this->line("   - hasAnyRole(['cortador', 'costurero']): " . ($user->hasAnyRole(['cortador', 'costurero']) ? ' true' : ' false'));
        $this->line('');

        // Simular el middleware OperarioAccess
        $this->info('Simulando middleware OperarioAccess:');
        
        if (!$user->hasAnyRole(['cortador', 'costurero'])) {
            $this->error("    MIDDLEWARE BLOQUEADO: Usuario no tiene rol cortador ni costurero");
            $this->line("   Roles actuales: " . json_encode($user->roles()->pluck('name')->toArray()));
            return;
        }
        
        $this->line("    MIDDLEWARE PERMITIDO");
        $this->line('');

        // Simular el AuthenticatedSessionController
        $this->info('Simulando AuthenticatedSessionController::store():');
        
        if (!$user || !$user->role) {
            $this->error("    PROBLEMA: Usuario no tiene rol (role relationship)");
            $this->line("      user->role: " . json_encode($user->role));
            return;
        }

        $roleName = is_object($user->role) ? $user->role->name : $user->role;
        $this->line("   - Rol detectado: {$roleName}");

        if ($roleName === 'costurero') {
            $this->line("    REDIRECCIÓN: operario.dashboard");
        } else {
            $this->error("    ERROR: Rol '{$roleName}' no tiene ruta de redirección");
        }

        $this->line('');
        $this->line(' Simulación completada');
        $this->line('');
    }
}
