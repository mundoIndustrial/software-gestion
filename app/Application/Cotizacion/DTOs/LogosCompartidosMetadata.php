<?php

namespace App\Application\Cotizacion\DTOs;

/**
 * Extrae metadatos de logos compartidos del FormData de cotización bordado/logo.
 *
 * @return array<string, array<string, mixed>> nombreCompartido => metadata
 */
final class LogosCompartidosMetadata
{
    public static function fromHttpRequest($request): array
    {
        $out = [];
        foreach ($request->all() as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (!preg_match('/^logo_compartido_metadata_(\d+)$/', (string) $key)) {
                continue;
            }
            $decoded = json_decode($value, true);
            if (!is_array($decoded) || empty($decoded['nombreCompartido'])) {
                continue;
            }
            $out[(string) $decoded['nombreCompartido']] = $decoded;
        }

        return $out;
    }
}
