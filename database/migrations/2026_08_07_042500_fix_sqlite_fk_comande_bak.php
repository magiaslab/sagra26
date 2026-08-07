<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Su SQLite, dopo rebuild della tabella comande, le FK figlie possono restare
 * agganciate a "comande_bak" (tabella non più esistente).
 * Sintomo: insert su comanda_righe → "no such table: main.comande_bak"
 * (es. Conferma e stampa di una comanda sospesa).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        $this->riparaFkSePuntaABak('comanda_righe');
        $this->riparaFkSePuntaABak('comanda_correzioni');
    }

    public function down(): void
    {
        // Irreversibile: lo stato comande_bak è corrotto.
    }

    private function riparaFkSePuntaABak(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $row = DB::selectOne(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
            [$table]
        );
        $sql = (string) ($row->sql ?? '');
        if ($sql === '' || ! preg_match('/references\s*["\']?comande_bak["\']?/i', $sql)) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        match ($table) {
            'comanda_righe' => $this->rebuildComandaRighe(),
            'comanda_correzioni' => $this->rebuildComandaCorrezioni(),
            default => null,
        };

        Schema::enableForeignKeyConstraints();
    }

    private function rebuildComandaRighe(): void
    {
        Schema::create('comanda_righe_fix_fk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained('comande')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained('menu_items');
            $table->integer('quantita');
            $table->decimal('prezzo_unitario', 6, 2);
            $table->integer('qta_scalata')->default(0);
            $table->boolean('bar')->default(false);
            $table->timestamps();
        });

        $cols = 'id, comanda_id, menu_item_id, quantita, prezzo_unitario, qta_scalata, bar, created_at, updated_at';
        DB::statement("INSERT INTO comanda_righe_fix_fk ({$cols}) SELECT {$cols} FROM comanda_righe");

        Schema::drop('comanda_righe');
        Schema::rename('comanda_righe_fix_fk', 'comanda_righe');
    }

    private function rebuildComandaCorrezioni(): void
    {
        Schema::create('comanda_correzioni_fix_fk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained('comande')->cascadeOnDelete();
            $table->foreignId('postazione_id')->constrained('postazioni');
            $table->json('righe_precedenti');
            $table->decimal('totale_precedente', 8, 2);
            $table->string('motivo')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        $cols = 'id, comanda_id, postazione_id, righe_precedenti, totale_precedente, motivo, created_at';
        DB::statement("INSERT INTO comanda_correzioni_fix_fk ({$cols}) SELECT {$cols} FROM comanda_correzioni");

        Schema::drop('comanda_correzioni');
        Schema::rename('comanda_correzioni_fix_fk', 'comanda_correzioni');
    }
};
