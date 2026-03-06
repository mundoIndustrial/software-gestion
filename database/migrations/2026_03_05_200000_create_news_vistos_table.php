<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_vistos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('news_id')->comment('ID del registro en la tabla news');
            $table->unsignedBigInteger('user_id')->comment('ID del usuario que marcó como visto');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['news_id', 'user_id'], 'news_user_visto_unique');
            $table->index('user_id');
            $table->index('news_id');

            $table->foreign('news_id')
                ->references('id')
                ->on('news')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_vistos');
    }
};
