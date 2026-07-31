<?php

namespace App\Services;

use App\Models\Chiusura;
use App\Models\Impostazione;
use App\Models\PuntoCassa;
use App\Models\Serata;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class ChiusuraService
{
    public function __construct(
        private readonly RiconciliazioneService $riconciliazione,
    ) {}

    /**
     * @param  array<string, mixed>  $dati
     */
    public function salva(Serata $serata, PuntoCassa $puntoCassa, array $dati): Chiusura
    {
        $chiusura = Chiusura::query()->firstOrNew([
            'serata_id' => $serata->id,
            'punto_cassa_id' => $puntoCassa->id,
        ]);

        foreach (array_keys(Chiusura::TAGLI) as $campo) {
            $chiusura->{$campo} = (int) ($dati[$campo] ?? 0);
        }

        $chiusura->fondo_iniziale = (float) ($dati['fondo_iniziale'] ?? $chiusura->fondo_iniziale ?? 0);
        $chiusura->fondo_trattenuto = (float) ($dati['fondo_trattenuto'] ?? 0);
        $chiusura->totale_pos = (float) ($dati['totale_pos'] ?? 0);
        $chiusura->totale_z = (float) ($dati['totale_z'] ?? 0);
        $chiusura->note = $dati['note'] ?? null;
        $chiusura->contante_contato = $chiusura->calcolaContanteContato();
        $chiusura->contante_consegnato = round(
            (float) $chiusura->contante_contato - (float) $chiusura->fondo_trattenuto,
            2
        );
        $chiusura->chiusa_at = now();
        $chiusura->save();

        return $chiusura;
    }

    public function riconciliazione(Serata $serata, PuntoCassa $puntoCassa): array
    {
        $chiusura = Chiusura::query()
            ->where('serata_id', $serata->id)
            ->where('punto_cassa_id', $puntoCassa->id)
            ->firstOrFail();

        return $this->riconciliazione->calcola($serata, $puntoCassa, $chiusura);
    }
}
