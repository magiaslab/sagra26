<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE comande MODIFY metodo_pagamento ENUM('contante','pos','misto','omaggio','sospeso') NULL");
        } elseif ($driver === 'sqlite') {
            $this->rebuildSqliteMetodoPagamento();
        }

        Schema::table('comande', function (Blueprint $table) {
            $table->string('autorizzato_da', 80)->nullable();
            $table->string('nominativo', 80)->nullable();
            $table->string('pagamento_note', 255)->nullable();
            $table->timestamp('sospeso_chiuso_at')->nullable();
            $table->boolean('era_sospeso')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('comande', function (Blueprint $table) {
            $table->dropColumn([
                'autorizzato_da',
                'nominativo',
                'pagamento_note',
                'sospeso_chiuso_at',
                'era_sospeso',
            ]);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE comande MODIFY metodo_pagamento ENUM('contante','pos','misto') NULL");
        }
    }

    /**
     * SQLite crea CHECK sull'enum: va ricostruita la tabella per accettare omaggio/sospeso.
     */
    private function rebuildSqliteMetodoPagamento(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('comande_new_omaggio', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('numero_progressivo')->unique();
            $table->foreignId('serata_id')->constrained('serate');
            $table->foreignId('postazione_id')->constrained('postazioni');
            $table->foreignId('punto_cassa_id')->constrained('punti_cassa');
            $table->integer('coperti')->default(0);
            $table->enum('stato', ['aperta', 'stampata', 'annullata'])->default('aperta');
            $table->unsignedInteger('version')->default(1);
            $table->string('metodo_pagamento', 20)->nullable();
            $table->decimal('importo_contante', 8, 2)->nullable();
            $table->decimal('importo_pos', 8, 2)->nullable();
            $table->decimal('totale', 8, 2)->default(0);
            $table->text('motivo_annullo')->nullable();
            $table->string('tavolo', 40)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        $cols = 'id, numero_progressivo, serata_id, postazione_id, punto_cassa_id, coperti, stato, version, metodo_pagamento, importo_contante, importo_pos, totale, motivo_annullo, tavolo, note, created_at, updated_at';
        DB::statement("INSERT INTO comande_new_omaggio ({$cols}) SELECT {$cols} FROM comande");

        Schema::drop('comande');
        Schema::rename('comande_new_omaggio', 'comande');

        Schema::enableForeignKeyConstraints();
    }
};
