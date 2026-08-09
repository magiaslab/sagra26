<?php

namespace App\Services;

use App\Models\Chiusura;
use App\Models\Comanda;
use App\Models\PuntoCassa;
use App\Models\Serata;

class RiconciliazioneService
{
    /**
     * @return array{
     *   atteso_contante: float,
     *   atteso_pos: float,
     *   atteso_totale: float,
     *   reale_contante: float,
     *   reale_pos: float,
     *   fiscale_contante: float,
     *   fiscale_pos: float,
     *   fiscale: float,
     *   delta_contante: float,
     *   delta_pos: float,
     *   delta_fiscale_contante: float,
     *   delta_fiscale_pos: float,
     *   delta_fiscale: float,
     *   contante_consegnato: float,
     *   incasso_contante_reale: float
     * }
     */
    public function calcola(Serata $serata, PuntoCassa $puntoCassa, Chiusura $chiusura): array
    {
        $atteso = $this->attesoDaComande($serata, $puntoCassa);

        $realeContante = round((float) $chiusura->contante_contato - (float) $chiusura->fondo_iniziale, 2);
        $realePos = round((float) $chiusura->totale_pos, 2);
        $fiscaleContante = round((float) ($chiusura->totale_z_contante ?? 0), 2);
        $fiscalePos = round((float) ($chiusura->totale_z_pos ?? 0), 2);
        // Compat: chiusure legacy senza split → usa totale_z come unico fiscale.
        if ($fiscaleContante === 0.0 && $fiscalePos === 0.0 && (float) $chiusura->totale_z !== 0.0) {
            $fiscaleContante = round((float) $chiusura->totale_z, 2);
        }
        $fiscale = round($fiscaleContante + $fiscalePos, 2);
        $consegnato = round((float) $chiusura->contante_contato - (float) $chiusura->fondo_trattenuto, 2);

        return [
            'atteso_contante' => $atteso['contante'],
            'atteso_pos' => $atteso['pos'],
            'atteso_totale' => $atteso['totale'],
            'atteso_dettaglio' => [
                'n_comande' => $atteso['n_comande'],
                'n_omaggi' => $atteso['n_omaggi'],
                'omaggi_valore' => $atteso['omaggi_valore'],
                'n_sospesi_aperti' => $atteso['n_sospesi_aperti'],
                'sospesi_valore' => $atteso['sospesi_valore'],
                'n_sconti' => $atteso['n_sconti'],
                'sconti_valore' => $atteso['sconti_valore'],
                'n_corrette' => $atteso['n_corrette'],
                'n_miste' => $atteso['n_miste'],
            ],
            'reale_contante' => $realeContante,
            'reale_pos' => $realePos,
            'fiscale_contante' => $fiscaleContante,
            'fiscale_pos' => $fiscalePos,
            'fiscale' => $fiscale,
            'delta_contante' => round($realeContante - $atteso['contante'], 2),
            'delta_pos' => round($realePos - $atteso['pos'], 2),
            'delta_fiscale_contante' => round($atteso['contante'] - $fiscaleContante, 2),
            'delta_fiscale_pos' => round($atteso['pos'] - $fiscalePos, 2),
            'delta_fiscale' => round($atteso['totale'] - $fiscale, 2),
            'contante_consegnato' => $consegnato,
            'incasso_contante_reale' => $realeContante,
        ];
    }

    /**
     * @return array{
     *   contante: float,
     *   pos: float,
     *   totale: float,
     *   n_comande: int,
     *   n_omaggi: int,
     *   omaggi_valore: float,
     *   n_sospesi_aperti: int,
     *   sospesi_valore: float,
     *   n_sconti: int,
     *   sconti_valore: float,
     *   n_corrette: int,
     *   n_miste: int
     * }
     */
    public function attesoDaComande(Serata $serata, PuntoCassa $puntoCassa): array
    {
        $comande = Comanda::query()
            ->withCount('correzioni')
            ->where('serata_id', $serata->id)
            ->where('punto_cassa_id', $puntoCassa->id)
            ->where('stato', 'stampata')
            ->get();

        $contante = 0.0;
        $pos = 0.0;
        $omaggiValore = 0.0;
        $sospesiValore = 0.0;
        $scontiValore = 0.0;
        $nOmaggi = 0;
        $nSospesi = 0;
        $nSconti = 0;
        $nCorrette = 0;
        $nMiste = 0;

        foreach ($comande as $comanda) {
            $contante += $comanda->importoContanteEffettivo();
            $pos += $comanda->importoPosEffettivo();

            if ($comanda->isOmaggio()) {
                $nOmaggi++;
                $omaggiValore += (float) $comanda->totale;
            } elseif ($comanda->isSospesoAperto()) {
                $nSospesi++;
                $sospesiValore += (float) $comanda->totale;
            } elseif ($comanda->isScontoParziale()) {
                $nSconti++;
                $scontiValore += $comanda->importoSconto();
            }

            if ((int) $comanda->correzioni_count > 0) {
                $nCorrette++;
            }
            if ($comanda->metodo_pagamento === 'misto') {
                $nMiste++;
            }
        }

        $contante = round($contante, 2);
        $pos = round($pos, 2);

        return [
            'contante' => $contante,
            'pos' => $pos,
            'totale' => round($contante + $pos, 2),
            'n_comande' => $comande->count(),
            'n_omaggi' => $nOmaggi,
            'omaggi_valore' => round($omaggiValore, 2),
            'n_sospesi_aperti' => $nSospesi,
            'sospesi_valore' => round($sospesiValore, 2),
            'n_sconti' => $nSconti,
            'sconti_valore' => round($scontiValore, 2),
            'n_corrette' => $nCorrette,
            'n_miste' => $nMiste,
        ];
    }

    public function fondoInizialeSuggerito(PuntoCassa $puntoCassa): ?float
    {
        $precedente = $this->chiusuraPrecedenteCompletata($puntoCassa);

        return $precedente ? (float) $precedente->fondo_trattenuto : null;
    }

    /**
     * @return array{importo: float, pezzi: array<string, int>, descrizione: string}|null
     */
    public function fondoPrecedenteDettaglio(PuntoCassa $puntoCassa): ?array
    {
        $precedente = $this->chiusuraPrecedenteCompletata($puntoCassa);
        if (! $precedente) {
            return null;
        }

        $pezzi = $precedente->pezziFondoNormalizzati();

        return [
            'importo' => (float) $precedente->fondo_trattenuto,
            'pezzi' => $pezzi,
            'descrizione' => Chiusura::descrizionePezzi($pezzi),
        ];
    }

    private function chiusuraPrecedenteCompletata(PuntoCassa $puntoCassa): ?Chiusura
    {
        return Chiusura::query()
            ->where('punto_cassa_id', $puntoCassa->id)
            ->whereNotNull('chiusa_at')
            ->orderByDesc('chiusa_at')
            ->first();
    }
}
