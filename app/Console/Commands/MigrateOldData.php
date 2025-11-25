<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Cliente;

class MigrateOldData extends Command
{
    protected $signature = 'migrate:old-data';
    protected $description = 'Migra datos de arquitectura antigua a nueva (usuarios, clientes, pedidos, prendas)';

    public function handle()
    {
        $this->info("\n");
        $this->info(str_repeat("=", 140));
        $this->info("🚀 INICIANDO MIGRACIÓN DE DATOS - Arquitectura Antigua → Nueva");
        $this->info(str_repeat("=", 140) . "\n");

        try {
            // ============================================
            // FASE 1: CREAR USUARIOS (ASESORAS)
            // ============================================
            $this->info("⏳ FASE 1: Creando 37 usuarios (asesoras)...");
            $this->line("");
            $this->crearUsuarios();

            // ============================================
            // FASE 2: CREAR CLIENTES
            // ============================================
            $this->info("\n⏳ FASE 2: Creando 964 clientes...");
            $this->line("");
            $this->crearClientes();

            // ============================================
            // FINAL
            // ============================================
            $this->info("\n");
            $this->info(str_repeat("=", 140));
            $this->info("✅ FASE 1 Y 2 COMPLETADAS - Usuarios y Clientes creados");
            $this->info(str_repeat("=", 140) . "\n");

        } catch (\Exception $e) {
            $this->error("❌ Error durante migración: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    // ============================================
    // FASE 1: CREAR USUARIOS (ASESORAS)
    // ============================================
    private function crearUsuarios()
    {
        // Obtener asesoras únicas de tabla_original
        $asesoras = DB::table('tabla_original')
            ->whereNotNull('asesora')
            ->distinct()
            ->pluck('asesora')
            ->sort()
            ->values();

        $totalAsesoras = $asesoras->count();
        $this->line("   📊 Asesoras únicas encontradas: $totalAsesoras");

        $nuevosUsuarios = 0;
        $usuariosExistentes = 0;
        $mapeoAsesoras = []; // Para referencia posterior

        foreach ($asesoras as $nombre) {
            // Buscar si el usuario ya existe
            $usuario = User::where('name', $nombre)->first();

            if (!$usuario) {
                // Crear nuevo usuario
                $usuario = User::create([
                    'name' => $nombre,
                    'email' => strtolower(str_replace(' ', '.', $nombre)) . '@asesora.local',
                    'password' => bcrypt('password123'), // Password temporal
                    'email_verified_at' => now(),
                ]);
                $nuevosUsuarios++;
                $this->line("   ✅ Usuario creado: {$usuario->name} (ID: {$usuario->id})");
            } else {
                $usuariosExistentes++;
            }

            // Guardar mapeo
            $mapeoAsesoras[$nombre] = $usuario->id;
        }

        // Verificar si hay pedidos sin asesora
        $pedidosSinAsesora = DB::table('tabla_original')->whereNull('asesora')->count();
        if ($pedidosSinAsesora > 0) {
            // Crear usuario especial para pedidos sin asesora
            $usuarioSinAsesora = User::firstOrCreate(
                ['email' => 'sin-asesora@asesora.local'],
                [
                    'name' => 'SIN_ASESORA',
                    'password' => bcrypt('password123'),
                    'email_verified_at' => now(),
                ]
            );
            $mapeoAsesoras[null] = $usuarioSinAsesora->id;
            $this->line("   ✅ Usuario especial creado para pedidos sin asesora (ID: {$usuarioSinAsesora->id})");
        }

        // Guardar mapeo en cache para siguiente fase
        cache()->put('mapeo_asesoras', $mapeoAsesoras, now()->addHours(24));

        $this->line("\n   📈 Resumen USUARIOS:");
        $this->line("      Nuevos usuarios creados: $nuevosUsuarios");
        $this->line("      Usuarios ya existentes: $usuariosExistentes");
        $this->line("      Total usuarios en base: " . User::count());
    }

    // ============================================
    // FASE 2: CREAR CLIENTES
    // ============================================
    private function crearClientes()
    {
        // Obtener clientes únicos de tabla_original
        $clientes = DB::table('tabla_original')
            ->whereNotNull('cliente')
            ->distinct()
            ->pluck('cliente')
            ->sort()
            ->values();

        $totalClientes = $clientes->count();
        $this->line("   📊 Clientes únicos encontrados: $totalClientes");

        $nuevosClientes = 0;
        $clientesExistentes = 0;
        $mapeoClientes = []; // Para referencia posterior

        foreach ($clientes as $nombre) {
            // Buscar si el cliente ya existe
            $cliente = Cliente::where('nombre', $nombre)->first();

            if (!$cliente) {
                // Crear nuevo cliente
                $cliente = Cliente::create([
                    'nombre' => $nombre,
                    'estado' => 'activo',
                ]);
                $nuevosClientes++;
                $this->line("   ✅ Cliente creado: {$cliente->nombre} (ID: {$cliente->id})");
            } else {
                $clientesExistentes++;
            }

            // Guardar mapeo
            $mapeoClientes[$nombre] = $cliente->id;
        }

        // Verificar si hay pedidos sin cliente
        $pedidosSinCliente = DB::table('tabla_original')->whereNull('cliente')->count();
        if ($pedidosSinCliente > 0) {
            // Crear cliente especial para pedidos sin cliente
            $clienteSinCliente = Cliente::firstOrCreate(
                ['nombre' => 'SIN_CLIENTE'],
                ['estado' => 'activo']
            );
            $mapeoClientes[null] = $clienteSinCliente->id;
            $this->line("   ✅ Cliente especial creado para pedidos sin cliente (ID: {$clienteSinCliente->id})");
        }

        // Guardar mapeo en cache para siguiente fase
        cache()->put('mapeo_clientes', $mapeoClientes, now()->addHours(24));

        $this->line("\n   📈 Resumen CLIENTES:");
        $this->line("      Nuevos clientes creados: $nuevosClientes");
        $this->line("      Clientes ya existentes: $clientesExistentes");
        $this->line("      Total clientes en base: " . Cliente::count());
    }
}
