<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $categorie = DB::table('categorie')->get(['id', 'area_stampa']);
        $items = DB::table('menu_items')->get(['id', 'area_stampa']);

        Schema::table('categorie', function (Blueprint $table) {
            $table->dropColumn('area_stampa');
        });
        Schema::table('categorie', function (Blueprint $table) {
            $table->string('area_stampa', 32)->default('cliente');
        });
        foreach ($categorie as $row) {
            DB::table('categorie')->where('id', $row->id)->update([
                'area_stampa' => $this->normalizeArea($row->area_stampa, false),
            ]);
        }

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('area_stampa');
        });
        Schema::table('menu_items', function (Blueprint $table) {
            $table->string('area_stampa', 32)->nullable();
        });
        foreach ($items as $row) {
            DB::table('menu_items')->where('id', $row->id)->update([
                'area_stampa' => $this->normalizeArea($row->area_stampa, true),
            ]);
        }
    }

    public function down(): void
    {
        $categorie = DB::table('categorie')->get(['id', 'area_stampa']);
        $items = DB::table('menu_items')->get(['id', 'area_stampa']);

        Schema::table('categorie', function (Blueprint $table) {
            $table->dropColumn('area_stampa');
        });
        Schema::table('categorie', function (Blueprint $table) {
            $table->enum('area_stampa', ['cucina', 'griglia', 'cliente'])->default('cliente');
        });
        foreach ($categorie as $row) {
            DB::table('categorie')->where('id', $row->id)->update([
                'area_stampa' => $this->legacyArea($row->area_stampa, false),
            ]);
        }

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('area_stampa');
        });
        Schema::table('menu_items', function (Blueprint $table) {
            $table->enum('area_stampa', ['cucina', 'griglia', 'cliente'])->nullable();
        });
        foreach ($items as $row) {
            DB::table('menu_items')->where('id', $row->id)->update([
                'area_stampa' => $this->legacyArea($row->area_stampa, true),
            ]);
        }
    }

    private function normalizeArea(?string $area, bool $nullable): ?string
    {
        if ($area === null || $area === '') {
            return $nullable ? null : 'cliente';
        }

        return match ($area) {
            'cucina', 'cucina_1' => 'cucina_1',
            'cucina_2' => 'cucina_2',
            'griglia' => 'griglia',
            default => 'cliente',
        };
    }

    private function legacyArea(?string $area, bool $nullable): ?string
    {
        if ($area === null || $area === '') {
            return $nullable ? null : 'cliente';
        }

        return match ($area) {
            'cucina_1', 'cucina_2', 'cucina' => 'cucina',
            'griglia' => 'griglia',
            default => 'cliente',
        };
    }
};
