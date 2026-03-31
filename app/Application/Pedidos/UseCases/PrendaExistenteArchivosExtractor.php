<?php

namespace App\Application\Pedidos\UseCases;

use Illuminate\Http\Request;

class PrendaExistenteArchivosExtractor
{
    public function extraer(Request $requestOriginal, int $prendaIndex): array
    {
        $archivos = [];
        $prefijo = 'prenda_existente_' . $prendaIndex . '_';

        foreach ($requestOriginal->allFiles() as $key => $value) {
            $claveNormalizada = $this->obtenerClaveNormalizada($key, $prefijo);
            if ($claveNormalizada === null) {
                continue;
            }

            if ($this->agregarImagenes($archivos, $claveNormalizada, $value)) {
                continue;
            }

            if ($this->agregarFotosTela($archivos, $claveNormalizada, $value)) {
                continue;
            }

            if ($this->agregarFotosProcesoNuevo($archivos, $claveNormalizada, $value)) {
                continue;
            }

            if ($this->agregarFotosProcesoTallasNuevo($archivos, $claveNormalizada, $value)) {
                continue;
            }

            $this->agregarFotosColor($archivos, $claveNormalizada, $value);
        }

        return $archivos;
    }

    private function obtenerClaveNormalizada(mixed $key, string $prefijo): ?string
    {
        if (!is_string($key) || strpos($key, $prefijo) !== 0) {
            return null;
        }

        return substr($key, strlen($prefijo));
    }

    private function agregarImagenes(array &$archivos, string $claveNormalizada, mixed $value): bool
    {
        if (!preg_match('/^imagenes(?:\[\])?$/', $claveNormalizada)) {
            return false;
        }

        $archivos['imagenes'] = is_array($value) ? $value : [$value];

        return true;
    }

    private function agregarFotosTela(array &$archivos, string $claveNormalizada, mixed $value): bool
    {
        // Formato explícito: fotos_tela[0]
        if (preg_match('/^fotos_tela\[(\d+)\]$/', $claveNormalizada, $matches)) {
            $indice = (int) $matches[1];
            $archivos['fotos_tela'][$indice] = $value;
            return true;
        }

        // Formato normalizado por PHP/Laravel: fotos_tela => [0 => UploadedFile, ...]
        if ($claveNormalizada === 'fotos_tela') {
            if (is_array($value)) {
                foreach ($value as $indice => $archivo) {
                    $archivos['fotos_tela'][(int) $indice] = $archivo;
                }
            } elseif ($value !== null) {
                $archivos['fotos_tela'][] = $value;
            }
            return true;
        }

        return false;
    }

    private function agregarFotosProcesoNuevo(array &$archivos, string $claveNormalizada, mixed $value): bool
    {
        if (!preg_match('/^fotosProcesoNuevo_(\d+)(?:\[\])?$/', $claveNormalizada, $matches)) {
            return false;
        }

        $archivos['fotosProcesoNuevo_' . $matches[1]] = is_array($value) ? $value : [$value];

        return true;
    }

    private function agregarFotosProcesoTallasNuevo(array &$archivos, string $claveNormalizada, mixed $value): bool
    {
        if (!preg_match('/^fotosProcesoTallasNuevo_(\d+)_([a-zA-Z]+)_(.+?)(?:\[\])?$/', $claveNormalizada, $matches)) {
            return false;
        }

        $key = 'fotosProcesoTallasNuevo_' . $matches[1] . '_' . $matches[2] . '_' . $matches[3];
        $archivos[$key] = is_array($value) ? $value : [$value];

        return true;
    }

    private function agregarFotosColor(array &$archivos, string $claveNormalizada, mixed $value): void
    {
        if (preg_match('/^fotos_color\[(\d+)\]$/', $claveNormalizada, $matches)) {
            $archivos['fotos_color'][(int) $matches[1]] = $value;
            return;
        }

        if ($claveNormalizada !== 'fotos_color') {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $indice => $archivo) {
                $archivos['fotos_color'][(int) $indice] = $archivo;
            }
            return;
        }

        if ($value !== null) {
            $archivos['fotos_color'][] = $value;
        }
    }
}
