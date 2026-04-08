<?php

namespace Tests\Feature\Architecture;

use Tests\TestCase;

class PedidosDomainArchitectureTest extends TestCase
{
    public function test_domain_pedidos_no_depende_de_laravel_ni_de_modelos(): void
    {
        $domainPath = base_path('app/Domain/Pedidos');
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($domainPath)
        );
        $archivos = collect();

        foreach ($iterator as $archivo) {
            if ($archivo->isFile() && $archivo->getExtension() === 'php') {
                $archivos->push($archivo->getPathname());
            }
        }

        if ($archivos->isEmpty()) {
            $this->fail('No se encontraron archivos en app/Domain/Pedidos');
        }

        $patronesProhibidos = [
            'use App\\Models\\',
            'use Illuminate\\',
            'use Illuminate\\Support\\Facades\\',
            'DB::',
            'Log::',
            'Storage::',
            'Auth::',
            ' app(',
            'app(',
            ' resolve(',
            'resolve(',
        ];

        $violaciones = [];

        foreach ($archivos as $archivo) {
            $contenido = file_get_contents($archivo);

            foreach ($patronesProhibidos as $patron) {
                if (str_contains($contenido, $patron)) {
                    $violaciones[] = basename($archivo) . ' contiene "' . $patron . '"';
                }
            }
        }

        $this->assertSame([], $violaciones, implode(PHP_EOL, $violaciones));
    }
}
