<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postazioni', function (Blueprint $table) {
            $table->string('claimed_session_id')->nullable()->after('nome');
            $table->timestamp('claimed_at')->nullable()->after('claimed_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('postazioni', function (Blueprint $table) {
            $table->dropColumn(['claimed_session_id', 'claimed_at']);
        });
    }
};
