<?php

namespace Database\Factories;

use App\Models\RegistroPisoProduccion;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistroPisoProduccionFactory extends Factory
{
    protected $model = RegistroPisoProduccion::class;

    public function definition(): array
    {
        $porcionTiempo = $this->faker->randomFloat(2, 0.5, 1);
        $numeroOperarios = $this->faker->numberBetween(5, 20);
        $tiempoCiclo = $this->faker->randomFloat(2, 500, 1000);
        $tiempoParadaNoProgramada = $this->faker->numberBetween(0, 600);
        $paradaProgramada = $this->faker->randomElement(['DESAYUNO', 'MEDIA TARDE', 'NINGUNA']);
        
        $tiempoParaProgramada = match($paradaProgramada) {
            'DESAYUNO', 'MEDIA TARDE' => 900,
            default => 0
        };

        $tiempoDisponible = (3600 * $porcionTiempo * $numeroOperarios) 
                          - $tiempoParadaNoProgramada 
                          - $tiempoParaProgramada;
        $tiempoDisponible = max(0, $tiempoDisponible);

        $meta = $tiempoCiclo > 0 ? ($tiempoDisponible / $tiempoCiclo) * 0.9 : 0;
        $cantidad = $this->faker->numberBetween(0, (int)($meta * 1.2));
        $eficiencia = $meta > 0 ? ($cantidad / $meta) : 0;

        return [
            'fecha' => $this->faker->date(),
            'modulo' => $this->faker->randomElement(['MODULO 1', 'MODULO 2', 'MODULO 3']),
            'orden_produccion' => $this->faker->numberBetween(1000, 9999),
            'hora' => $this->faker->randomElement(['HORA 01', 'HORA 02', 'HORA 03', 'HORA 04', 'HORA 05', 'HORA 06', 'HORA 07', 'HORA 08']),
            'tiempo_ciclo' => $tiempoCiclo,
            'porcion_tiempo' => $porcionTiempo,
            'cantidad' => $cantidad,
            'producida' => $cantidad,
            'paradas_programadas' => $paradaProgramada,
            'paradas_no_programadas' => $this->faker->optional()->randomElement(['Falta de material', 'Mantenimiento', 'Ninguna']),
            'tiempo_parada_no_programada' => $tiempoParadaNoProgramada,
            'numero_operarios' => $numeroOperarios,
            'tiempo_para_programada' => $tiempoParaProgramada,
            'tiempo_disponible' => $tiempoDisponible,
            'meta' => $meta,
            'eficiencia' => $eficiencia,
        ];
    }
}
