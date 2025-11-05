<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;
use App\Models\RegistroPisoCorte;
use App\Models\User;
use App\Models\Hora;
use App\Models\Maquina;
use App\Models\Tela;

class TablerosOrdenamientoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que verifica el ordenamiento ascendente de registros de producción
     */
    public function test_produccion_registros_ordenados_por_id_ascendente()
    {
        // Crear 5 registros de producción
        $registros = RegistroPisoProduccion::factory()->count(5)->create();

        // Hacer petición GET a la ruta de tableros
        $response = $this->get('/tableros');

        $response->assertStatus(200);

        // Obtener los registros de la vista
        $registrosVista = $response->viewData('registros');

        // Verificar que están ordenados por ID ascendente
        $ids = $registrosVista->pluck('id')->toArray();
        $idsSorted = $ids;
        sort($idsSorted);

        $this->assertEquals($idsSorted, $ids, 'Los registros de producción deben estar ordenados por ID ascendente');
    }

    /**
     * Test que verifica el ordenamiento ascendente de registros de polos
     */
    public function test_polos_registros_ordenados_por_id_ascendente()
    {
        // Crear 5 registros de polos
        $registros = RegistroPisoPolo::factory()->count(5)->create();

        // Hacer petición GET a la ruta de tableros
        $response = $this->get('/tableros');

        $response->assertStatus(200);

        // Obtener los registros de la vista
        $registrosVista = $response->viewData('registrosPolos');

        // Verificar que están ordenados por ID ascendente
        $ids = $registrosVista->pluck('id')->toArray();
        $idsSorted = $ids;
        sort($idsSorted);

        $this->assertEquals($idsSorted, $ids, 'Los registros de polos deben estar ordenados por ID ascendente');
    }

    /**
     * Test que verifica el ordenamiento ascendente de registros de corte
     */
    public function test_corte_registros_ordenados_por_id_ascendente()
    {
        // Crear datos necesarios para corte
        $hora = Hora::factory()->create();
        $operario = User::factory()->create();
        $maquina = Maquina::factory()->create();
        $tela = Tela::factory()->create();

        // Crear 5 registros de corte
        $registros = RegistroPisoCorte::factory()->count(5)->create([
            'hora_id' => $hora->id,
            'operario_id' => $operario->id,
            'maquina_id' => $maquina->id,
            'tela_id' => $tela->id,
        ]);

        // Hacer petición GET a la ruta de tableros
        $response = $this->get('/tableros');

        $response->assertStatus(200);

        // Obtener los registros de la vista
        $registrosVista = $response->viewData('registrosCorte');

        // Verificar que están ordenados por ID ascendente
        $ids = $registrosVista->pluck('id')->toArray();
        $idsSorted = $ids;
        sort($idsSorted);

        $this->assertEquals($idsSorted, $ids, 'Los registros de corte deben estar ordenados por ID ascendente');
    }

    /**
     * Test que verifica que nuevos registros se agregan al final
     */
    public function test_nuevos_registros_se_agregan_al_final()
    {
        // Crear 3 registros iniciales
        $registro1 = RegistroPisoProduccion::factory()->create();
        $registro2 = RegistroPisoProduccion::factory()->create();
        $registro3 = RegistroPisoProduccion::factory()->create();

        // Obtener registros ordenados
        $registros = RegistroPisoProduccion::orderBy('id', 'asc')->get();

        // Verificar orden
        $this->assertEquals($registro1->id, $registros[0]->id);
        $this->assertEquals($registro2->id, $registros[1]->id);
        $this->assertEquals($registro3->id, $registros[2]->id);

        // Crear un nuevo registro
        $registro4 = RegistroPisoProduccion::factory()->create();

        // Obtener registros ordenados nuevamente
        $registrosActualizados = RegistroPisoProduccion::orderBy('id', 'asc')->get();

        // Verificar que el nuevo registro está al final
        $this->assertEquals($registro4->id, $registrosActualizados->last()->id);
        $this->assertGreaterThan($registro3->id, $registro4->id);
    }

    /**
     * Test de integración: crear múltiples registros y verificar orden
     */
    public function test_multiples_registros_mantienen_orden_correcto()
    {
        // Crear 10 registros de producción
        for ($i = 1; $i <= 10; $i++) {
            RegistroPisoProduccion::factory()->create();
        }

        // Obtener todos los registros ordenados
        $registros = RegistroPisoProduccion::orderBy('id', 'asc')->get();

        // Verificar que cada registro tiene un ID mayor que el anterior
        for ($i = 1; $i < count($registros); $i++) {
            $this->assertGreaterThan(
                $registros[$i - 1]->id,
                $registros[$i]->id,
                "El registro en posición {$i} debe tener un ID mayor que el anterior"
            );
        }
    }

    /**
     * Test JSON API: verificar que la respuesta JSON también está ordenada
     */
    public function test_api_json_retorna_registros_ordenados()
    {
        // Crear 5 registros
        RegistroPisoProduccion::factory()->count(5)->create();

        // Hacer petición JSON
        $response = $this->getJson('/tableros', ['Accept' => 'application/json']);

        $response->assertStatus(200);

        // Obtener los IDs de la respuesta JSON
        $registros = $response->json('registros');
        $ids = array_column($registros, 'id');

        // Verificar que están ordenados ascendentemente
        $idsSorted = $ids;
        sort($idsSorted);

        $this->assertEquals($idsSorted, $ids, 'La API JSON debe retornar registros ordenados por ID ascendente');
    }
}
