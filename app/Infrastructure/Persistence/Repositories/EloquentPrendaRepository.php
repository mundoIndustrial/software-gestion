<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Prenda\Repositories\PrendaRepositoryInterface;
use App\Domain\Prenda\Entities\Prenda;
use App\Models\Prenda as PrendaModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentPrendaRepository implements PrendaRepositoryInterface
{
    public function __construct(private PrendaModel $model) {}

    public function porId(int $id): ?Prenda
    {
        try {
            $modelo = $this->model->with(['telas', 'procesos', 'variaciones'])->findOrFail($id);
            return $this->mapearDelModelo($modelo);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    public function todas(): array
    {
        $modelos = $this->model->with(['telas', 'procesos', 'variaciones'])->get();
        return $modelos->map(fn($m) => $this->mapearDelModelo($m))->toArray();
    }

    public function porOrigen(string $origen): array
    {
        $modelos = $this->model
            ->where('origen', $origen)
            ->with(['telas', 'procesos', 'variaciones'])
            ->get();

        return $modelos->map(fn($m) => $this->mapearDelModelo($m))->toArray();
    }

    public function porTipoCotizacion(string $tipo): array
    {
        $modelos = $this->model
            ->where('tipo_cotizacion', $tipo)
            ->with(['telas', 'procesos', 'variaciones'])
            ->get();

        return $modelos->map(fn($m) => $this->mapearDelModelo($m))->toArray();
    }

    public function guardar(Prenda $prenda): void
    {
        $datos = $prenda->paraArray();

        // Separar datos de la prenda principal de las relaciones
        $datosModelo = [
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'genero' => $datos['genero'],
            'origen' => $datos['origen'],
            'tipo_cotizacion' => $datos['tipo_cotizacion'],
        ];

        if ($prenda->id()->valor() !== null) {
            // UPDATE
            $this->model->findOrFail($prenda->id()->valor())->update($datosModelo);
            $id = $prenda->id()->valor();
        } else {
            // CREATE
            $modelo = $this->model->create($datosModelo);
            $id = $modelo->id;
        }

        // Sincronizar relaciones many-to-many
        $modelo = $this->model->findOrFail($id);

        // Sincronizar telas
        $telaIds = array_column($datos['telas'], 'id');
        $modelo->telas()->sync($telaIds);

        // Sincronizar procesos
        $procesoIds = array_column($datos['procesos'], 'id');
        if (empty($procesoIds)) {
            $modelo->procesos()->detach();
        } else {
            $modelo->procesos()->sync($procesoIds);
        }

        // Sincronizar variaciones
        if (!empty($datos['variaciones'])) {
            $variacionDatos = [];
            foreach ($datos['variaciones'] as $var) {
                $variacionDatos[$var['id']] = [];
            }
            $modelo->variaciones()->sync($variacionDatos);
        } else {
            $modelo->variaciones()->detach();
        }
    }

    public function eliminar(int $id): void
    {
        $this->model->findOrFail($id)->delete();
    }

    public function contar(): int
    {
        return $this->model->count();
    }

    public function buscarPorNombre(string $nombre): array
    {
        $modelos = $this->model
            ->where('nombre', 'like', "%{$nombre}%")
            ->with(['telas', 'procesos', 'variaciones'])
            ->get();

        return $modelos->map(fn($m) => $this->mapearDelModelo($m))->toArray();
    }

    /**
     * Mapea PrendaModel (Eloquent) a Prenda (Domain Entity)
     */
    private function mapearDelModelo(PrendaModel $modelo): Prenda
    {
        $datos = [
            'id' => $modelo->id,
            'nombre' => $modelo->nombre,
            'descripcion' => $modelo->descripcion,
            'genero' => $modelo->genero,
            'origen' => $modelo->origen,
            'tipo_cotizacion' => $modelo->tipo_cotizacion,
            'telas' => $modelo->telas->map(function ($tela) {
                return [
                    'id' => $tela->id,
                    'nombre' => $tela->nombre,
                    'codigo' => $tela->codigo,
                ];
            })->toArray(),
            'procesos' => $modelo->procesos->map(function ($proceso) {
                return [
                    'id' => $proceso->id,
                    'nombre' => $proceso->nombre,
                ];
            })->toArray(),
            'variaciones' => $modelo->variaciones->map(function ($variacion) {
                return [
                    'id' => $variacion->id,
                    'talla' => $variacion->talla,
                    'color' => $variacion->color,
                ];
            })->toArray(),
        ];

        return Prenda::desdeArray($datos);
    }
}
