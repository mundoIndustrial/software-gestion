<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Epp;

class EppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Desactivar constraints
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Limpiar datos anteriores
        Epp::truncate();

        // Reactivar constraints
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Obtener categorías
        $categorias = \App\Models\EppCategoria::pluck('id', 'codigo');

        // Crear EPP de ejemplo
        $epps = [
            // PROTECCIÓN CABEZA
            [
                'codigo' => 'EPP-CAB-001',
                'nombre' => 'Casco de Seguridad ABS Amarillo',
                'categoria_id' => $categorias['CABEZA'],
                'descripcion' => 'Casco de seguridad fabricado en ABS con suspensión interna ajustable',
                'tallas_disponibles' => json_encode(['ÚNICO']),
                'activo' => true,
            ],
            [
                'codigo' => 'EPP-CAB-002',
                'nombre' => 'Casco de Seguridad ABS Blanco',
                'categoria_id' => $categorias['CABEZA'],
                'descripcion' => 'Casco de seguridad fabricado en ABS color blanco con suspensión',
                'tallas_disponibles' => json_encode(['ÚNICO']),
                'activo' => true,
            ],

            // PROTECCIÓN MANOS
            [
                'codigo' => 'EPP-MAO-001',
                'nombre' => 'Guantes Nitrilo Anti Resbalón',
                'categoria_id' => $categorias['MANOS'],
                'descripcion' => 'Guantes de nitrilo con revestimiento de puntos para mayor agarre y resistencia',
                'tallas_disponibles' => json_encode(['S', 'M', 'L', 'XL']),
                'activo' => true,
            ],
            [
                'codigo' => 'EPP-MAO-002',
                'nombre' => 'Guantes de Cuero Flor Vaca',
                'categoria_id' => $categorias['MANOS'],
                'descripcion' => 'Guantes de cuero natural de flor de vaca para trabajos de manipulación',
                'tallas_disponibles' => json_encode(['7', '8', '9', '10', '11']),
                'activo' => true,
            ],

            // PROTECCIÓN PIES
            [
                'codigo' => 'EPP-PIE-001',
                'nombre' => 'Botas de Seguridad Punta Acero Negras',
                'categoria_id' => $categorias['PIES'],
                'descripcion' => 'Botas de seguridad con puntara de acero, suela antideslizante y puntera de acero',
                'tallas_disponibles' => json_encode(['34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46']),
                'activo' => true,
            ],
            [
                'codigo' => 'EPP-PIE-002',
                'nombre' => 'Zapatos de Seguridad Punta Acero',
                'categoria_id' => $categorias['PIES'],
                'descripcion' => 'Zapatos de seguridad semi-bajos con puntara de acero y suela resistente',
                'tallas_disponibles' => json_encode(['34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45']),
                'activo' => true,
            ],

            // PROTECCIÓN CUERPO
            [
                'codigo' => 'EPP-CUE-001',
                'nombre' => 'Chaleco Reflectivo ANSI Naranja',
                'categoria_id' => $categorias['CUERPO'],
                'descripcion' => 'Chaleco reflectivo de alta visibilidad con tiras reflectivas frontales y dorsales',
                'tallas_disponibles' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
                'activo' => true,
            ],
            [
                'codigo' => 'EPP-CUE-002',
                'nombre' => 'Overol Algodón Azul Marino',
                'categoria_id' => $categorias['CUERPO'],
                'descripcion' => 'Overol de algodón 100% con cierre frontal y múltiples bolsillos de utilidad',
                'tallas_disponibles' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
                'activo' => true,
            ],

            // PROTECCIÓN OJOS/CARA
            [
                'codigo' => 'EPP-OJO-001',
                'nombre' => 'Gafas de Seguridad Panorámicas',
                'categoria_id' => $categorias['PROTECCION_VISUAL'],
                'descripcion' => 'Gafas de seguridad panorámicas con lentes de policarbonato resistentes al impacto',
                'tallas_disponibles' => json_encode(['ÚNICO']),
                'activo' => true,
            ],
            [
                'codigo' => 'EPP-OJO-002',
                'nombre' => 'Careta Protectora Transparente',
                'categoria_id' => $categorias['PROTECCION_VISUAL'],
                'descripcion' => 'Careta protectora de policarbonato transparente para protección facial integral',
                'tallas_disponibles' => json_encode(['ÚNICO']),
                'activo' => true,
            ],

            // PROTECCIÓN RESPIRATORIA
            [
                'codigo' => 'EPP-RES-001',
                'nombre' => 'Mascarilla N95 con Válvula',
                'categoria_id' => $categorias['RESPIRATORIA'],
                'descripcion' => 'Mascarilla N95 desechable con válvula de exhalación para protección respiratoria',
                'tallas_disponibles' => json_encode(['ÚNICO']),
                'activo' => true,
            ],
            [
                'codigo' => 'EPP-RES-002',
                'nombre' => 'Respirador Media Cara',
                'categoria_id' => $categorias['RESPIRATORIA'],
                'descripcion' => 'Respirador reutilizable de media cara con cartuchos intercambiables',
                'tallas_disponibles' => json_encode(['S/M', 'L/XL']),
                'activo' => true,
            ],

            // PROTECCIÓN AUDICIÓN
            [
                'codigo' => 'EPP-AUD-001',
                'nombre' => 'Protectores Auditivos Tipo Orejera',
                'categoria_id' => $categorias['PROTECCION_AUDITIVA'],
                'descripcion' => 'Orejeras protectoras de ruido con copa ajustable y acolchado confortable',
                'tallas_disponibles' => json_encode(['ÚNICO']),
                'activo' => true,
            ],

            // OTRA PROTECCIÓN
            [
                'codigo' => 'EPP-OTR-001',
                'nombre' => 'Arnés de Seguridad para Altura',
                'categoria_id' => $categorias['OTRA'],
                'descripcion' => 'Arnés de cuerpo completo para trabajos en altura con puntos de amarre múltiples',
                'tallas_disponibles' => json_encode(['M', 'L', 'XL']),
                'activo' => true,
            ],
        ];

        // Insertar EPPs sin imágenes
        foreach ($epps as $eppData) {
            Epp::create($eppData);
        }

        $this->command->info('✅ EPP Seeder ejecutado correctamente. ' . Epp::count() . ' EPPs creados sin imágenes.');
        $this->command->info('💡 Las imágenes deben ser agregadas manualmente a través del formulario de carga.');
    }
}
