<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tela;

class TelasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $telas = [
            ['nombre_tela' => 'Algodón'],
            ['nombre_tela' => 'Poliéster'],
            ['nombre_tela' => 'Lana'],
            ['nombre_tela' => 'Seda'],
            ['nombre_tela' => 'Lino'],
        ];

        foreach ($telas as $tela) {
            Tela::create($tela);
        }
    }
}
