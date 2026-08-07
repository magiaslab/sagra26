<?php

use App\Models\Impostazione;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Edizione sagra (anno): contenitore delle serate.
 * Le serate esistenti vengono agganciate all’edizione dell’anno corrente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edizioni', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anno')->unique();
            $table->string('nome', 120)->nullable();
            $table->string('stato', 20)->default('aperta'); // aperta | archiviata
            $table->timestamp('aperta_at')->nullable();
            $table->timestamp('chiusa_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::table('serate', function (Blueprint $table) {
            $table->foreignId('edizione_id')->nullable()->after('id')->constrained('edizioni')->nullOnDelete();
        });

        $anno = (int) (Impostazione::query()->value('intestazione_anno') ?: date('Y'));
        if ($anno < 2000 || $anno > 2100) {
            $anno = (int) date('Y');
        }

        $edizioneId = DB::table('edizioni')->insertGetId([
            'anno' => $anno,
            'nome' => 'Sagra '.$anno,
            'stato' => 'aperta',
            'aperta_at' => now(),
            'chiusa_at' => null,
            'note' => 'Creata in migrazione: serate esistenti agganciate qui.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('serate')->update(['edizione_id' => $edizioneId]);
    }

    public function down(): void
    {
        Schema::table('serate', function (Blueprint $table) {
            $table->dropConstrainedForeignId('edizione_id');
        });
        Schema::dropIfExists('edizioni');
    }
};
