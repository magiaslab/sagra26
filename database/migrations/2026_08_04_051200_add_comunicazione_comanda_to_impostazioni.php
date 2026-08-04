<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('impostazioni', function (Blueprint $table) {
            $table->text('comunicazione_comanda')->nullable()->after('pin_gestione');
        });
    }

    public function down(): void
    {
        Schema::table('impostazioni', function (Blueprint $table) {
            $table->dropColumn('comunicazione_comanda');
        });
    }
};
