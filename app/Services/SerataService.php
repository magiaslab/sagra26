<?php

namespace App\Services;

use App\Models\Chiusura;
use App\Models\Edizione;
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
        private readonly EdizioneService $edizioni,
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

        $edizione = Edizione::corrente() ?? $this->edizioni->assicuratiCorrente();
        if (! $edizione) {
            throw new RuntimeException('Nessuna edizione aperta. Apri o riapri un’edizione da Gestione → Edizione.');
        }

        return DB::transaction(function () use ($data, $note, $stockOverrides, $fondiIniziali, $edizione) {
            $serata = Serata::query()->create([
                'edizione_id' => $edizione->id,
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
        if (! $serata->isAperta()) {
            throw new RuntimeException('La serata non è aperta.');
        }

        $serata->stato = 'chiusa';
        $serata->save();

        return $serata;
    }

    /**
     * Riapre una serata chiusa (per correzioni / ristampa).
     * Bloccata se ne esiste già un'altra aperta.
     */
    public function riapri(Serata $serata): Serata
    {
        if ($serata->isAperta()) {
            throw new RuntimeException('La serata è già aperta.');
        }

        if (Serata::corrente()) {
            throw new RuntimeException('Esiste già una serata aperta: chiudila prima di riaprirne un\'altra.');
        }

        $this->assertEdizioneOperativa($serata);

        $serata->stato = 'aperta';
        $serata->save();

        return $serata;
    }

    /**
     * Elimina una serata e tutti i dati collegati (comande, stock, chiusure).
     * Consentita solo se chiusa (o se non è l'unica aperta senza comande — qui: solo chiuse).
     */
    public function elimina(Serata $serata): void
    {
        if ($serata->isAperta()) {
            throw new RuntimeException('Chiudi la serata prima di eliminarla.');
        }

        $this->assertEdizioneOperativa($serata);

        DB::transaction(function () use ($serata) {
            // comanda_righe / correzioni: cascadeOnDelete su comande
            $serata->comande()->delete();
            $serata->stocks()->delete();
            $serata->chiusure()->delete();
            $serata->delete();
        });
    }

    private function assertEdizioneOperativa(Serata $serata): void
    {
        if (! $serata->edizione_id) {
            return;
        }

        $edizione = Edizione::query()->find($serata->edizione_id);
        if ($edizione && ! $edizione->isAperta()) {
            throw new RuntimeException('L’edizione di questa serata è archiviata. Riapri l’edizione da Gestione → Edizione.');
        }
    }
}
