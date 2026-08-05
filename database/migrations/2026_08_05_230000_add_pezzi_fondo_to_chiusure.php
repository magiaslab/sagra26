<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chiusure', function (Blueprint $table) {
            $table->json('pezzi_fondo')->nullable()->after('fondo_trattenuto');
        });
    }

    public function down(): void
    {
        Schema::table('chiusure', function (Blueprint $table) {
            $table->dropColumn('pezzi_fondo');
        });
    }
};
