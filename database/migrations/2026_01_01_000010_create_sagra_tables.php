<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorie', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('area_stampa', ['cucina', 'griglia', 'cliente'])->default('cliente');
            $table->integer('ordinamento')->default(0);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorie');
            $table->string('nome');
            $table->decimal('prezzo', 6, 2);
            $table->boolean('attivo')->default(true);
            $table->boolean('piatto_del_giorno')->default(false);
            $table->integer('stock_default')->nullable();
            $table->enum('area_stampa', ['cucina', 'griglia', 'cliente'])->nullable();
            $table->integer('ordinamento')->default(0);
            $table->timestamps();
        });

        Schema::create('postazioni', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
        });

        Schema::create('punti_cassa', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->boolean('attivo')->default(true);
            $table->timestamps();
        });

        Schema::create('postazione_punto_cassa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postazione_id')->constrained('postazioni');
            $table->foreignId('punto_cassa_id')->constrained('punti_cassa');
            $table->date('valido_da');
            $table->timestamps();
        });

        Schema::create('serate', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->enum('stato', ['aperta', 'chiusa'])->default('aperta');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('serata_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serata_id')->constrained('serate');
            $table->foreignId('menu_item_id')->constrained('menu_items');
            $table->integer('stock_iniziale');
            $table->integer('stock_residuo');
            $table->timestamps();
            $table->unique(['serata_id', 'menu_item_id']);
        });

        Schema::create('comande', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('numero_progressivo')->unique();
            $table->foreignId('serata_id')->constrained('serate');
            $table->foreignId('postazione_id')->constrained('postazioni');
            $table->foreignId('punto_cassa_id')->constrained('punti_cassa');
            $table->integer('coperti')->default(0);
            $table->enum('stato', ['aperta', 'stampata', 'annullata'])->default('aperta');
            $table->enum('metodo_pagamento', ['contante', 'pos', 'misto'])->nullable();
            $table->decimal('importo_contante', 8, 2)->nullable();
            $table->decimal('importo_pos', 8, 2)->nullable();
            $table->decimal('totale', 8, 2)->default(0);
            $table->text('motivo_annullo')->nullable();
            $table->timestamps();
        });

        Schema::create('comanda_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained('comande')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained('menu_items');
            $table->integer('quantita');
            $table->decimal('prezzo_unitario', 6, 2);
            $table->integer('qta_scalata')->default(0);
            $table->timestamps();
        });

        Schema::create('chiusure', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serata_id')->constrained('serate');
            $table->foreignId('punto_cassa_id')->constrained('punti_cassa');
            $table->decimal('fondo_iniziale', 8, 2)->default(0);
            $table->integer('n_100')->default(0);
            $table->integer('n_50')->default(0);
            $table->integer('n_20')->default(0);
            $table->integer('n_10')->default(0);
            $table->integer('n_5')->default(0);
            $table->integer('n_2')->default(0);
            $table->integer('n_1')->default(0);
            $table->integer('n_050')->default(0);
            $table->integer('n_020')->default(0);
            $table->integer('n_010')->default(0);
            $table->integer('n_005')->default(0);
            $table->integer('n_002')->default(0);
            $table->integer('n_001')->default(0);
            $table->decimal('contante_contato', 8, 2)->default(0);
            $table->decimal('fondo_trattenuto', 8, 2)->default(0);
            $table->decimal('contante_consegnato', 8, 2)->default(0);
            $table->decimal('totale_pos', 8, 2)->default(0);
            $table->decimal('totale_z', 8, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamp('chiusa_at')->nullable();
            $table->timestamps();
            $table->unique(['serata_id', 'punto_cassa_id']);
        });

        Schema::create('impostazioni', function (Blueprint $table) {
            $table->id();
            $table->string('intestazione_nome')->default('Sagra del Cacciucchetto');
            $table->string('intestazione_anno')->default('2026');
            $table->string('intestazione_sottotitolo')->nullable();
            $table->string('pin_gestione')->default('1234');
            $table->string('chromium_path')->nullable();
            $table->timestamps();
        });

        Schema::create('comanda_numeri', function (Blueprint $table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comanda_numeri');
        Schema::dropIfExists('impostazioni');
        Schema::dropIfExists('chiusure');
        Schema::dropIfExists('comanda_righe');
        Schema::dropIfExists('comande');
        Schema::dropIfExists('serata_stock');
        Schema::dropIfExists('serate');
        Schema::dropIfExists('postazione_punto_cassa');
        Schema::dropIfExists('punti_cassa');
        Schema::dropIfExists('postazioni');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('categorie');
    }
};
