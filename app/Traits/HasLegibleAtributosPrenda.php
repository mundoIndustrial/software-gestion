<?php

namespace App\Traits;

use App\Helpers\AtributosPrendaHelper;

/**
 * HasLegibleAtributosPrenda
 * 
 * Trait que proporciona métodos para obtener versiones legibles de color_id, tela_id y referencia
 * 
 * Se usa en modelos que tengan campos: color_id, tela_id, referencia
 */
trait HasLegibleAtributosPrenda
{
    /**
     * Obtener el nombre del color de forma legible
     * 
     * @return string
     */
    public function getColorLabelAttribute(): string
    {
        return AtributosPrendaHelper::obtenerNombreColor($this->color_id ?? null);
    }

    /**
     * Obtener el nombre de la tela de forma legible
     * 
     * @return string
     */
    public function getTelaLabelAttribute(): string
    {
        return AtributosPrendaHelper::obtenerNombreTela($this->tela_id ?? null);
    }

    /**
     * Obtener la referencia de la tela de forma legible
     * 
     * @return string
     */
    public function getTelaReferenciaAttribute(): string
    {
        return AtributosPrendaHelper::obtenerReferenciaTela($this->tela_id ?? null);
    }

    /**
     * Obtener la tela con formato legible: "Nombre Tela (Ref: XXXXX)"
     * 
     * @return string
     */
    public function getTelaFormatoAttribute(): string
    {
        return AtributosPrendaHelper::formatearTela($this->tela_id ?? null);
    }

    /**
     * Obtener información completa del color
     * 
     * @return array
     */
    public function getColorInfoAttribute(): array
    {
        return AtributosPrendaHelper::obtenerColor($this->color_id ?? null);
    }

    /**
     * Obtener información completa de la tela
     * 
     * @return array
     */
    public function getTelaInfoAttribute(): array
    {
        return AtributosPrendaHelper::obtenerTela($this->tela_id ?? null);
    }
}
