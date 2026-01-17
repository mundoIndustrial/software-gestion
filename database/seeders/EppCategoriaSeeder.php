<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EppCategoria;

class EppCategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'codigo' => 'CABEZA',
                'nombre' => 'Protección de Cabeza',
                'descripcion' => 'Cascos, sombreros de seguridad y otros equipos para protección craneal',
                'activo' => true,
            ],
            [
                'codigo' => 'MANOS',
                'nombre' => 'Protección de Manos',
                'descripcion' => 'Guantes, mitones y otros equipos para protección de manos',
                'activo' => true,
            ],
            [
                'codigo' => 'PIES',
                'nombre' => 'Protección de Pies',
                'descripcion' => 'Botas, zapatos de seguridad y otros equipos para protección de pies',
                'activo' => true,
            ],
            [
                'codigo' => 'CUERPO',
                'nombre' => 'Protección de Cuerpo',
                'descripcion' => 'Chalecos, overoles y otros equipos para protección corporal',
                'activo' => true,
            ],
            [
                'codigo' => 'PROTECCION_AUDITIVA',
                'nombre' => 'Protección Auditiva',
                'descripcion' => 'Orejeras, tapones y otros equipos para protección del oído',
                'activo' => true,
            ],
            [
                'codigo' => 'PROTECCION_VISUAL',
                'nombre' => 'Protección Visual',
                'descripcion' => 'Gafas, caretas y otros equipos para protección ocular',
                'activo' => true,
            ],
            [
                'codigo' => 'RESPIRATORIA',
                'nombre' => 'Protección Respiratoria',
                'descripcion' => 'Mascarillas, respiradores y otros equipos para protección respiratoria',
                'activo' => true,
            ],
            [
                'codigo' => 'OTRA',
                'nombre' => 'Otra Protección',
                'descripcion' => 'Otros equipos de protección personal',
                'activo' => true,
            ],
        ];

        foreach ($categorias as $categoria) {
            EppCategoria::updateOrCreate(
                ['codigo' => $categoria['codigo']],
                $categoria
            );
        }

        $this->command->info('✅ EppCategoriaSeeder ejecutado correctamente. ' . EppCategoria::count() . ' categorías creadas.');
    }
}
