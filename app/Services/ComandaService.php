<?php

namespace App\Services;

use App\Exceptions\ComandaConflittoException;
use App\Models\Comanda;
use App\Models\ComandaCorrezione;
use App\Models\ComandaRiga;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\Serata;
use App\Models\SerataStock;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ComandaService
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    /**
     * Allocates the next global progressive number atomically.
     */
    public function nextNumero(): int
    {
        return (int) DB::table('comanda_numeri')->insertGetId([]);
    }

    /**
     * @param  array<int, array{menu_item_id: int, quantita: int}>  $righe
     * @param  int  $coperti  Ignorato: ricalcolato server-side dalle voci is_coperto (compat API client).
     */
    public function confermaEStampa(
        Serata $serata,
        Postazione $postazione,
        array $righe,
        int $coperti,
        string $metodoPagamento,
        ?float $importoContante = null,
        ?float $importoPos = null,
        ?Comanda $esistente = null,
        ?string $motivo = null,
        ?int $versionAttesa = null,
        ?string $tavolo = null,
        ?string $note = null,
        ?string $autorizzatoDa = null,
        ?string $nominativo = null,
        ?string $pagamentoNote = null,
    ): Comanda {
        if (! $serata->isAperta()) {
            throw new RuntimeException('Nessuna serata aperta.');
        }

        if (! in_array($metodoPagamento, ['contante', 'pos', 'misto', 'omaggio', 'sospeso'], true)) {
            throw new RuntimeException('Metodo di pagamento non valido.');
        }

        if (in_array($metodoPagamento, ['omaggio', 'sospeso'], true)) {
            if ($autorizzatoDa === null || trim($autorizzatoDa) === '') {
                throw new RuntimeException('Indica chi ha autorizzato.');
            }
            if ($nominativo === null || trim($nominativo) === '') {
                throw new RuntimeException(
                    $metodoPagamento === 'omaggio'
                        ? 'Indica il nome ospite.'
                        : 'Indica il nominativo del sospeso.'
                );
            }
        }

        $puntoCassa = $postazione->puntoCassaAttivo($serata->data->toDateString());
        if (! $puntoCassa) {
            throw new RuntimeException(
                'Questa postazione non è ancora collegata al cassetto — chiedi a chi gestisce le Impostazioni di completare il collegamento.'
            );
        }

        $righeNormalizzate = $this->normalizzaRighe($righe);
        if ($righeNormalizzate === []) {
            throw new RuntimeException('Comanda vuota.');
        }

        // Coperti: ricalcolo server da voci is_coperto (il valore client è ignorato).
        $coperti = $this->calcolaCoperti($righeNormalizzate);

        return DB::transaction(function () use (
            $serata,
            $postazione,
            $puntoCassa,
            $righeNormalizzate,
            $coperti,
            $metodoPagamento,
            $importoContante,
            $importoPos,
            $esistente,
            $motivo,
            $versionAttesa,
            $tavolo,
            $note,
            $autorizzatoDa,
            $nominativo,
            $pagamentoNote,
        ) {
            if ($esistente) {
                $comanda = Comanda::query()->with('righe.menuItem')->lockForUpdate()->findOrFail($esistente->id);
                if ($comanda->isAnnullata()) {
                    throw new RuntimeException('Comanda annullata, non modificabile.');
                }

                // Impedisce correzioni cross-serata: lo stock e i totali restano sulla serata giusta.
                if ((int) $comanda->serata_id !== (int) $serata->id) {
                    throw new RuntimeException('Comanda non appartiene alla serata corrente, impossibile correggerla.');
                }

                if ($versionAttesa !== null && (int) $comanda->version !== (int) $versionAttesa) {
                    throw new ComandaConflittoException(
                        'Questa comanda è stata modificata da un\'altra postazione nel frattempo. Ricarica e riprova.'
                    );
                }

                // Snapshot pagamento prima della correzione: Contante/POS scelti
                // dal cassiere valgono solo per la differenza, non per tutto il totale
                // (salvo omaggio/sospeso → riassegnazione intera).
                $pagamentoPrecedente = [
                    'totale' => (float) $comanda->totale,
                    'contante' => $comanda->importoContanteEffettivo(),
                    'pos' => $comanda->importoPosEffettivo(),
                    'metodo' => (string) $comanda->metodo_pagamento,
                ];

                // Prezzi già pagati: in correzione non si ricalcolano dal menù attuale
                // (altrimenti togliere un piatto da 10€ può restituire 9€ se un altro prezzo è cambiato).
                $prezziStorici = [];
                foreach ($comanda->righe as $rigaEsistente) {
                    $prezziStorici[(int) $rigaEsistente->menu_item_id] = (float) $rigaEsistente->prezzo_unitario;
                }

                ComandaCorrezione::query()->create([
                    'comanda_id' => $comanda->id,
                    'postazione_id' => $postazione->id,
                    'righe_precedenti' => $comanda->righe->map(fn ($r) => [
                        'menu_item_id' => $r->menu_item_id,
                        'nome' => $r->menuItem->nome,
                        'quantita' => $r->quantita,
                        'prezzo_unitario' => (float) $r->prezzo_unitario,
                    ])->all(),
                    'totale_precedente' => $comanda->totale,
                    'motivo' => $motivo,
                ]);

                $this->applicaDeltaStock($serata, $comanda, $righeNormalizzate);
                $comanda->righe()->delete();
            } else {
                // Stock PRIMA del numero: nextNumero() usa autoincrement su comanda_numeri
                // che non torna indietro al rollback → altrimenti bruceremmo progressivi.
                $this->applicaDeltaStock($serata, null, $righeNormalizzate);

                $comanda = new Comanda([
                    'numero_progressivo' => $this->nextNumero(),
                    'serata_id' => $serata->id,
                    'postazione_id' => $postazione->id,
                    'punto_cassa_id' => $puntoCassa->id,
                    'version' => 1,
                ]);
                $pagamentoPrecedente = null;
                $prezziStorici = [];
            }

            $totale = 0.0;
            $comanda->coperti = $coperti;
            $comanda->stato = 'stampata';
            $comanda->totale = 0;
            if ($esistente) {
                $comanda->version = (int) $comanda->version + 1;
            }
            // Metodo/importi definitivi dopo il ricalcolo totale (correzione: delta o riassegnazione).
            $comanda->metodo_pagamento = $metodoPagamento;
            $comanda->importo_contante = null;
            $comanda->importo_pos = null;
            $comanda->save();

            foreach ($righeNormalizzate as $riga) {
                $item = MenuItem::query()->findOrFail($riga['menu_item_id']);
                $prezzo = array_key_exists((int) $item->id, $prezziStorici)
                    ? $prezziStorici[(int) $item->id]
                    : (float) $item->prezzo;
                $sub = round($riga['quantita'] * $prezzo, 2);
                $totale += $sub;

                ComandaRiga::query()->create([
                    'comanda_id' => $comanda->id,
                    'menu_item_id' => $item->id,
                    'quantita' => $riga['quantita'],
                    'prezzo_unitario' => $prezzo,
                    'bar' => (bool) $item->bar,
                    'qta_scalata' => $item->stock_default !== null ? $riga['quantita'] : 0,
                ]);
            }

            $totale = round($totale, 2);
            $comanda->totale = $totale;
            $comanda->tavolo = ($tavolo !== null && trim($tavolo) !== '')
                ? mb_substr(trim($tavolo), 0, 40)
                : null;
            $comanda->note = ($note !== null && trim($note) !== '')
                ? mb_substr(trim($note), 0, 255)
                : null;

            if ($pagamentoPrecedente !== null) {
                $pagamento = $this->risolviPagamentoCorrezione(
                    $pagamentoPrecedente,
                    $totale,
                    $metodoPagamento,
                    $importoContante,
                    $importoPos,
                );
                $comanda->metodo_pagamento = $pagamento['metodo'];
                $comanda->importo_contante = $pagamento['importo_contante'];
                $comanda->importo_pos = $pagamento['importo_pos'];
            } elseif ($metodoPagamento === 'misto') {
                $pagamento = $this->pagamentoMisto($totale, $importoContante, $importoPos);
                $comanda->metodo_pagamento = $pagamento['metodo'];
                $comanda->importo_contante = $pagamento['importo_contante'];
                $comanda->importo_pos = $pagamento['importo_pos'];
            } else {
                $comanda->metodo_pagamento = $metodoPagamento;
                $comanda->importo_contante = null;
                $comanda->importo_pos = null;
            }

            $this->applicaMetadatiPagamentoSpeciale(
                $comanda,
                $pagamentoPrecedente['metodo'] ?? null,
                $autorizzatoDa,
                $nominativo,
                $pagamentoNote,
            );

            $comanda->save();

            return $comanda->load(['righe.menuItem.categoria', 'postazione', 'puntoCassa', 'serata']);
        });
    }

    /**
     * @param  array{totale: float, contante: float, pos: float, metodo?: string}  $precedente
     * @return array{metodo: string, importo_contante: ?float, importo_pos: ?float}
     */
    private function risolviPagamentoCorrezione(
        array $precedente,
        float $nuovoTotale,
        string $metodoScelto,
        ?float $importoContante = null,
        ?float $importoPos = null,
    ): array {
        $metodoPrec = $precedente['metodo'] ?? null;
        $daNonIncassato = in_array($metodoPrec, ['sospeso', 'omaggio'], true);

        // Omaggio / sospeso: riassegnano sempre l'intero totale (niente delta parziale).
        if (in_array($metodoScelto, ['omaggio', 'sospeso'], true)) {
            return [
                'metodo' => $metodoScelto,
                'importo_contante' => null,
                'importo_pos' => null,
            ];
        }

        // Chiusura sospeso o conversione da omaggio: si paga (o restituisce) tutto il nuovo totale.
        if ($daNonIncassato) {
            if ($metodoScelto === 'misto') {
                return $this->pagamentoMisto($nuovoTotale, $importoContante, $importoPos);
            }
            if ($metodoScelto === 'contante') {
                return $this->normalizzaMetodoImporti($nuovoTotale, 0.0);
            }
            if ($metodoScelto === 'pos') {
                return $this->normalizzaMetodoImporti(0.0, $nuovoTotale);
            }
            throw new RuntimeException('Scegli Contante, POS, Misto o Omaggio.');
        }

        $delta = round($nuovoTotale - $precedente['totale'], 2);

        if (abs($delta) < 0.005) {
            if (! in_array($metodoScelto, ['contante', 'pos'], true)) {
                throw new RuntimeException('Per correggere il pagamento scegli Contante, POS, Omaggio o Sospeso.');
            }

            return $metodoScelto === 'contante'
                ? $this->normalizzaMetodoImporti($nuovoTotale, 0.0)
                : $this->normalizzaMetodoImporti(0.0, $nuovoTotale);
        }

        if (! in_array($metodoScelto, ['contante', 'pos'], true)) {
            throw new RuntimeException('Per la differenza scegli Contante o POS (oppure Omaggio/Sospeso per riassegnare tutto).');
        }

        $contante = round($precedente['contante'], 2);
        $pos = round($precedente['pos'], 2);
        if ($metodoScelto === 'contante') {
            $contante = round($contante + $delta, 2);
        } else {
            $pos = round($pos + $delta, 2);
        }

        return $this->normalizzaMetodoImporti($contante, $pos);
    }

    /**
     * @return array{metodo: string, importo_contante: float, importo_pos: float}
     */
    private function pagamentoMisto(float $totale, ?float $importoContante, ?float $importoPos): array
    {
        $c = round((float) $importoContante, 2);
        $p = round((float) $importoPos, 2);
        if ($c < 0 || $p < 0 || abs(($c + $p) - $totale) > 0.01) {
            throw new RuntimeException(
                'Pagamento misto: contante + POS devono eguagliare il totale ('.$totale.' €).'
            );
        }

        return [
            'metodo' => 'misto',
            'importo_contante' => $c,
            'importo_pos' => $p,
        ];
    }

    private function applicaMetadatiPagamentoSpeciale(
        Comanda $comanda,
        ?string $metodoPrecedente,
        ?string $autorizzatoDa,
        ?string $nominativo,
        ?string $pagamentoNote,
    ): void {
        $metodo = (string) $comanda->metodo_pagamento;

        if (in_array($metodo, ['omaggio', 'sospeso'], true)) {
            $comanda->autorizzato_da = mb_substr(trim((string) $autorizzatoDa), 0, 80);
            $comanda->nominativo = mb_substr(trim((string) $nominativo), 0, 80);
            $comanda->pagamento_note = ($pagamentoNote !== null && trim($pagamentoNote) !== '')
                ? mb_substr(trim($pagamentoNote), 0, 255)
                : null;
        }

        if ($metodo === 'sospeso') {
            $comanda->era_sospeso = true;
            $comanda->sospeso_chiuso_at = null;
        }

        if ($metodoPrecedente === 'sospeso' && $metodo !== 'sospeso') {
            $comanda->era_sospeso = true;
            $comanda->sospeso_chiuso_at = now();
        }
    }

    /**
     * @return array{metodo: string, importo_contante: ?float, importo_pos: ?float}
     */
    private function normalizzaMetodoImporti(float $contante, float $pos): array
    {
        if (abs($contante) < 0.005) {
            $contante = 0.0;
        }
        if (abs($pos) < 0.005) {
            $pos = 0.0;
        }

        if ($contante !== 0.0 && $pos !== 0.0) {
            return [
                'metodo' => 'misto',
                'importo_contante' => $contante,
                'importo_pos' => $pos,
            ];
        }

        if ($pos !== 0.0) {
            return [
                'metodo' => 'pos',
                'importo_contante' => null,
                'importo_pos' => null,
            ];
        }

        return [
            'metodo' => 'contante',
            'importo_contante' => null,
            'importo_pos' => null,
        ];
    }

    public function annulla(Comanda $comanda, string $motivo): Comanda
    {
        return DB::transaction(function () use ($comanda, $motivo) {
            $comanda = Comanda::query()->lockForUpdate()->findOrFail($comanda->id);
            if ($comanda->isAnnullata()) {
                return $comanda;
            }

            // Vincolo su Serata::corrente() (non solo "serata della comanda ancora aperta"):
            // dopo la chiusura corrente() è null → annullo bloccato anche senza nuova serata;
            // con una nuova serata aperta, serata_id diverso → stesso blocco.
            // Così non si alterano stock/incassi di turni già cassati.
            $serataCorrente = Serata::corrente();
            if (! $serataCorrente || (int) $comanda->serata_id !== (int) $serataCorrente->id) {
                throw new RuntimeException('Comanda non appartiene alla serata corrente, impossibile annullarla.');
            }

            foreach ($comanda->righe as $riga) {
                if ($riga->qta_scalata > 0) {
                    $this->stock->restituisci(
                        $comanda->serata_id,
                        $riga->menu_item_id,
                        $riga->qta_scalata,
                    );
                    $riga->qta_scalata = 0;
                    $riga->save();
                }
            }

            $comanda->stato = 'annullata';
            $comanda->motivo_annullo = $motivo;
            $comanda->save();

            return $comanda;
        });
    }

    /**
     * @param  array<int, array{menu_item_id: int, quantita: int}>  $righe
     * @return array<int, array{menu_item_id: int, quantita: int}>
     */
    private function normalizzaRighe(array $righe): array
    {
        $out = [];
        foreach ($righe as $riga) {
            $qty = (int) ($riga['quantita'] ?? 0);
            $id = (int) ($riga['menu_item_id'] ?? 0);
            if ($qty > 0 && $id > 0) {
                $out[] = ['menu_item_id' => $id, 'quantita' => $qty];
            }
        }

        return $out;
    }

    /**
     * Somma le quantità delle voci menù con flag is_coperto.
     *
     * @param  array<int, array{menu_item_id: int, quantita: int}>  $righeNormalizzate
     */
    private function calcolaCoperti(array $righeNormalizzate): int
    {
        $ids = array_values(array_unique(array_map(
            fn (array $r) => $r['menu_item_id'],
            $righeNormalizzate,
        )));

        if ($ids === []) {
            return 0;
        }

        $copertoIds = MenuItem::query()
            ->whereIn('id', $ids)
            ->where('is_coperto', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $coperti = 0;
        foreach ($righeNormalizzate as $riga) {
            if (in_array($riga['menu_item_id'], $copertoIds, true)) {
                $coperti += $riga['quantita'];
            }
        }

        return $coperti;
    }

    /**
     * @param  array<int, array{menu_item_id: int, quantita: int}>  $nuove
     */
    private function applicaDeltaStock(Serata $serata, ?Comanda $esistente, array $nuove): void
    {
        $precedenti = [];
        if ($esistente) {
            foreach ($esistente->righe as $riga) {
                $precedenti[$riga->menu_item_id] = $riga->qta_scalata;
            }
        }

        $nuoveMap = [];
        foreach ($nuove as $riga) {
            $item = MenuItem::query()->findOrFail($riga['menu_item_id']);
            if ($item->stock_default === null) {
                continue;
            }
            $nuoveMap[$item->id] = $riga['quantita'];
        }

        $ids = array_unique(array_merge(array_keys($precedenti), array_keys($nuoveMap)));
        foreach ($ids as $menuItemId) {
            $vecchia = $precedenti[$menuItemId] ?? 0;
            $nuova = $nuoveMap[$menuItemId] ?? 0;
            $delta = $nuova - $vecchia;
            if ($delta === 0) {
                continue;
            }
            if ($delta > 0) {
                $this->stock->scala($serata->id, $menuItemId, $delta);
            } else {
                $this->stock->restituisci($serata->id, $menuItemId, abs($delta));
            }
        }
    }
}
