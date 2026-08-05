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
     *   fiscale: float,
     *   delta_contante: float,
     *   delta_pos: float,
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
        $fiscale = round((float) $chiusura->totale_z, 2);
        $consegnato = round((float) $chiusura->contante_contato - (float) $chiusura->fondo_trattenuto, 2);

        return [
            'atteso_contante' => $atteso['contante'],
            'atteso_pos' => $atteso['pos'],
            'atteso_totale' => $atteso['totale'],
            'reale_contante' => $realeContante,
            'reale_pos' => $realePos,
            'fiscale' => $fiscale,
            'delta_contante' => round($realeContante - $atteso['contante'], 2),
            'delta_pos' => round($realePos - $atteso['pos'], 2),
            'delta_fiscale' => round($atteso['totale'] - $fiscale, 2),
            'contante_consegnato' => $consegnato,
            'incasso_contante_reale' => $realeContante,
        ];
    }

    /**
     * @return array{contante: float, pos: float, totale: float}
     */
    public function attesoDaComande(Serata $serata, PuntoCassa $puntoCassa): array
    {
        $comande = Comanda::query()
            ->where('serata_id', $serata->id)
            ->where('punto_cassa_id', $puntoCassa->id)
            ->where('stato', 'stampata')
            ->get();

        $contante = 0.0;
        $pos = 0.0;

        foreach ($comande as $comanda) {
            $contante += $comanda->importoContanteEffettivo();
            $pos += $comanda->importoPosEffettivo();
        }

        $contante = round($contante, 2);
        $pos = round($pos, 2);

        return [
            'contante' => $contante,
            'pos' => $pos,
            'totale' => round($contante + $pos, 2),
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
