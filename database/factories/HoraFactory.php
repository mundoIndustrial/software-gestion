<?php

namespace Database\Factories;

use App\Models\Hora;
use Illuminate\Database\Eloquent\Factories\Factory;

class HoraFactory extends Factory
{
    protected $model = Hora::class;

    public function definition(): array
    {
        return [
            'hora' => $this->faker->randomElement([
                'HORA 01', 'HORA 02', 'HORA 03', 'HORA 04', 
                'HORA 05', 'HORA 06', 'HORA 07', 'HORA 08'
            ]),
        ];
    }
}
