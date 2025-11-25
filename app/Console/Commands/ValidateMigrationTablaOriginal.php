<?php

namespace App\Console\Commands;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\TablaOriginal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateMigrationTablaOriginal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'validate:tabla-original-migration';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Valida que la migración de tabla_original a pedidos_produccion fue exitosa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔═══════════════════════════════════════════════════════╗');
        $this->info('║  Validación de Migración: tabla_original             ║');
        $this->info('╚═══════════════════════════════════════════════════════╝');

        $this->newLine();

        // 1. Contar registros
        $this->section('📊 Conteo de registros');
        $this->validarConteos();

        // 2. Validar integridad referencial
        $this->section('🔗 Integridad referencial');
        $this->validarIntegridadReferencial();

        // 3. Validar datos específicos
        $this->section('✓ Validación de datos');
        $this->validarDatos();

        // 4. Identificar problemas
        $this->section('⚠️  Validación de problemas potenciales');
        $this->validarProblemas();

        $this->info("\n╔═══════════════════════════════════════════════════════╗");
        $this->info("║  ✅ Validación completada                           ║");
        $this->info("╚═══════════════════════════════════════════════════════╝\n");

        return 0;
    }

    private function validarConteos()
    {
        $totalOriginal = TablaOriginal::count();
        $totalMigrado = PedidoProduccion::whereNotNull('numero_pedido')->count();
        $totalPrendas = PrendaPedido::count();
        $totalProcesos = ProcesoPrenda::count();

        $this->info("Tabla original:          {$totalOriginal}");
        $this->info("Pedidos migrados:        {$totalMigrado}");
        $this->info("Prendas creadas:         {$totalPrendas}");
        $this->info("Procesos creados:        {$totalProcesos}");

        if ($totalMigrado === $totalOriginal) {
            $this->info("✅ Cantidad de pedidos coincide");
        } else {
            $this->warn("⚠️  Discrepancia: Original={$totalOriginal}, Migrado={$totalMigrado}");
        }

        if ($totalPrendas > 0) {
            $this->info("✅ Prendas fueron creadas");
        } else {
            $this->warn("⚠️  No se crearon prendas");
        }

        if ($totalProcesos > 0) {
            $this->info("✅ Procesos fueron creados");
        } else {
            $this->warn("⚠️  No se crearon procesos");
        }
    }

    private function validarIntegridadReferencial()
    {
        // Verificar que todos los prendas_pedido tengan pedido_produccion válido
        $prendasSinPedido = DB::table('prendas_pedido')
            ->leftJoin('pedidos_produccion', 'prendas_pedido.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->whereNull('pedidos_produccion.id')
            ->count();

        if ($prendasSinPedido === 0) {
            $this->info("✅ Todas las prendas tienen pedido válido");
        } else {
            $this->warn("⚠️  {$prendasSinPedido} prendas sin pedido asociado");
        }

        // Verificar que todos los procesos_prenda tengan prenda válida
        $procesosSinPrenda = DB::table('procesos_prenda')
            ->leftJoin('prendas_pedido', 'procesos_prenda.prenda_pedido_id', '=', 'prendas_pedido.id')
            ->whereNull('prendas_pedido.id')
            ->count();

        if ($procesosSinPrenda === 0) {
            $this->info("✅ Todos los procesos tienen prenda válida");
        } else {
            $this->warn("⚠️  {$procesosSinPrenda} procesos sin prenda asociada");
        }
    }

    private function validarDatos()
    {
        // Verificar que numero_pedido es único
        $duplicados = DB::table('pedidos_produccion')
            ->whereNotNull('numero_pedido')
            ->groupBy('numero_pedido')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicados === 0) {
            $this->info("✅ Todos los numero_pedido son únicos");
        } else {
            $this->warn("⚠️  {$duplicados} pedidos con numero_pedido duplicado");
        }

        // Verificar campos no vacíos
        $sinCliente = PedidoProduccion::whereNull('cliente')->count();
        $sinEstado = PedidoProduccion::whereNull('estado')->count();
        $sinFecha = PedidoProduccion::whereNull('fecha_de_creacion_de_orden')->count();

        if ($sinCliente === 0) {
            $this->info("✅ Todos los pedidos tienen cliente");
        } else {
            $this->warn("⚠️  {$sinCliente} pedidos sin cliente");
        }

        if ($sinEstado === 0) {
            $this->info("✅ Todos los pedidos tienen estado");
        } else {
            $this->warn("⚠️  {$sinEstado} pedidos sin estado");
        }

        if ($sinFecha === 0) {
            $this->info("✅ Todos los pedidos tienen fecha de creación");
        } else {
            $this->warn("⚠️  {$sinFecha} pedidos sin fecha de creación");
        }
    }

    private function validarProblemas()
    {
        // Prendas sin nombre
        $prendasSinNombre = PrendaPedido::whereNull('nombre_prenda')->count();
        if ($prendasSinNombre > 0) {
            $this->warn("⚠️  {$prendasSinNombre} prendas sin nombre");
        }

        // Procesos sin fecha_inicio
        $processosSinFecha = ProcesoPrenda::whereNull('fecha_inicio')->count();
        if ($processosSinFecha > 0) {
            $this->warn("⚠️  {$processosSinFecha} procesos sin fecha de inicio");
        }

        // Pedidos con estado inválido
        $estadosValidos = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];
        $estadosInvalidos = DB::table('pedidos_produccion')
            ->whereNotIn('estado', $estadosValidos)
            ->whereNotNull('estado')
            ->count();

        if ($estadosInvalidos === 0) {
            $this->info("✅ Todos los estados son válidos");
        } else {
            $this->warn("⚠️  {$estadosInvalidos} pedidos con estado inválido");
        }

        // Verificar cotizacion_id en históricos (debe ser null)
        $conCotizacion = PedidoProduccion::whereNotNull('cotizacion_id')->count();
        $this->info("Pedidos con cotización: {$conCotizacion} (estos son nuevos)");

        $sinCotizacion = PedidoProduccion::whereNull('cotizacion_id')->count();
        $this->info("Pedidos históricos (sin cotización): {$sinCotizacion}");
    }

    private function section($title)
    {
        $this->newLine();
        $this->info("─────────────────────────────────────────────────────");
        $this->info($title);
        $this->info("─────────────────────────────────────────────────────");
    }
}
