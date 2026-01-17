<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->longText('descripcion')->nullable();
            $table->json('tallas_disponibles')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Foreign key
            $table->foreign('categoria_id')
                ->references('id')
                ->on('epp_categorias')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epps');
    }
};
