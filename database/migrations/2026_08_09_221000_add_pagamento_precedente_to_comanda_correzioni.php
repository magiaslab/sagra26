<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot del pagamento prima della correzione (metodo, canali, sconto)
 * per lo storico "comande modificate".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('comanda_correzioni', 'pagamento_precedente')) {
            Schema::table('comanda_correzioni', function (Blueprint $table) {
                $table->json('pagamento_precedente')->nullable()->after('totale_precedente');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('comanda_correzioni', 'pagamento_precedente')) {
            Schema::table('comanda_correzioni', function (Blueprint $table) {
                $table->dropColumn('pagamento_precedente');
            });
        }
    }
};
