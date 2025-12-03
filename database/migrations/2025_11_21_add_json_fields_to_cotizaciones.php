<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // PASO 2: PRODUCTOS
            $table->json('productos')->nullable()->after('cotizar_segun_indicaciones'); // Array de productos con: nombre_producto, descripcion, fotos, imagen_tela
            $table->json('especificaciones')->nullable()->after('productos'); // Disponibilidad, forma_pago, régimen, se_ha_vendido, última_venta, flete
            
            // PASO 3: LOGO
            $table->json('imagenes')->nullable()->after('especificaciones'); // Array de imágenes (máx 5)
            $table->json('tecnicas')->nullable()->after('imagenes'); // Array de técnicas seleccionadas (BORDADO, DTF, ESTAMPADO, SUBLIMADO)
            $table->text('observaciones_tecnicas')->nullable()->after('tecnicas'); // Observaciones de técnicas
            $table->json('ubicaciones')->nullable()->after('observaciones_tecnicas'); // Ubicaciones por sección (CAMISA, JEAN/SUDADERA, GORRAS)
            $table->json('observaciones_generales')->nullable()->after('ubicaciones'); // Array de observaciones generales
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn([
                'productos',
                'especificaciones',
                'imagenes',
                'tecnicas',
                'observaciones_tecnicas',
                'ubicaciones',
                'observaciones_generales'
            ]);
        });
    }
};
