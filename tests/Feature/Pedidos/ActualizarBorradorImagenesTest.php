<?php

namespace Tests\Feature\Pedidos;

use App\Models\User;
use App\Models\PedidoProduccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * ActualizarBorradorImagenesTest
 *
 * Validar que la sincronización inteligente de imágenes funciona:
 * - 14+ prendas sin perder imágenes
 * - Merge inteligente: existentes + nuevas
 * - Eliminación selectiva de imágenes
 * - Múltiples ediciones sin duplicación
 */
class ActualizarBorradorImagenesTest extends TestCase
{
    use RefreshDatabase;

    protected User $asesor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->asesor = User::factory()->create();
    }

    /**
     * Test: Actualizar prenda con 3 imágenes existentes + agregar 2 nuevas + eliminar 1
     * Resultado esperado: 4 imágenes finales (3 - 1 + 2)
     */
    public function test_actualizar_prenda_merge_inteligente_imagenes()
    {
        // 1️⃣ Crear borrador con 1 prenda que tiene 3 imágenes
        $pedido = PedidoProduccion::factory()
            ->forAsesor($this->asesor->id)
            ->withState(['estado' => 'BORRADOR'])
            ->create();

        $prenda = $pedido->prendas()->create([
            'nombre_prenda' => 'Camisa Test',
            'descripcion' => 'Camisa para test',
        ]);

        // Crear 3 imágenes existentes en BD
        $imagenIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $imagen = $prenda->imagenes()->create([
                'ruta_original' => "/storage/prendas/imagen-{$i}.jpg",
                'ruta_webp' => "/storage/prendas/imagen-{$i}.webp",
            ]);
            $imagenIds[] = $imagen->id;
        }

        // Verificar que la prenda tiene 3 imágenes
        $this->assertEquals(3, $prenda->imagenes->count());

        // 2️⃣ Actualizar: mantener imágenes [2, 3], eliminar [1], agregar 2 nuevas
        $response = $this
            ->actingAs($this->asesor)
            ->putJson("/api/asesores/pedidos/{$pedido->id}/borrador", [
                'pedido' => json_encode([
                    'cliente' => 'Cliente Test',
                    'forma_de_pago' => 'Crédito',
                    'observaciones' => 'Test actualización con imágenes',
                ]),
                'prendas_existentes' => json_encode([
                    [
                        'prenda_id' => $prenda->id,
                        'nombre_prenda' => 'Camisa Test Actualizada',
                        'descripcion' => 'Actualizada',
                        // Imágenes existentes (IDs 2 y 3)
                        'imagenes_existentes' => json_encode([
                            ['id' => $imagenIds[1], 'ruta_original' => "/storage/prendas/imagen-2.jpg"],
                            ['id' => $imagenIds[2], 'ruta_original' => "/storage/prendas/imagen-3.jpg"],
                        ]),
                        // Eliminar imagen 1
                        'imagenes_a_eliminar' => json_encode([$imagenIds[0]]),
                    ],
                ]),
                'imagenes' => [
                    UploadedFile::fake()->image('nueva-1.jpg'),
                    UploadedFile::fake()->image('nueva-2.jpg'),
                ],
            ]);

        $response->assertStatus(200);

        // 3️⃣ Verificar resultado
        $prenda->refresh();

        // Debe tener 4 imágenes: (3 - 1 + 2 = 4)
        $this->assertEquals(4, $prenda->imagenes->count(),
            'Prenda debe tener 4 imágenes (3 originales - 1 eliminada + 2 nuevas)');

        // Verificar que imagen 1 fue eliminada
        $this->assertFalse(
            $prenda->imagenes->contains('id', $imagenIds[0]),
            'Imagen 1 debería haber sido eliminada'
        );

        // Verificar que imágenes 2 y 3 siguen existiendo
        $this->assertTrue(
            $prenda->imagenes->contains('id', $imagenIds[1]),
            'Imagen 2 debería existir (no eliminada)'
        );
        $this->assertTrue(
            $prenda->imagenes->contains('id', $imagenIds[2]),
            'Imagen 3 debería existir (no eliminada)'
        );
    }

    /**
     * Test: Actualizar 14 prendas con cambios mixtos
     *
     * Escenario:
     * - Prenda 1-5: Sin cambios en imágenes (deben preservarse)
     * - Prenda 6-10: Agregar imágenes nuevas (merge)
     * - Prenda 11-14: Eliminar algunas imágenes
     */
    public function test_actualizar_14_prendas_cambios_mixtos()
    {
        // 1️⃣ Crear borrador con 14 prendas
        $pedido = PedidoProduccion::factory()
            ->forAsesor($this->asesor->id)
            ->withState(['estado' => 'BORRADOR'])
            ->create();

        $prendas = [];
        for ($i = 1; $i <= 14; $i++) {
            $prenda = $pedido->prendas()->create([
                'nombre_prenda' => "Prenda {$i}",
                'descripcion' => "Descripción de prenda {$i}",
            ]);

            // Cada prenda tiene 2-3 imágenes
            for ($j = 0; $j < 2; $j++) {
                $prenda->imagenes()->create([
                    'ruta_original' => "/storage/prendas/{$i}-{$j}.jpg",
                    'ruta_webp' => "/storage/prendas/{$i}-{$j}.webp",
                ]);
            }

            $prendas[$i] = $prenda;
        }

        // Verificar estado inicial: 14 prendas × 2 imágenes = 28 imágenes
        $this->assertEquals(14, $pedido->prendas->count());
        $this->assertEquals(28, $pedido->prendas->sum(fn($p) => $p->imagenes->count()));

        // 2️⃣ Actualizar con cambios mixtos
        $prendas_existentes_data = [];

        // Prendas 1-5: Sin cambios en imágenes
        for ($i = 1; $i <= 5; $i++) {
            $imagenesExistentes = $prendas[$i]->imagenes->map(fn($img) => [
                'id' => $img->id,
                'ruta_original' => $img->ruta_original,
            ])->toArray();

            $prendas_existentes_data[] = [
                'prenda_id' => $prendas[$i]->id,
                'nombre_prenda' => "Prenda {$i} Actualizada",
                'imagenes_existentes' => json_encode($imagenesExistentes),
            ];
        }

        // Prendas 6-10: Agregar 1 imagen nueva a cada una
        for ($i = 6; $i <= 10; $i++) {
            $imagenesExistentes = $prendas[$i]->imagenes->map(fn($img) => [
                'id' => $img->id,
                'ruta_original' => $img->ruta_original,
            ])->toArray();

            $prendas_existentes_data[] = [
                'prenda_id' => $prendas[$i]->id,
                'nombre_prenda' => "Prenda {$i} Actualizada",
                'imagenes_existentes' => json_encode($imagenesExistentes),
                // Nueva imagen se agrega como File en el formData
            ];
        }

        // Prendas 11-14: Eliminar 1 imagen de cada una
        for ($i = 11; $i <= 14; $i++) {
            $imagenes = $prendas[$i]->imagenes->toArray();
            $imagenAEliminar = $imagenes[0]['id']; // Eliminar la primera

            $imagenesMantenidas = array_slice($imagenes, 1);
            $imagenesExistentes = array_map(fn($img) => [
                'id' => $img['id'],
                'ruta_original' => $img['ruta_original'],
            ], $imagenesMantenidas);

            $prendas_existentes_data[] = [
                'prenda_id' => $prendas[$i]->id,
                'nombre_prenda' => "Prenda {$i} Actualizada",
                'imagenes_existentes' => json_encode($imagenesExistentes),
                'imagenes_a_eliminar' => json_encode([$imagenAEliminar]),
            ];
        }

        // Preparar imagenes nuevas para prendas 6-10 (5 imágenes nuevas en total)
        $imagenesNuevas = [];
        for ($i = 0; $i < 5; $i++) {
            $imagenesNuevas[] = UploadedFile::fake()->image("nueva-{$i}.jpg");
        }

        $response = $this
            ->actingAs($this->asesor)
            ->putJson("/api/asesores/pedidos/{$pedido->id}/borrador", [
                'pedido' => json_encode([
                    'cliente' => 'Cliente Test',
                    'forma_de_pago' => 'Crédito',
                    'observaciones' => 'Test 14 prendas con cambios mixtos',
                ]),
                'prendas_existentes' => json_encode($prendas_existentes_data),
                'imagenes' => $imagenesNuevas,
            ]);

        $response->assertStatus(200);

        // 3️⃣ Verificar resultados finales
        $pedido->refresh();

        // Prendas 1-5: Deben seguir con 2 imágenes cada una
        for ($i = 1; $i <= 5; $i++) {
            $this->assertEquals(2, $prendas[$i]->fresh()->imagenes->count(),
                "Prenda {$i} debería tener 2 imágenes (sin cambios)");
        }

        // Prendas 6-10: Deben tener 3 imágenes cada una (2 + 1 nueva)
        for ($i = 6; $i <= 10; $i++) {
            $this->assertEquals(3, $prendas[$i]->fresh()->imagenes->count(),
                "Prenda {$i} debería tener 3 imágenes (2 + 1 nueva)");
        }

        // Prendas 11-14: Deben tener 1 imagen cada una (2 - 1 eliminada)
        for ($i = 11; $i <= 14; $i++) {
            $this->assertEquals(1, $prendas[$i]->fresh()->imagenes->count(),
                "Prenda {$i} debería tener 1 imagen (2 - 1 eliminada)");
        }

        // Verificar total: 10 + 15 + 4 = 29 imágenes
        $totalEsperado = (5 * 2) + (5 * 3) + (4 * 1);  // 10 + 15 + 4 = 29
        $totalReal = $pedido->prendas->sum(fn($p) => $p->fresh()->imagenes->count());
        $this->assertEquals($totalEsperado, $totalReal,
            "Total de imágenes debe ser {$totalEsperado}, obtenido {$totalReal}");
    }

    /**
     * Test: Editar múltiples veces sin perder imágenes
     *
     * Escenario:
     * - Crear prenda con 2 imágenes
     * - Edit 1: Agregar 1 imagen → 3 total
     * - Edit 2: Eliminar 1 imagen → 2 total
     * - Edit 3: Agregar 2 imágenes → 4 total
     *
     * Resultado: Sin duplicación, sin pérdida
     */
    public function test_editar_multiple_veces_sin_perder_imagenes()
    {
        $pedido = PedidoProduccion::factory()
            ->forAsesor($this->asesor->id)
            ->withState(['estado' => 'BORRADOR'])
            ->create();

        $prenda = $pedido->prendas()->create([
            'nombre_prenda' => 'Prenda Multi-Edit',
            'descripcion' => 'Test',
        ]);

        // Crear 2 imágenes iniciales
        $img1 = $prenda->imagenes()->create([
            'ruta_original' => '/storage/img1.jpg',
            'ruta_webp' => '/storage/img1.webp',
        ]);
        $img2 = $prenda->imagenes()->create([
            'ruta_original' => '/storage/img2.jpg',
            'ruta_webp' => '/storage/img2.webp',
        ]);

        $this->assertEquals(2, $prenda->imagenes->count());

        // 🔧 EDIT 1: Agregar 1 imagen
        $response = $this
            ->actingAs($this->asesor)
            ->putJson("/api/asesores/pedidos/{$pedido->id}/borrador", [
                'pedido' => json_encode(['cliente' => 'Test', 'forma_de_pago' => 'Crédito']),
                'prendas_existentes' => json_encode([
                    [
                        'prenda_id' => $prenda->id,
                        'nombre_prenda' => 'Prenda Multi-Edit',
                        'imagenes_existentes' => json_encode([
                            ['id' => $img1->id, 'ruta_original' => $img1->ruta_original],
                            ['id' => $img2->id, 'ruta_original' => $img2->ruta_original],
                        ]),
                    ],
                ]),
                'imagenes' => [UploadedFile::fake()->image('edit1.jpg')],
            ]);

        $response->assertStatus(200);
        $prenda->refresh();
        $this->assertEquals(3, $prenda->imagenes->count(), 'Edit 1: Debe tener 3 imágenes (2 + 1)');

        // 🔧 EDIT 2: Eliminar 1 imagen
        $imagenAEliminar = $prenda->imagenes->first()->id;
        $imagenesMantenidas = $prenda->imagenes->where('id', '!=', $imagenAEliminar)->all();

        $response = $this
            ->actingAs($this->asesor)
            ->putJson("/api/asesores/pedidos/{$pedido->id}/borrador", [
                'pedido' => json_encode(['cliente' => 'Test', 'forma_de_pago' => 'Crédito']),
                'prendas_existentes' => json_encode([
                    [
                        'prenda_id' => $prenda->id,
                        'nombre_prenda' => 'Prenda Multi-Edit',
                        'imagenes_existentes' => json_encode(array_map(fn($img) => [
                            'id' => $img->id,
                            'ruta_original' => $img->ruta_original,
                        ], $imagenesMantenidas)),
                        'imagenes_a_eliminar' => json_encode([$imagenAEliminar]),
                    ],
                ]),
            ]);

        $response->assertStatus(200);
        $prenda->refresh();
        $this->assertEquals(2, $prenda->imagenes->count(), 'Edit 2: Debe tener 2 imágenes (3 - 1)');

        // 🔧 EDIT 3: Agregar 2 imágenes
        $response = $this
            ->actingAs($this->asesor)
            ->putJson("/api/asesores/pedidos/{$pedido->id}/borrador", [
                'pedido' => json_encode(['cliente' => 'Test', 'forma_de_pago' => 'Crédito']),
                'prendas_existentes' => json_encode([
                    [
                        'prenda_id' => $prenda->id,
                        'nombre_prenda' => 'Prenda Multi-Edit',
                        'imagenes_existentes' => json_encode($prenda->imagenes->map(fn($img) => [
                            'id' => $img->id,
                            'ruta_original' => $img->ruta_original,
                        ])->toArray()),
                    ],
                ]),
                'imagenes' => [
                    UploadedFile::fake()->image('edit3a.jpg'),
                    UploadedFile::fake()->image('edit3b.jpg'),
                ],
            ]);

        $response->assertStatus(200);
        $prenda->refresh();
        $this->assertEquals(4, $prenda->imagenes->count(), 'Edit 3: Debe tener 4 imágenes (2 + 2)');
    }
}
