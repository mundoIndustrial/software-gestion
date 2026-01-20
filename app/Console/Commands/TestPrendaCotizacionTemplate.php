<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PrendaCotizacionTemplateService;

class TestPrendaCotizacionTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:prenda-cotizacion {numero_pedido? : El número de pedido a probar}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Prueba la plantilla de prendas para pedidos relacionados a cotizaciones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Obtener número de pedido
        $numeroPedido = $this->argument('numero_pedido');

        if (!$numeroPedido) {
            $numeroPedido = $this->ask('Ingresa el número de pedido a probar');
        }

        // Validar que sea un número
        if (!is_numeric($numeroPedido)) {
            $this->error(' El número de pedido debe ser numérico');
            return 1;
        }

        $numeroPedido = (int)$numeroPedido;

        $this->info(" Probando plantilla para pedido: {$numeroPedido}");
        $this->newLine();

        try {
            // Instanciar el servicio
            $service = new PrendaCotizacionTemplateService();

            // Generar plantilla
            $prendas = $service->generarPlantillaPrendas($numeroPedido);

            if (empty($prendas)) {
                $this->warn('  No se encontraron prendas para este pedido');
                return 0;
            }

            // Mostrar resultados
            $this->info(" Se encontraron " . count($prendas) . " prenda(s)");
            $this->newLine();

            foreach ($prendas as $prenda) {
                $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                $this->line("<info>PRENDA {$prenda['numero']}: {$prenda['nombre']}</info>");
                $this->newLine();
                $this->line("<comment>{$prenda['atributos']}</comment>");
                $this->newLine();

                $this->line("<fg=cyan>DESCRIPCION:</> {$prenda['descripcion']}");
                $this->newLine();

                // Mostrar detalles si existen
                if (!empty($prenda['detalles'])) {
                    foreach ($prenda['detalles'] as $detalle) {
                        $this->line("<fg=yellow>. {$detalle['tipo']}:</>");
                        $this->line("{$detalle['valor']}");
                        $this->newLine();
                    }
                }

                // Mostrar tallas
                $this->line("<fg=red>Tallas: {$prenda['tallas']}</>");
                $this->newLine();
            }

            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();

            // Mostrar JSON para referencia
            $this->info(' Datos en formato JSON:');
            $this->line(json_encode($prendas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return 0;
        } catch (\Exception $e) {
            $this->error(' Error: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
