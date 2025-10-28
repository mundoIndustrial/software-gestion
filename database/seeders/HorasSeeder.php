<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Hora;

class HorasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $horas = [
            ['hora' => 1, 'rango' => '08:00am - 09:00am'],
            ['hora' => 2, 'rango' => '09:00am - 10:00am'],
            ['hora' => 3, 'rango' => '10:00am - 11:00am'],
            ['hora' => 4, 'rango' => '11:00am - 12:00pm'],
            ['hora' => 5, 'rango' => '12:00pm - 01:00pm'],
            ['hora' => 6, 'rango' => '01:00pm - 02:00pm'],
            ['hora' => 7, 'rango' => '02:00pm - 03:00pm'],
            ['hora' => 8, 'rango' => '03:00pm - 04:00pm'],
            ['hora' => 9, 'rango' => '04:00pm - 05:00pm'],
            ['hora' => 10, 'rango' => '05:00pm - 06:00pm'],
            ['hora' => 11, 'rango' => '06:00pm - 07:00pm'],
            ['hora' => 12, 'rango' => '07:00pm - 08:00pm'],
        ];

        foreach ($horas as $hora) {
            Hora::create($hora);
        }
    }
}
