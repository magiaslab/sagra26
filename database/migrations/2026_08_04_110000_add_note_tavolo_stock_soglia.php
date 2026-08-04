<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comande', function (Blueprint $table) {
            $table->string('tavolo', 40)->nullable()->after('motivo_annullo');
            $table->string('note', 255)->nullable()->after('tavolo');
        });

        Schema::table('impostazioni', function (Blueprint $table) {
            $table->unsignedInteger('stock_soglia_alert')->default(10)->after('comunicazione_comanda');
        });
    }

    public function down(): void
    {
        Schema::table('comande', function (Blueprint $table) {
            $table->dropColumn(['tavolo', 'note']);
        });

        Schema::table('impostazioni', function (Blueprint $table) {
            $table->dropColumn('stock_soglia_alert');
        });
    }
};
