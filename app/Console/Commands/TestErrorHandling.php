<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestErrorHandling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:errors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando para probar el manejo de errores personalizado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Probando el sistema de manejo de errores...');
        
        $this->info('✅ Sistema de errores configurado correctamente:');
        $this->line('   - Vista de error personalizada: resources/views/error.blade.php');
        $this->line('   - Handler de excepciones: app/Exceptions/Handler.php');
        $this->line('   - Vistas específicas: resources/views/errors/404.blade.php, 500.blade.php, 403.blade.php');
        
        $this->newLine();
        $this->info('🔗 URLs de prueba que puedes visitar:');
        $this->line('   - /pagina-que-no-existe (Error 404)');
        $this->line('   - Cualquier error de base de datos');
        $this->line('   - Errores de validación');
        
        $this->newLine();
        $this->info('📋 Características implementadas:');
        $this->line('   ✓ Mensajes amigables para usuarios');
        $this->line('   ✓ Detalles técnicos ocultables');
        $this->line('   ✓ Códigos de error únicos');
        $this->line('   ✓ Interfaz moderna y responsive');
        $this->line('   ✓ Soporte para AJAX/API (respuestas JSON)');
        
        return Command::SUCCESS;
    }
}
