<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->boolean('is_coperto')->default(false)->after('bar');
        });

        // Migrazione dati: la voce storica "Coperto" diventa la voce flaggata.
        DB::table('menu_items')->where('nome', 'Coperto')->update(['is_coperto' => true]);
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('is_coperto');
        });
    }
};
