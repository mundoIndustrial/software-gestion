<?php

namespace Tests\Feature\Cotizacion;

use App\Models\Cotizacion;
use App\Models\TipoCotizacion;
use App\Models\Cliente;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class CotizacionesMinimoTest extends TestCase
{
    /**
     * Test: Verificar que la BD estÃ¡ disponible
     */
    public function test_database_connection()
    {
        try {
            DB::connection()->getPdo();
            $this->assertTrue(true, "Base de datos conectada");
        } catch (\Exception $e) {
            $this->fail("No hay conexiÃ³n a BD: " . $e->getMessage());
        }
    }

    /**
     * Test: Verificar que las tablas existen
     */
    public function test_tables_exist()
    {
        $tables = DB::select("
            SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = ?
        ", [DB::getDatabaseName()]);

        $tableNames = array_map(fn($t) => $t->TABLE_NAME, $tables);

        $this->assertContains('cotizaciones', $tableNames);
        $this->assertContains('tipos_cotizacion', $tableNames);
        $this->assertContains('clientes', $tableNames);
        $this->assertContains('users', $tableNames);
    }

    /**
     * Test: Contar cotizaciones existentes
     */
    public function test_count_existing_quotations()
    {
        $count = Cotizacion::count();
        $this->assertGreaterThanOrEqual(0, $count);
        echo "\n[INFO] Total cotizaciones en BD: {$count}";
    }

    /**
     * Test: Verificar tipos de cotizaciÃ³n
     */
    public function test_quotation_types_exist()
    {
        $tipos = TipoCotizacion::all();
        $this->assertNotEmpty($tipos);
        echo "\n[INFO] Tipos de cotizaciÃ³n: " . $tipos->count();
    }

    /**
     * Test: Verificar clientes existentes
     */
    public function test_clients_exist()
    {
        $clientes = Cliente::all();
        $this->assertNotEmpty($clientes);
        echo "\n[INFO] Clientes: " . $clientes->count();
    }

    /**
     * Test: Verificar usuarios (asesores)
     */
    public function test_users_exist()
    {
        $usuarios = User::all();
        $this->assertNotEmpty($usuarios);
        echo "\n[INFO] Usuarios/Asesores: " . $usuarios->count();
    }
}

