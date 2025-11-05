<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;
use App\Models\RegistroPisoCorte;
use App\Models\User;

class TablerosPaginationTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear un usuario para autenticación
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_displays_pagination_controls_for_produccion()
    {
        // Crear 60 registros para forzar paginación (50 por página)
        RegistroPisoProduccion::factory()->count(60)->create();

        $response = $this->get(route('tableros.index'));

        $response->assertStatus(200);
        $response->assertSee('pagination-controls');
        $response->assertSee('paginationControls-produccion');
        $response->assertSee('Mostrando');
        $response->assertSee('de 60 registros');
    }

    /** @test */
    public function it_displays_pagination_controls_for_polos()
    {
        // Crear 60 registros para forzar paginación
        RegistroPisoPolo::factory()->count(60)->create();

        $response = $this->get(route('tableros.index'));

        $response->assertStatus(200);
        $response->assertSee('paginationControls-polos');
    }

    /** @test */
    public function it_displays_pagination_controls_for_corte()
    {
        // Crear 60 registros para forzar paginación
        RegistroPisoCorte::factory()->count(60)->create();

        $response = $this->get(route('tableros.index'));

        $response->assertStatus(200);
        $response->assertSee('paginationControls-corte');
    }

    /** @test */
    public function it_shows_correct_number_of_records_on_first_page()
    {
        // Crear 60 registros
        RegistroPisoProduccion::factory()->count(60)->create();

        $response = $this->get(route('tableros.index'));

        $response->assertStatus(200);
        // Debe mostrar "Mostrando 1-50 de 60 registros"
        $response->assertSee('Mostrando 1-50 de 60 registros');
    }

    /** @test */
    public function it_navigates_to_second_page_correctly()
    {
        // Crear 60 registros
        RegistroPisoProduccion::factory()->count(60)->create();

        $response = $this->get(route('tableros.index', ['page' => 2]));

        $response->assertStatus(200);
        // Debe mostrar "Mostrando 51-60 de 60 registros"
        $response->assertSee('Mostrando 51-60 de 60 registros');
    }

    /** @test */
    public function it_displays_correct_pagination_buttons()
    {
        // Crear 150 registros para tener 3 páginas
        RegistroPisoProduccion::factory()->count(150)->create();

        $response = $this->get(route('tableros.index'));

        $response->assertStatus(200);
        // Debe tener botones de navegación
        $response->assertSee('fa-angle-double-left'); // <<
        $response->assertSee('fa-angle-left'); // <
        $response->assertSee('fa-angle-right'); // >
        $response->assertSee('fa-angle-double-right'); // >>
    }

    /** @test */
    public function it_shows_active_page_button()
    {
        // Crear 100 registros
        RegistroPisoProduccion::factory()->count(100)->create();

        $response = $this->get(route('tableros.index', ['page' => 2]));

        $response->assertStatus(200);
        // El botón de la página 2 debe tener la clase 'active'
        $response->assertSee('class="pagination-btn page-number active"', false);
    }

    /** @test */
    public function it_returns_json_for_ajax_requests()
    {
        // Crear 60 registros
        RegistroPisoProduccion::factory()->count(60)->create();

        $response = $this->get(route('tableros.index', ['page' => 2, 'section' => 'produccion']), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'table_html',
            'pagination' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
                'first_item',
                'last_item',
                'links_html'
            ],
            'debug' => [
                'server_time_ms',
                'section'
            ]
        ]);
    }
    
    /** @test */
    public function ajax_request_returns_correct_section_data()
    {
        // Crear registros para cada sección
        RegistroPisoProduccion::factory()->count(60)->create();
        RegistroPisoPolo::factory()->count(60)->create();

        // Probar producción
        $response = $this->get(route('tableros.index', ['page' => 1, 'section' => 'produccion']), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json'
        ]);
        $response->assertStatus(200);
        $response->assertJson(['debug' => ['section' => 'produccion']]);

        // Probar polos
        $response = $this->get(route('tableros.index', ['page' => 1, 'section' => 'polos']), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json'
        ]);
        $response->assertStatus(200);
        $response->assertJson(['debug' => ['section' => 'polos']]);
    }

    /** @test */
    public function it_shows_correct_page_numbers_around_current_page()
    {
        // Crear 300 registros para tener 6 páginas
        RegistroPisoProduccion::factory()->count(300)->create();

        // Ir a la página 4
        $response = $this->get(route('tableros.index', ['page' => 4]));

        $response->assertStatus(200);
        // Debe mostrar páginas 2, 3, 4, 5, 6 (2 antes y 2 después de la actual)
        $response->assertSee('data-page="2"', false);
        $response->assertSee('data-page="3"', false);
        $response->assertSee('data-page="4"', false);
        $response->assertSee('data-page="5"', false);
        $response->assertSee('data-page="6"', false);
    }

    /** @test */
    public function it_disables_previous_buttons_on_first_page()
    {
        // Crear 60 registros
        RegistroPisoProduccion::factory()->count(60)->create();

        $response = $this->get(route('tableros.index', ['page' => 1]));

        $response->assertStatus(200);
        // Los botones de "anterior" deben estar deshabilitados
        $response->assertSee('disabled', false);
    }

    /** @test */
    public function it_disables_next_buttons_on_last_page()
    {
        // Crear 60 registros (2 páginas)
        RegistroPisoProduccion::factory()->count(60)->create();

        $response = $this->get(route('tableros.index', ['page' => 2]));

        $response->assertStatus(200);
        // Los botones de "siguiente" deben estar deshabilitados
        $response->assertSee('disabled', false);
    }

    /** @test */
    public function it_orders_records_in_descending_order()
    {
        // Crear registros con IDs específicos
        $registro1 = RegistroPisoProduccion::factory()->create(['id' => 1]);
        $registro2 = RegistroPisoProduccion::factory()->create(['id' => 2]);
        $registro3 = RegistroPisoProduccion::factory()->create(['id' => 3]);

        $response = $this->get(route('tableros.index'));

        $response->assertStatus(200);
        
        // Verificar que los registros están en orden descendente
        $content = $response->getContent();
        $pos3 = strpos($content, "data-id=\"{$registro3->id}\"");
        $pos2 = strpos($content, "data-id=\"{$registro2->id}\"");
        $pos1 = strpos($content, "data-id=\"{$registro1->id}\"");
        
        // El registro 3 debe aparecer antes que el 2, y el 2 antes que el 1
        $this->assertLessThan($pos2, $pos3);
        $this->assertLessThan($pos1, $pos2);
    }

    /** @test */
    public function it_updates_progress_bar_correctly()
    {
        // Crear 100 registros (2 páginas)
        RegistroPisoProduccion::factory()->count(100)->create();

        // Primera página (50%)
        $response = $this->get(route('tableros.index', ['page' => 1]));
        $response->assertStatus(200);
        $response->assertSee('width: 50%', false);

        // Segunda página (100%)
        $response = $this->get(route('tableros.index', ['page' => 2]));
        $response->assertStatus(200);
        $response->assertSee('width: 100%', false);
    }
}
