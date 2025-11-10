<?php

namespace Database\Factories;

use App\Models\RegistroPisoCorte;
use App\Models\Hora;
use App\Models\User;
use App\Models\Maquina;
use App\Models\Tela;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistroPisoCorteFactory extends Factory
{
    protected $model = RegistroPisoCorte::class;

    public function definition(): array
    {
        $porcionTiempo = $this->faker->randomFloat(2, 0.5, 1);
        $tiempoCiclo = $this->faker->randomFloat(2, 100, 500);
        $tiempoParadaNoProgramada = $this->faker->numberBetween(0, 600);
        $paradaProgramada = $this->faker->randomElement(['DESAYUNO', 'MEDIA TARDE', 'NINGUNA']);
        $tipoExtendido = $this->faker->randomElement(['Trazo Largo', 'Trazo Corto', 'Ninguno']);
        $numeroCapas = $this->faker->numberBetween(10, 50);
        $tiempoTrazado = $this->faker->numberBetween(0, 300);
        
        $tiempoParaProgramada = match($paradaProgramada) {
            'DESAYUNO', 'MEDIA TARDE' => 900,
            default => 0
        };

        $tiempoExtendido = match($tipoExtendido) {
            'Trazo Largo' => 40 * $numeroCapas,
            'Trazo Corto' => 25 * $numeroCapas,
            default => 0
        };

        $tiempoDisponible = (3600 * $porcionTiempo) 
                          - $tiempoParaProgramada
                          - $tiempoParadaNoProgramada
                          - $tiempoExtendido
                          - $tiempoTrazado;
        $tiempoDisponible = max(0, $tiempoDisponible);

        $meta = $tiempoCiclo > 0 ? $tiempoDisponible / $tiempoCiclo : 0;
        $cantidad = $this->faker->numberBetween(0, (int)($meta * 1.2));
        $eficiencia = $meta > 0 ? ($cantidad / $meta) : 0;

        return [
            'fecha' => $this->faker->date(),
            'orden_produccion' => $this->faker->numberBetween(1000, 9999),
            'hora_id' => Hora::factory(),
            'operario_id' => User::factory(),
            'maquina_id' => Maquina::factory(),
            'tela_id' => Tela::factory(),
            'actividad' => $this->faker->randomElement(['Cortar', 'Extender', 'Trazar']),
            'tiempo_ciclo' => $tiempoCiclo,
            'porcion_tiempo' => $porcionTiempo,
            'cantidad' => $cantidad,
            'paradas_programadas' => $paradaProgramada,
            'tiempo_para_programada' => $tiempoParaProgramada,
            'paradas_no_programadas' => $this->faker->optional()->randomElement(['Falta de material', 'Mantenimiento', 'Ninguna']),
            'tiempo_parada_no_programada' => $tiempoParadaNoProgramada,
            'tipo_extendido' => $tipoExtendido,
            'numero_capas' => $numeroCapas,
            'tiempo_extendido' => $tiempoExtendido,
            'trazado' => $this->faker->randomElement(['SI', 'NO']),
            'tiempo_trazado' => $tiempoTrazado,
            'tiempo_disponible' => $tiempoDisponible,
            'meta' => $meta,
            'eficiencia' => $eficiencia,
        ];
    }
}
