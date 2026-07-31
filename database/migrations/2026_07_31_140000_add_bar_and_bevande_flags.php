<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->boolean('bar')->default(false)->after('piatto_del_giorno');
        });

        Schema::table('comanda_righe', function (Blueprint $table) {
            $table->boolean('bar')->default(false)->after('qta_scalata');
        });

        Schema::table('categorie', function (Blueprint $table) {
            $table->boolean('is_bevande')->default(false)->after('area_stampa');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('bar');
        });

        Schema::table('comanda_righe', function (Blueprint $table) {
            $table->dropColumn('bar');
        });

        Schema::table('categorie', function (Blueprint $table) {
            $table->dropColumn('is_bevande');
        });
    }
};
