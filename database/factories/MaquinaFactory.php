<?php

namespace Database\Factories;

use App\Models\Maquina;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaquinaFactory extends Factory
{
    protected $model = Maquina::class;

    public function definition(): array
    {
        return [
            'nombre_maquina' => 'MAQUINA ' . strtoupper($this->faker->unique()->lexify('???')),
        ];
    }
}
