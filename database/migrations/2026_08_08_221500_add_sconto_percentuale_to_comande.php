<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Percentuale sconto (1–100). 100 = omaggio; 1–99 = sconto sul totale, poi si paga il residuo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('comande', 'sconto_percentuale')) {
            Schema::table('comande', function (Blueprint $table) {
                $table->unsignedTinyInteger('sconto_percentuale')->nullable()->after('pagamento_note');
            });
        }

        // Omaggi esistenti: 100%.
        DB::table('comande')->where('metodo_pagamento', 'omaggio')->whereNull('sconto_percentuale')->update([
            'sconto_percentuale' => 100,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('comande', 'sconto_percentuale')) {
            Schema::table('comande', function (Blueprint $table) {
                $table->dropColumn('sconto_percentuale');
            });
        }
    }
};
