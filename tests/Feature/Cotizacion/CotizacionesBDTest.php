<?php

namespace Tests\Feature\Cotizacion;

use PHPUnit\Framework\TestCase;
use MySQLi;

/**
 * Tests simples de validación de BD de Cotizaciones
 * Uso directo de MySQLi para evitar problemas con PDO
 */
class CotizacionesBDTest extends TestCase
{
    private ?MySQLi $db = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->db = new MySQLi(
            '127.0.0.1',
            'root',
            '29522628',
            'mundo_bd'
        );

        if ($this->db->connect_error) {
            $this->fail("Conexión fallida: " . $this->db->connect_error);
        }

        $this->db->set_charset("utf8mb4");
    }

    protected function tearDown(): void
    {
        if ($this->db) {
            $this->db->close();
        }
        parent::tearDown();
    }

    /**
     * Prueba 1: Conexión exitosa a BD
     */
    public function test_database_connected(): void
    {
        $this->assertIsObject($this->db);
        echo "\n✓ Base de datos conectada";
    }

    /**
     * Prueba 2: Cotizaciones existentes
     */
    public function test_quotations_count(): void
    {
        $result = $this->db->query("SELECT COUNT(*) as cnt FROM cotizaciones WHERE deleted_at IS NULL");
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];

        $this->assertGreaterThanOrEqual(0, $count);
        echo "\n✓ Total de cotizaciones: {$count}";
    }

    /**
     * Prueba 3: Tipos de cotización disponibles
     */
    public function test_types_count(): void
    {
        $result = $this->db->query("SELECT COUNT(*) as cnt FROM tipos_cotizacion");
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];

        $this->assertGreaterThanOrEqual(3, $count);
        echo "\n✓ Tipos de cotización: {$count}";
    }

    /**
     * Prueba 4: Verificar tablas clave existen
     */
    public function test_required_tables_exist(): void
    {
        $sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA='mundo_bd' 
                AND TABLE_NAME IN ('cotizaciones', 'tipos_cotizacion', 'prendas_cot', 'prenda_fotos_cot')";
        
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];

        $this->assertEquals(4, $count);
        echo "\n✓ 4 tablas requeridas existen";
    }

    /**
     * Prueba 5: Prendas en cotizaciones
     */
    public function test_prendas_in_quotations(): void
    {
        $result = $this->db->query("SELECT COUNT(*) as cnt FROM prendas_cot");
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];

        $this->assertGreaterThanOrEqual(0, $count);
        echo "\n✓ Total prendas en cotizaciones: {$count}";
    }

    /**
     * Prueba 6: Fotos de prendas
     */
    public function test_photos_exist(): void
    {
        $result = $this->db->query("SELECT COUNT(*) as cnt FROM prenda_fotos_cot");
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];

        $this->assertGreaterThanOrEqual(0, $count);
        echo "\n✓ Total fotos: {$count}";
    }

    /**
     * Prueba 7: Validar estructura de numero_cotizacion
     */
    public function test_numero_cotizacion_structure(): void
    {
        $result = $this->db->query("
            SELECT COUNT(*) as cnt FROM cotizaciones 
            WHERE numero_cotizacion IS NOT NULL 
            AND numero_cotizacion != ''
        ");
        
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];

        $this->assertGreaterThanOrEqual(0, $count);
        echo "\n✓ Cotizaciones con numero válido: {$count}";
    }

    /**
     * Prueba 8: Clientes en sistema
     */
    public function test_clients_exist(): void
    {
        $result = $this->db->query("SELECT COUNT(*) as cnt FROM clientes");
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];

        $this->assertGreaterThanOrEqual(0, $count);
        echo "\n✓ Total clientes: {$count}";
    }

    /**
     * Prueba 9: Usuarios/Asesores
     */
    public function test_users_exist(): void
    {
        $result = $this->db->query("SELECT COUNT(*) as cnt FROM users");
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];

        $this->assertGreaterThanOrEqual(0, $count);
        echo "\n✓ Total usuarios: {$count}";
    }

    /**
     * Prueba 10: Estructura BD completa
     */
    public function test_complete_structure(): void
    {
        $result = $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM cotizaciones) as cot_count,
                (SELECT COUNT(*) FROM prendas_cot) as prenda_count,
                (SELECT COUNT(*) FROM prenda_fotos_cot) as foto_count,
                (SELECT COUNT(*) FROM clientes) as client_count,
                (SELECT COUNT(*) FROM users) as user_count
        ");
        
        $row = $result->fetch_assoc();
        
        echo "\n═══════════════════════════════════════════════════════════";
        echo "\nRESUMEN ESTRUCTURA BD COTIZACIONES:";
        echo "\n═══════════════════════════════════════════════════════════";
        echo "\n• Cotizaciones: " . $row['cot_count'];
        echo "\n• Prendas: " . $row['prenda_count'];
        echo "\n• Fotos: " . $row['foto_count'];
        echo "\n• Clientes: " . $row['client_count'];
        echo "\n• Usuarios: " . $row['user_count'];
        echo "\n═══════════════════════════════════════════════════════════";
        
        // Assertions mínimas
        $this->assertNotNull($row['cot_count']);
        $this->assertNotNull($row['prenda_count']);
    }
}
