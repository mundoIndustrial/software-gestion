<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogoPrendaCot extends Model
{
    protected $table = 'logo_prenda_cot';

    protected $fillable = [
        'logo_cot_id',
        'nombre_producto',
        'descripcion',
        'cantidad',
    ];

    /**
     * Relación: Pertenece a una LogoCotizacion
     */
    public function logoCotizacion(): BelongsTo
    {
        return $this->belongsTo(LogoCotizacion::class, 'logo_cot_id');
    }

    /**
     * Relación: Tiene muchas prendas técnicas
     */
    public function prendasTecnicas(): HasMany
    {
        return $this->hasMany(LogoCotizacionTecnicaPrenda::class, 'logo_prenda_cot_id');
    }
}
