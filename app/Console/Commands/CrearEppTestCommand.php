<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Shared\CQRS\CommandBus;
use App\Application\Commands\CrearEppCommand;

class CrearEppTestCommand extends Command
{
    protected $signature = 'epp:crear-test';
    protected $description = 'Crear un EPP de prueba';

    public function __construct(private CommandBus $commandBus)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('🔵 Iniciando prueba de creación de EPP...');
        $this->newLine();

        $nombre = 'Gafas de Seguridad Test ' . time();
        $categoria = 'OJOS';
        $codigo = 'GAF-SEG-' . time();
        $descripcion = 'Gafas de seguridad para protección ocular - Test: ' . date('Y-m-d H:i:s');

        $this->info('📝 Datos a crear:');
        $this->line("  - Nombre: $nombre");
        $this->line("  - Categoría: $categoria");
        $this->line("  - Código: $codigo");
        $this->line("  - Descripción: $descripcion");
        $this->newLine();

        try {
            $command = new CrearEppCommand(
                nombre: $nombre,
                categoria: $categoria,
                codigo: $codigo,
                descripcion: $descripcion
            );

            $resultado = $this->commandBus->execute($command);

            $this->info('✅ EPP creado exitosamente!');
            $this->info('📊 Resultado:');
            $this->line(json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error al crear EPP:');
            $this->error('   ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
