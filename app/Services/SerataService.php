<?php

namespace App\Services;

use App\Models\Chiusura;
use App\Models\MenuItem;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Models\SerataStock;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SerataService
{
    public function __construct(
        private readonly RiconciliazioneService $riconciliazione,
    ) {}

    /**
     * @param  array<int, int>  $stockOverrides  menu_item_id => stock_iniziale
     * @param  array<int, float>  $fondiIniziali  punto_cassa_id => fondo_iniziale
     */
    public function apri(string $data, ?string $note = null, array $stockOverrides = [], array $fondiIniziali = []): Serata
    {
        if (Serata::corrente()) {
            throw new RuntimeException('Esiste già una serata aperta.');
        }

        return DB::transaction(function () use ($data, $note, $stockOverrides, $fondiIniziali) {
            $serata = Serata::query()->create([
                'data' => $data,
                'stato' => 'aperta',
                'note' => $note,
            ]);

            $limitati = MenuItem::query()
                ->where('attivo', true)
                ->whereNotNull('stock_default')
                ->get();

            foreach ($limitati as $item) {
                $iniziale = $stockOverrides[$item->id] ?? (int) $item->stock_default;
                SerataStock::query()->create([
                    'serata_id' => $serata->id,
                    'menu_item_id' => $item->id,
                    'stock_iniziale' => $iniziale,
                    'stock_residuo' => $iniziale,
                ]);
            }

            $punti = PuntoCassa::query()->where('attivo', true)->get();
            foreach ($punti as $punto) {
                $fondo = $fondiIniziali[$punto->id]
                    ?? $this->riconciliazione->fondoInizialeSuggerito($punto)
                    ?? 0;

                Chiusura::query()->create([
                    'serata_id' => $serata->id,
                    'punto_cassa_id' => $punto->id,
                    'fondo_iniziale' => $fondo,
                ]);
            }

            return $serata;
        });
    }

    public function chiudi(Serata $serata): Serata
    {
        $serata->stato = 'chiusa';
        $serata->save();

        return $serata;
    }
}
