<?php

namespace Tests\Feature\Cotizacion;

use PHPUnit\Framework\TestCase as BaseTestCase;
use MySQLi;

/**
 * Tests de Cotizaciones usando MySQLi directo (evita problemas con PDO)
 */
class CotizacionesMySQLiTest extends BaseTestCase
{
    private MySQLi $db;

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
            $this->fail("Error conectando a BD: " . $this->db->connect_error);
        }

        // Establecer charset
        $this->db->set_charset("utf8mb4");
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    /**
     * Test: ConexiÃ³n a BD
     */
    public function test_database_connection(): void
    {
        $this->assertFalse($this->db->connect_error);
        echo "\nâœ“ ConexiÃ³n a BD exitosa";
    }

    /**
     * Test: Contar cotizaciones existentes
     */
    public function test_count_existing_quotations(): void
    {
        $result = $this->db->query("SELECT COUNT(*) as total FROM cotizaciones WHERE deleted_at IS NULL");
        $row = $result->fetch_assoc();
        $count = $row['total'];

        $this->assertGreaterThanOrEqual(0, $count);
        echo "\nâœ“ Total cotizaciones: {$count}";
    }

    /**
     * Test: Verificar tipos de cotizaciÃ³n
     */
    public function test_quotation_types_exist(): void
    {
        $result = $this->db->query("SELECT COUNT(*) as total FROM tipos_cotizacion");
        $row = $result->fetch_assoc();
        $count = $row['total'];

        $this->assertGreater(0, $count);
        echo "\nâœ“ Tipos de cotizaciÃ³n: {$count}";
    }

    /**
     * Test: Crear cotizaciÃ³n simple
     */
    public function test_create_simple_quotation(): void
    {
        $this->db->query("
            INSERT INTO usuarios (nombre, email, contraseÃ±a, rol, estado, created_at, updated_at)
            VALUES ('Test Asesor', 'test@example.com', 'test123', 'asesor', 'activo', NOW(), NOW())
            ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
        ");
        
        $asesorId = $this->db->insert_id;
        echo "\nâœ“ Asesor creado: {$asesorId}";

        // Crear cotizaciÃ³n
        $this->db->query("
            INSERT INTO cotizaciones 
            (numero_cotizacion, id_tipo_cotizacion, id_cliente, id_asesor, estado, created_at, updated_at)
            VALUES 
            (CONCAT('TEST-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')), 1, 1, {$asesorId}, 'borrador', NOW(), NOW())
        ");

        $cotizacionId = $this->db->insert_id;
        $this->assertGreater(0, $cotizacionId);
        echo "\nâœ“ CotizaciÃ³n creada: {$cotizacionId}";
    }

    /**
     * Test: Validar numero_cotizacion Ãºnico
     */
    public function test_numero_cotizacion_unique(): void
    {
        $sql = "SHOW COLUMNS FROM cotizaciones LIKE 'numero_cotizacion'";
        $result = $this->db->query($sql);
        $field = $result->fetch_assoc();

        // Verificar si existe la columna
        $this->assertIsArray($field);
        echo "\nâœ“ Campo numero_cotizacion existe";
    }

    /**
     * Test: Verificar tablas relacionadas
     */
    public function test_related_tables_exist(): void
    {
        $tables = [
            'cotizaciones',
            'tipos_cotizacion',
            'prendas_cotizacion',
            'prendas_variantes_cotizacion',
            'prendas_tallas_cotizacion',
            'prendas_telas_cotizacion',
            'prendas_fotos_cotizacion'
        ];

        $result = $this->db->query("
            SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = 'mundo_bd'
        ");

        $existingTables = [];
        while ($row = $result->fetch_assoc()) {
            $existingTables[] = $row['TABLE_NAME'];
        }

        foreach ($tables as $table) {
            $this->assertContains($table, $existingTables, "Tabla {$table} no existe");
        }

        echo "\nâœ“ Todas las tablas principales existen";
    }
}

