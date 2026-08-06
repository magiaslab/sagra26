<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix: su alcuni DB la migration omaggio/sospeso non ha aggiornato il vincolo
 * su metodo_pagamento (ENUM MySQL/MariaDB o CHECK SQLite).
 * Risultato: "Conferma e stampa" di un sospeso fallisce con errore SQL.
 *
 * Rende metodo_pagamento un VARCHAR(20) libero — niente ENUM/CHECK.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE comande MODIFY metodo_pagamento VARCHAR(20) NULL');

            return;
        }

        if ($driver === 'sqlite') {
            $this->rebuildSqliteMetodoPagamentoSeServe();
        }
    }

    public function down(): void
    {
        // Non ripristiniamo ENUM/CHECK: sarebbe regressivo.
    }

    private function rebuildSqliteMetodoPagamentoSeServe(): void
    {
        $row = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'comande'");
        $sql = (string) ($row->sql ?? '');

        // Già libre (varchar senza check sui valori di pagamento) → niente da fare.
        $haCheckMetodo = (bool) preg_match(
            '/metodo_pagamento[^,)]*check\s*\(\s*["\']?metodo_pagamento/i',
            $sql
        );
        if (! $haCheckMetodo) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::create('comande_fix_metodo', function (Blueprint $table) {
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
            $table->string('autorizzato_da', 80)->nullable();
            $table->string('nominativo', 80)->nullable();
            $table->string('pagamento_note', 255)->nullable();
            $table->timestamp('sospeso_chiuso_at')->nullable();
            $table->boolean('era_sospeso')->default(false);
            $table->timestamps();
        });

        $cols = 'id, numero_progressivo, serata_id, postazione_id, punto_cassa_id, coperti, stato, version, '
            .'metodo_pagamento, importo_contante, importo_pos, totale, motivo_annullo, tavolo, note, '
            .'autorizzato_da, nominativo, pagamento_note, sospeso_chiuso_at, era_sospeso, created_at, updated_at';

        DB::statement("INSERT INTO comande_fix_metodo ({$cols}) SELECT {$cols} FROM comande");

        Schema::drop('comande');
        Schema::rename('comande_fix_metodo', 'comande');

        Schema::enableForeignKeyConstraints();
    }
};
