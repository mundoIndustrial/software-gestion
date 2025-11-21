<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Elimina todas las tablas no utilizadas
     * ORDEN IMPORTANTE: Eliminar primero las que tienen FK, luego las referenciadas
     */
    public function up(): void
    {
        // Desactivar restricciones de clave foránea temporalmente
        Schema::disableForeignKeyConstraints();

        // PASO 1: Eliminar tablas que tienen referencias (FK)
        // Estas tablas tienen claves foráneas que apuntan a otras tablas

        // Eliminar tabla costos_prenda (tiene FK a prendas_cotizacion y componentes_prenda)
        if (Schema::hasTable('costos_prenda')) {
            Schema::dropIfExists('costos_prenda');
        }

        // Eliminar tabla historial_cotizaciones (tiene FK a cotizaciones)
        if (Schema::hasTable('historial_cotizaciones')) {
            Schema::dropIfExists('historial_cotizaciones');
        }

        // Eliminar tabla formatos_cotizacion (tiene FK a cotizaciones)
        if (Schema::hasTable('formatos_cotizacion')) {
            Schema::dropIfExists('formatos_cotizacion');
        }

        // PASO 2: Eliminar tablas que son referenciadas
        // Ahora que eliminamos las que las referenciaban, podemos eliminar estas

        // Eliminar tabla prendas_cotizacion (antigua, reemplazada por prendas_cotizaciones)
        if (Schema::hasTable('prendas_cotizacion')) {
            Schema::dropIfExists('prendas_cotizacion');
        }

        // Eliminar tabla componentes_prenda (no se usa)
        if (Schema::hasTable('componentes_prenda')) {
            Schema::dropIfExists('componentes_prenda');
        }

        // Eliminar tabla especificaciones_cotizaciones (no se usa)
        if (Schema::hasTable('especificaciones_cotizaciones')) {
            Schema::dropIfExists('especificaciones_cotizaciones');
        }

        // Reactivar restricciones de clave foránea
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No restaurar - estas tablas no son necesarias
    }
};
