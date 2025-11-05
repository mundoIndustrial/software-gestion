<?php

namespace Database\Factories;

use App\Models\Tela;
use Illuminate\Database\Eloquent\Factories\Factory;

class TelaFactory extends Factory
{
    protected $model = Tela::class;

    public function definition(): array
    {
        return [
            'nombre_tela' => 'TELA ' . strtoupper($this->faker->unique()->lexify('???')),
        ];
    }
}
