<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Il registratore espone Z contante e Z POS separati: si sommano in totale_z.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chiusure', function (Blueprint $table) {
            $table->decimal('totale_z_contante', 8, 2)->default(0)->after('totale_pos');
            $table->decimal('totale_z_pos', 8, 2)->default(0)->after('totale_z_contante');
        });

        // Chiusure già salvate: metti l’intero Z storico in contante (split sconosciuto).
        DB::table('chiusure')->where('totale_z', '>', 0)->update([
            'totale_z_contante' => DB::raw('totale_z'),
            'totale_z_pos' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('chiusure', function (Blueprint $table) {
            $table->dropColumn(['totale_z_contante', 'totale_z_pos']);
        });
    }
};
