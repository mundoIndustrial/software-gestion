<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        $suffix = now()->format('YmdHisv') . '_' . bin2hex(random_bytes(3));

        return [
            'user_id' => User::factory(),
            'nombre' => $this->faker->company() . ' ' . $suffix,
            'email' => "cliente_{$suffix}@test.local",
            'telefono' => $this->faker->numerify('3#########'),
            'ciudad' => $this->faker->city(),
            'notas' => $this->faker->optional()->sentence(),
        ];
    }
}
