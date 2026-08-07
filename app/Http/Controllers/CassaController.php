<?php

namespace App\Http\Controllers;

use App\Exceptions\ComandaConflittoException;
use App\Models\Comanda;
use App\Models\Impostazione;
use App\Models\MenuItem;
use App\Models\Postazione;
use App\Models\Serata;
use App\Services\ComandaService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class CassaController extends Controller
{
    public function index(Request $request): View
    {
        $serata = Serata::corrente();
        $claimToken = $this->postazioneClaimToken($request);
        $postazioni = Postazione::query()->orderBy('id')->get();
        $postazioneId = (int) ($request->session()->get('postazione_id') ?? 0);
        $postazioneAttiva = $postazioneId > 0
            ? $postazioni->firstWhere('id', $postazioneId)
            : null;

        // Riprendi solo se questa sessione possiede ancora il claim attivo.
        $claimAttivo = $postazioneAttiva
            && $postazioneAttiva->isClaimedBy($claimToken)
            && $postazioneAttiva->hasActiveClaim();
        if (! $claimAttivo) {
            $postazioneId = 0;
            $request->session()->forget('postazione_id');
        } else {
            $postazioneAttiva->touchClaim();
        }

        $menu = MenuItem::query()
            ->with('categoria')
            ->where('attivo', true)
            ->orderBy('ordinamento')
            ->get()
            ->map(fn (MenuItem $item) => [
                'id' => $item->id,
                'nome' => $item->nome,
                'prezzo' => (float) $item->prezzo,
                'categoria' => $item->categoria->nome,
                'categoria_id' => $item->categoria_id,
                'area_stampa' => $item->areaStampaEffettiva(),
                'stock_limitato' => $item->stock_default !== null,
                'ordinamento' => $item->ordinamento,
                'piatto_del_giorno' => $item->piatto_del_giorno,
                'is_coperto' => (bool) $item->is_coperto,
            ]);

        $stock = [];
        if ($serata) {
            $stockService = app(StockService::class);
            $stockService->assicuraStockLimitati($serata->id);
            $stock = $stockService->mappaResidui($serata->id);
        }

        $impostazioni = Impostazione::corrente();
        // Allineato all’allocator atomico (comanda_numeri), non al max delle comande.
        $prossimoNumero = (int) (DB::table('comanda_numeri')->max('id') ?? 0) + 1;
        $prossimoNumeroDiSerata = Comanda::prossimoNumeroDiSerata($serata?->id);
        $copertiTotali = Comanda::copertiTotaliSerata($serata?->id);

        $ultimaPayload = $this->ultimaStampataPayload($postazioneId);

        return view('cassa.index', [
            'serata' => $serata,
            'postazioni' => $postazioni->map(fn (Postazione $p) => $p->statoClaimPer($claimToken))->values(),
            'postazioneId' => $postazioneId,
            'richiedeSceltaPostazione' => $postazioneId === 0,
            'menu' => $menu,
            'stock' => $stock,
            'impostazioni' => $impostazioni,
            'prossimoNumero' => $prossimoNumero,
            'prossimoNumeroDiSerata' => $prossimoNumeroDiSerata,
            'copertiTotali' => $copertiTotali,
            'ultimaStampata' => $ultimaPayload
                ? (object) [
                    'numero_progressivo' => $ultimaPayload['numero'],
                    'totale' => $ultimaPayload['totale'],
                ]
                : null,
        ]);
    }

    public function setPostazione(Request $request): JsonResponse
    {
        $data = $request->validate([
            'postazione_id' => 'required|exists:postazioni,id',
            'force' => 'sometimes|boolean',
            'pin' => 'nullable|string|max:40',
        ]);

        $postazione = Postazione::query()->findOrFail((int) $data['postazione_id']);
        $claimToken = $this->postazioneClaimToken($request);
        $force = (bool) ($data['force'] ?? false);

        if (! $force && $postazione->claimConflictFor($claimToken)) {
            $tempo = $postazione->claimAgeLabel();

            return response()->json([
                'ok' => false,
                'claim_conflitto' => true,
                'richiede_pin' => true,
                'error' => "«{$postazione->nome}» è già in uso (ultima attività: {$tempo} fa). Per forzare serve il PIN gestione: l’altra postazione verrà sospesa.",
                'postazione_id' => $postazione->id,
                'postazione_nome' => $postazione->nome,
                'postazioni' => $this->postazioniStato($claimToken),
            ], 409);
        }

        if ($force && $postazione->claimConflictFor($claimToken)) {
            $pin = (string) ($data['pin'] ?? '');
            $atteso = (string) Impostazione::corrente()->pin_gestione;
            if ($pin === '' || ! hash_equals($atteso, $pin)) {
                return response()->json([
                    'ok' => false,
                    'claim_conflitto' => true,
                    'richiede_pin' => true,
                    'error' => 'PIN non valido. Impossibile forzare la postazione.',
                    'postazione_id' => $postazione->id,
                    'postazione_nome' => $postazione->nome,
                    'postazioni' => $this->postazioniStato($claimToken),
                ], 422);
            }
        }

        $prevId = (int) $request->session()->get('postazione_id');
        if ($prevId > 0 && $prevId !== $postazione->id) {
            Postazione::query()->find($prevId)?->releaseIfClaimedBy($claimToken);
        }

        $postazione->claim($claimToken);
        $request->session()->put('postazione_id', $postazione->id);

        $mappata = $postazione->puntoCassaAttivo() !== null;

        return response()->json([
            'ok' => true,
            'mappata' => $mappata,
            'postazione_id' => $postazione->id,
            'postazione_nome' => $postazione->nome,
            'warning' => $mappata
                ? null
                : 'Questa postazione non è ancora collegata al cassetto — chiedi a chi gestisce le Impostazioni di completare il collegamento.',
            'ultima_stampata' => $this->ultimaStampataPayload($postazione->id),
            'postazioni' => $this->postazioniStato($claimToken),
        ]);
    }

    public function stock(Request $request, StockService $stock): JsonResponse
    {
        $claimToken = $this->postazioneClaimToken($request);
        $postazioneId = (int) $request->session()->get('postazione_id');
        $claimPerso = false;

        if ($postazioneId > 0) {
            $postazione = Postazione::query()->find($postazioneId);
            if (! $postazione) {
                $claimPerso = true;
                $request->session()->forget('postazione_id');
                $postazioneId = 0;
            } elseif ($postazione->isClaimedBy($claimToken) && $postazione->hasActiveClaim()) {
                $postazione->touchClaim();
            } else {
                // Claim rubato, scaduto o non più nostro: sospendi questa cassa.
                $claimPerso = true;
                $request->session()->forget('postazione_id');
                $postazioneId = 0;
            }
        }

        $serata = Serata::corrente();
        if (! $serata) {
            return response()->json([
                'stock' => [],
                'coperti_totali' => 0,
                'ultima_stampata' => null,
                'claim_perso' => $claimPerso,
                'claim_attivo' => false,
                'postazioni' => $this->postazioniStato($claimToken),
            ]);
        }

        $stock->assicuraStockLimitati($serata->id);

        return response()->json([
            'stock' => $stock->mappaResidui($serata->id),
            'coperti_totali' => Comanda::copertiTotaliSerata($serata->id),
            'ultima_stampata' => $this->ultimaStampataPayload($postazioneId),
            'claim_perso' => $claimPerso,
            'claim_attivo' => $postazioneId > 0 && ! $claimPerso,
            'postazioni' => $this->postazioniStato($claimToken),
        ]);
    }

    public function conferma(Request $request, ComandaService $service): JsonResponse
    {
        $data = $request->validate([
            'postazione_id' => 'required|exists:postazioni,id',
            'coperti' => 'required|integer|min:0',
            'metodo_pagamento' => 'required|in:contante,pos,misto,omaggio,sospeso',
            'importo_contante' => 'nullable|numeric',
            'importo_pos' => 'nullable|numeric',
            'comanda_id' => 'nullable|exists:comande,id',
            'motivo' => 'nullable|string|max:255',
            'tavolo' => 'nullable|string|max:40',
            'note' => 'nullable|string|max:255',
            'version' => 'nullable|integer|min:1',
            'pin_autorizzazione' => 'nullable|string|max:40',
            'autorizzato_da' => 'nullable|string|max:80',
            'nominativo' => 'nullable|string|max:80',
            'pagamento_note' => 'nullable|string|max:255',
            'righe' => 'required|array|min:1',
            'righe.*.menu_item_id' => 'required|exists:menu_items,id',
            'righe.*.quantita' => 'required|integer|min:1',
        ]);

        $serata = Serata::corrente();
        if (! $serata) {
            return response()->json(['error' => 'Nessuna serata aperta.'], 422);
        }

        $guard = $this->assertClaimAttivo($request, (int) $data['postazione_id']);
        if ($guard !== null) {
            return $guard;
        }

        if (in_array($data['metodo_pagamento'], ['omaggio', 'sospeso'], true)) {
            $pin = (string) ($data['pin_autorizzazione'] ?? '');
            $atteso = (string) Impostazione::corrente()->pin_gestione;
            if ($pin === '' || ! hash_equals($atteso, $pin)) {
                return response()->json(['error' => 'PIN non valido.'], 422);
            }
        }

        try {
            $esistente = isset($data['comanda_id'])
                ? Comanda::query()->find($data['comanda_id'])
                : null;

            $comanda = $service->confermaEStampa(
                $serata,
                Postazione::query()->findOrFail($data['postazione_id']),
                $data['righe'],
                (int) $data['coperti'],
                $data['metodo_pagamento'],
                isset($data['importo_contante']) ? (float) $data['importo_contante'] : null,
                isset($data['importo_pos']) ? (float) $data['importo_pos'] : null,
                $esistente,
                $data['motivo'] ?? null,
                isset($data['version']) ? (int) $data['version'] : null,
                $data['tavolo'] ?? null,
                $data['note'] ?? null,
                $data['autorizzato_da'] ?? null,
                $data['nominativo'] ?? null,
                $data['pagamento_note'] ?? null,
            );

            return response()->json([
                'ok' => true,
                'comanda_id' => $comanda->id,
                'numero' => $comanda->numero_progressivo,
                'version' => $comanda->version,
                'print_url' => route('cassa.stampa', $comanda, absolute: false),
                'stock' => app(StockService::class)->mappaResidui($serata->id),
                'coperti_totali' => Comanda::copertiTotaliSerata($serata->id),
            ]);
        } catch (ComandaConflittoException $e) {
            return response()->json(['error' => $e->getMessage(), 'conflitto' => true], 409);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function storico(): JsonResponse
    {
        $serata = Serata::corrente();
        if (! $serata) {
            return response()->json(['comande' => []]);
        }

        $comande = Comanda::query()
            ->with(['postazione'])
            ->withCount('righe')
            ->where('serata_id', $serata->id)
            ->whereIn('stato', ['stampata', 'annullata'])
            ->orderByDesc('numero_progressivo')
            ->limit(60)
            ->get()
            ->map(fn (Comanda $c) => [
                'comanda_id' => $c->id,
                'numero' => $c->numero_progressivo,
                'version' => $c->version,
                'coperti' => $c->coperti,
                'metodo_pagamento' => $c->metodo_pagamento,
                'nominativo' => $c->nominativo,
                'totale' => (float) $c->totale,
                'stato' => $c->stato,
                'motivo_annullo' => $c->motivo_annullo,
                'n_righe' => $c->righe_count,
                'postazione_id' => (int) $c->postazione_id,
                'postazione' => $c->postazione?->nome,
                'print_url' => route('cassa.stampa', $c, absolute: false),
            ]);

        return response()->json(['comande' => $comande]);
    }

    public function richiamo(int $numero): JsonResponse
    {
        $serata = Serata::corrente();
        if (! $serata) {
            // Stesso 404 della comanda assente: non rivelare comande di altre serate.
            return response()->json(['error' => 'Comanda non trovata.'], 404);
        }

        $comanda = Comanda::query()
            ->with(['righe.menuItem.categoria', 'postazione'])
            ->where('numero_progressivo', $numero)
            ->where('serata_id', $serata->id)
            ->first();

        if (! $comanda) {
            return response()->json(['error' => 'Comanda non trovata.'], 404);
        }

        if ($comanda->isAnnullata()) {
            return response()->json(['error' => 'Comanda annullata.'], 422);
        }

        return response()->json([
            'comanda_id' => $comanda->id,
            'numero' => $comanda->numero_progressivo,
            'numero_di_serata' => $comanda->numeroDiSerata(),
            'version' => $comanda->version,
            'coperti' => $comanda->coperti,
            'metodo_pagamento' => $comanda->metodo_pagamento,
            'totale' => (float) $comanda->totale,
            'tavolo' => $comanda->tavolo,
            'note' => $comanda->note,
            'autorizzato_da' => $comanda->autorizzato_da,
            'nominativo' => $comanda->nominativo,
            'pagamento_note' => $comanda->pagamento_note,
            'era_sospeso' => (bool) $comanda->era_sospeso,
            'postazione_id' => (int) $comanda->postazione_id,
            'postazione' => $comanda->postazione?->nome,
            'correzioni_count' => $comanda->correzioni()->count(),
            'print_url' => route('cassa.stampa', $comanda, absolute: false),
            'righe' => $comanda->righe->map(function ($r) {
                $item = $r->menuItem;

                return [
                    'menu_item_id' => $r->menu_item_id,
                    'quantita' => $r->quantita,
                    'prezzo_unitario' => (float) $r->prezzo_unitario,
                    'nome' => $item?->nome ?? ('Voce #'.$r->menu_item_id),
                    'menu_item' => $item ? [
                        'id' => $item->id,
                        'nome' => $item->nome,
                        'prezzo' => (float) $item->prezzo,
                        'categoria' => $item->categoria?->nome ?? '',
                        'categoria_id' => $item->categoria_id,
                        'area_stampa' => $item->areaStampaEffettiva(),
                        'stock_limitato' => $item->stock_default !== null,
                        'ordinamento' => $item->ordinamento,
                        'piatto_del_giorno' => (bool) $item->piatto_del_giorno,
                        'is_coperto' => (bool) $item->is_coperto,
                        'attivo' => (bool) $item->attivo,
                    ] : null,
                ];
            }),
        ]);
    }

    public function stampa(Comanda $comanda): View
    {
        if ($comanda->isAnnullata()) {
            abort(404, 'Comanda annullata.');
        }

        $comanda->load(['righe.menuItem.categoria', 'serata', 'correzioni']);
        $impostazioni = Impostazione::corrente();

        $righe = $comanda->righe->map(function ($r) {
            $prezzo = (float) $r->prezzo_unitario;
            $item = $r->menuItem;

            return [
                'menu_item_id' => (int) $r->menu_item_id,
                'quantita' => $r->quantita,
                'nome' => $item?->nome ?? ('Voce #'.$r->menu_item_id),
                // Live dal menù: non storicizzato su comanda_righe (a differenza di bar/prezzo).
                'congelato' => (bool) ($item?->congelato ?? false),
                'prezzo_unitario' => $prezzo,
                'importo' => round($r->quantita * $prezzo, 2),
                'area_stampa' => $item?->areaStampaEffettiva() ?? 'cliente',
            ];
        });

        return view('print.comanda', [
            'comanda' => $comanda,
            'righe' => $righe,
            'diffCorrezione' => $comanda->diffUltimaCorrezione(),
            'impostazioni' => $impostazioni,
            'numeroDiSerata' => $comanda->numeroDiSerata(),
            'autoPrint' => request()->boolean('print'),
            'parte' => request()->string('parte')->toString() ?: 'tutte',
        ]);
    }

    public function annulla(Request $request, Comanda $comanda, ComandaService $service): JsonResponse
    {
        $data = $request->validate([
            'motivo' => 'required|string|min:2',
        ]);

        // Annulla da qualsiasi postazione attiva (non solo quella che ha emesso la comanda).
        $guard = $this->assertClaimAttivo($request, (int) $request->session()->get('postazione_id'));
        if ($guard !== null) {
            return $guard;
        }

        try {
            $service->annulla($comanda, $data['motivo']);

            return response()->json([
                'ok' => true,
                'coperti_totali' => Comanda::copertiTotaliSerata($comanda->serata_id),
                'stock' => app(StockService::class)->mappaResidui((int) $comanda->serata_id),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Token stabile in sessione (sopravvive a eventuale regenerate dell'id sessione).
     */
    private function postazioneClaimToken(Request $request): string
    {
        $existing = $request->session()->get('postazione_claim_token');
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $token = bin2hex(random_bytes(16));
        $request->session()->put('postazione_claim_token', $token);

        return $token;
    }

    /** @return list<array<string, mixed>> */
    private function postazioniStato(string $claimToken): array
    {
        return Postazione::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Postazione $p) => $p->statoClaimPer($claimToken))
            ->values()
            ->all();
    }

    private function assertClaimAttivo(Request $request, int $postazioneId): ?JsonResponse
    {
        $claimToken = $this->postazioneClaimToken($request);
        $sessionePostazione = (int) $request->session()->get('postazione_id');
        $postazione = Postazione::query()->find($postazioneId);

        if (! $postazione
            || $sessionePostazione !== $postazioneId
            || ! $postazione->isClaimedBy($claimToken)
            || ! $postazione->hasActiveClaim()) {
            return response()->json([
                'error' => 'Postazione non attiva su questo computer. Scegli di nuovo la cassa.',
                'claim_perso' => true,
                'postazioni' => $this->postazioniStato($claimToken),
            ], 409);
        }

        $postazione->touchClaim();

        return null;
    }

    /**
     * @return array{numero: int, totale: float}|null
     */
    private function ultimaStampataPayload(int $postazioneId): ?array
    {
        $serata = Serata::corrente();
        if (! $serata || $postazioneId <= 0) {
            return null;
        }

        $comanda = Comanda::query()
            ->where('serata_id', $serata->id)
            ->where('postazione_id', $postazioneId)
            ->where('stato', 'stampata')
            ->orderByDesc('id')
            ->first(['numero_progressivo', 'totale']);

        if (! $comanda) {
            return null;
        }

        return [
            'numero' => (int) $comanda->numero_progressivo,
            'totale' => (float) $comanda->totale,
        ];
    }
}
