<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TestLoginFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:login-flow {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test complete login flow for costurero user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? 'yus55@gmail.com';
        $password = 'password123'; // Contraseña por defecto del seeder
        
        $this->line('');
        $this->line('=== TESTING COMPLETE LOGIN FLOW ===');
        $this->line('');

        // Step 1: Verify user exists
        $this->info('Step 1: Verificar que el usuario existe');
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("   ❌ Usuario NO encontrado: {$email}");
            return;
        }
        
        $this->line("    Usuario encontrado: {$user->name} (ID: {$user->id})");
        $this->line('');

        // Step 2: Try authentication
        $this->info('Step 2: Intentar autenticación');
        $credentials = ['email' => $email, 'password' => $password];
        
        if (Auth::attempt($credentials)) {
            $this->line('    Autenticación exitosa');
        } else {
            $this->error('   ❌ Autenticación fallida');
            $this->line('   Posibles causas:');
            $this->line('   - Contraseña incorrecta');
            $this->line('   - Usuario inactivo');
            $this->line('');
            
            // Try to find if user password is different
            $this->warn('   Verificando hash de contraseña en BD...');
            $userFromDb = User::find($user->id);
            $this->line("   Hash en BD: {$userFromDb->password}");
            $this->line("   Intentaré rehash de contraseña para test...");
            
            return;
        }
        
        $this->line('');

        // Step 3: Check authenticated user
        $this->info('Step 3: Verificar usuario autenticado');
        $authenticatedUser = Auth::user();
        
        if ($authenticatedUser) {
            $this->line("    Usuario autenticado: {$authenticatedUser->name}");
        } else {
            $this->error('   ❌ No hay usuario autenticado');
            return;
        }
        
        $this->line('');

        // Step 4: Check role
        $this->info('Step 4: Verificar rol del usuario');
        
        if ($authenticatedUser->hasRole('costurero')) {
            $this->line('    Usuario tiene rol costurero');
        } else {
            $this->error('   ❌ Usuario NO tiene rol costurero');
        }
        
        $this->line('');

        // Step 5: Check middleware
        $this->info('Step 5: Verificar middleware OperarioAccess');
        
        if ($authenticatedUser->hasAnyRole(['cortador', 'costurero'])) {
            $this->line('    Middleware OperarioAccess permitiría acceso');
        } else {
            $this->error('   ❌ Middleware OperarioAccess rechazaría acceso');
        }
        
        $this->line('');

        // Step 6: Check dashboard rendering
        $this->info('Step 6: Verificar que puede acceder al dashboard');
        
        try {
            $service = app(\App\Application\Operario\Services\ObtenerPedidosOperarioService::class);
            $datosOperario = $service->obtenerPedidosDelOperario($authenticatedUser);
            
            $this->line("    Datos del operario obtenidos correctamente");
            $this->line("      Total pedidos: " . count($datosOperario->pedidos));
            $this->line("      Tipo: {$datosOperario->tipo}");
            
        } catch (\Exception $e) {
            $this->error("   ❌ Error al obtener datos: {$e->getMessage()}");
            return;
        }
        
        $this->line('');
        $this->line(' LOGIN FLOW TEST COMPLETADO EXITOSAMENTE');
        $this->line('');
        
        // Logout
        Auth::logout();
    }
}
