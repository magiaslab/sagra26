<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comanda_correzioni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained('comande')->cascadeOnDelete();
            $table->foreignId('postazione_id')->constrained('postazioni');
            $table->json('righe_precedenti');
            $table->decimal('totale_precedente', 8, 2);
            $table->string('motivo')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comanda_correzioni');
    }
};
