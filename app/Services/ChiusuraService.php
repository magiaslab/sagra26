<?php

namespace App\Services;

use App\Models\Chiusura;
use App\Models\PuntoCassa;
use App\Models\Serata;
use RuntimeException;

class ChiusuraService
{
    public function __construct(
        private readonly RiconciliazioneService $riconciliazione,
        private readonly SerataService $serate,
    ) {}

    /**
     * @param  array<string, mixed>  $dati
     */
    public function salva(Serata $serata, PuntoCassa $puntoCassa, array $dati): Chiusura
    {
        // Protezione foglio consegna: dati chiusura modificabili solo a serata aperta.
        if (! $serata->isAperta()) {
            throw new RuntimeException(
                'Serata chiusa: il foglio consegna contanti è bloccato. Usa «Riapri per correggere» oppure riapri la serata da Gestione → Serate.'
            );
        }

        $chiusura = Chiusura::query()->firstOrNew([
            'serata_id' => $serata->id,
            'punto_cassa_id' => $puntoCassa->id,
        ]);

        foreach (array_keys(Chiusura::TAGLI) as $campo) {
            $chiusura->{$campo} = (int) ($dati[$campo] ?? 0);
        }

        $pezziFondo = Chiusura::normalizzaPezzi(
            is_array($dati['pezzi_fondo'] ?? null) ? $dati['pezzi_fondo'] : []
        );
        $totaleFondoPezzi = Chiusura::totaleDaPezzi($pezziFondo);

        $chiusura->fondo_iniziale = (float) ($dati['fondo_iniziale'] ?? $chiusura->fondo_iniziale ?? 0);
        $chiusura->pezzi_fondo = $pezziFondo;

        // Se ci sono pezzi fondo, il trattenuto segue il conteggio (fonte di verità).
        if ($totaleFondoPezzi > 0.005 || array_sum($pezziFondo) > 0) {
            $chiusura->fondo_trattenuto = $totaleFondoPezzi;
        } else {
            $chiusura->fondo_trattenuto = (float) ($dati['fondo_trattenuto'] ?? 0);
        }

        $chiusura->totale_pos = (float) ($dati['totale_pos'] ?? 0);
        if (array_key_exists('totale_z_contante', $dati) || array_key_exists('totale_z_pos', $dati)) {
            $chiusura->totale_z_contante = (float) ($dati['totale_z_contante'] ?? 0);
            $chiusura->totale_z_pos = (float) ($dati['totale_z_pos'] ?? 0);
            $chiusura->sincronizzaTotaleZ();
        } else {
            // Compat: un solo totale_z (test / chiamate legacy).
            $chiusura->totale_z = (float) ($dati['totale_z'] ?? 0);
            if ((float) $chiusura->totale_z_contante === 0.0 && (float) $chiusura->totale_z_pos === 0.0) {
                $chiusura->totale_z_contante = (float) $chiusura->totale_z;
                $chiusura->totale_z_pos = 0;
            }
        }
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

    /**
     * Riapre serata (se chiusa) e sblocca la chiusura del punto per correggere i conteggi.
     */
    public function riapriPerCorrezione(Serata $serata, PuntoCassa $puntoCassa): Chiusura
    {
        if (! $serata->isAperta()) {
            $this->serate->riapri($serata->fresh());
            $serata->refresh();
        }

        $chiusura = Chiusura::query()->firstOrCreate(
            [
                'serata_id' => $serata->id,
                'punto_cassa_id' => $puntoCassa->id,
            ],
            [
                'fondo_iniziale' => 0,
                'fondo_trattenuto' => 0,
            ]
        );

        if ($chiusura->chiusa_at !== null) {
            $chiusura->chiusa_at = null;
            $chiusura->save();
        }

        return $chiusura->fresh();
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
