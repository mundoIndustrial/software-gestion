<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PrendaCot;
use App\Models\PrendaTallaCot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuardarTallasCotizacionTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        // Usar usuario existente en BD (ID 18)
        $this->user = User::find(18);
        if (!$this->user) {
            $this->markTestSkipped('Usuario ID 18 no existe en la BD');
        }
    }

    /**
     * Test: Verificar que las tallas se guardan en prenda_tallas_cot
     */
    public function test_guardar_tallas_en_bd()
    {
        $prendaId = 1;

        // Verificar que existe la prenda
        $prenda = PrendaCot::find($prendaId);
        $this->assertNotNull($prenda, 'La prenda debe existir');

        // Crear talla con cantidad
        $talla = PrendaTallaCot::create([
            'prenda_cot_id' => $prendaId,
            'talla' => 'M',
            'cantidad' => 10,
        ]);

        // Verificar que se guardó en BD
        $this->assertNotNull($talla->id);
        $this->assertEquals($prendaId, $talla->prenda_cot_id);
        $this->assertEquals('M', $talla->talla);
        $this->assertEquals(10, $talla->cantidad);

        // Verificar que se puede recuperar desde la BD
        $tallaRecuperada = PrendaTallaCot::find($talla->id);
        $this->assertNotNull($tallaRecuperada);
        $this->assertEquals('M', $tallaRecuperada->talla);
        $this->assertEquals(10, $tallaRecuperada->cantidad);

        echo "\n✅ Test: Guardar talla en BD - PASADO\n";
        echo "   ✓ Talla guardada con ID: {$talla->id}\n";
        echo "   ✓ Talla: {$talla->talla}, Cantidad: {$talla->cantidad}\n";
        echo "   ✓ Datos recuperables desde la BD\n";
    }

    /**
     * Test: Verificar que múltiples tallas se guardan correctamente
     */
    public function test_guardar_multiples_tallas()
    {
        $prendaId = 1;

        // Crear múltiples tallas con diferentes cantidades
        $tallasData = [
            ['talla' => 'XS', 'cantidad' => 5],
            ['talla' => 'S', 'cantidad' => 10],
            ['talla' => 'M', 'cantidad' => 15],
            ['talla' => 'L', 'cantidad' => 12],
            ['talla' => 'XL', 'cantidad' => 8],
            ['talla' => 'XXL', 'cantidad' => 3],
        ];

        $tallasCreadas = [];
        foreach ($tallasData as $datos) {
            $datos['prenda_cot_id'] = $prendaId;
            $tallasCreadas[] = PrendaTallaCot::create($datos);
        }

        // Verificar que se guardaron todas
        $this->assertCount(6, $tallasCreadas, 'Debe haber 6 tallas creadas');

        // Verificar datos específicos
        $tallaM = collect($tallasCreadas)->where('talla', 'M')->first();
        $this->assertNotNull($tallaM);
        $this->assertEquals(15, $tallaM->cantidad);

        $tallaXL = collect($tallasCreadas)->where('talla', 'XL')->first();
        $this->assertNotNull($tallaXL);
        $this->assertEquals(8, $tallaXL->cantidad);

        // Verificar total de cantidad
        $totalCantidad = collect($tallasCreadas)->sum('cantidad');
        $this->assertEquals(53, $totalCantidad);

        echo "\n✅ Test: Guardar múltiples tallas - PASADO\n";
        echo "   ✓ Tallas guardadas: " . count($tallasCreadas) . "\n";
        echo "   ✓ Total de cantidad: {$totalCantidad}\n";
        echo "   ✓ Datos específicos verificados\n";
    }

    /**
     * Test: Verificar que las cantidades se guardan correctamente
     */
    public function test_cantidades_tallas()
    {
        $prendaId = 1;

        // Crear tallas con diferentes cantidades
        $cantidades = [1, 5, 10, 20, 50, 100];
        $tallasCreadas = [];

        foreach ($cantidades as $cantidad) {
            $tallasCreadas[] = PrendaTallaCot::create([
                'prenda_cot_id' => $prendaId,
                'talla' => "T{$cantidad}",
                'cantidad' => $cantidad,
            ]);
        }

        // Verificar que se guardaron todas
        $this->assertCount(6, $tallasCreadas, 'Debe haber 6 tallas creadas');

        // Verificar cantidades
        $tallasOrdenadas = collect($tallasCreadas)->sortBy('cantidad')->values();
        foreach ($tallasOrdenadas as $index => $talla) {
            $this->assertEquals($cantidades[$index], $talla->cantidad);
        }

        echo "\n✅ Test: Cantidades de tallas - PASADO\n";
        echo "   ✓ Todas las cantidades guardadas correctamente\n";
    }

    /**
     * Test: Verificar que se pueden actualizar tallas y cantidades
     */
    public function test_actualizar_tallas()
    {
        $prendaId = 1;

        // Crear talla
        $talla = PrendaTallaCot::create([
            'prenda_cot_id' => $prendaId,
            'talla' => 'L',
            'cantidad' => 10,
        ]);

        // Actualizar cantidad
        $talla->update(['cantidad' => 20]);

        // Verificar actualización
        $tallaActualizada = PrendaTallaCot::find($talla->id);
        $this->assertEquals(20, $tallaActualizada->cantidad);

        echo "\n✅ Test: Actualizar tallas - PASADO\n";
        echo "   ✓ Talla actualizada correctamente\n";
        echo "   ✓ Nueva cantidad: {$tallaActualizada->cantidad}\n";
    }

    /**
     * Test: Verificar que se pueden eliminar tallas
     */
    public function test_eliminar_tallas()
    {
        $prendaId = 1;

        // Crear talla
        $talla = PrendaTallaCot::create([
            'prenda_cot_id' => $prendaId,
            'talla' => 'XXL',
            'cantidad' => 5,
        ]);

        $tallaId = $talla->id;

        // Eliminar
        $talla->delete();

        // Verificar eliminación
        $tallaEliminada = PrendaTallaCot::find($tallaId);
        $this->assertNull($tallaEliminada);

        echo "\n✅ Test: Eliminar tallas - PASADO\n";
        echo "   ✓ Talla eliminada correctamente\n";
    }
}
