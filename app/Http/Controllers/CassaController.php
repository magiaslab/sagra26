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
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class CassaController extends Controller
{
    public function index(Request $request): View
    {
        $serata = Serata::corrente();
        $postazioni = Postazione::query()->orderBy('id')->get();
        $postazioneId = (int) ($request->session()->get('postazione_id') ?? $postazioni->first()?->id);

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
            ]);

        $stock = $serata
            ? app(StockService::class)->mappaResidui($serata->id)
            : [];

        $impostazioni = Impostazione::corrente();

        return view('cassa.index', [
            'serata' => $serata,
            'postazioni' => $postazioni,
            'postazioneId' => $postazioneId,
            'menu' => $menu,
            'stock' => $stock,
            'impostazioni' => $impostazioni,
        ]);
    }

    public function setPostazione(Request $request): JsonResponse
    {
        $data = $request->validate([
            'postazione_id' => 'required|exists:postazioni,id',
        ]);
        $request->session()->put('postazione_id', (int) $data['postazione_id']);

        return response()->json(['ok' => true]);
    }

    public function stock(StockService $stock): JsonResponse
    {
        $serata = Serata::corrente();
        if (! $serata) {
            return response()->json(['stock' => []]);
        }

        return response()->json(['stock' => $stock->mappaResidui($serata->id)]);
    }

    public function conferma(Request $request, ComandaService $service): JsonResponse
    {
        $data = $request->validate([
            'postazione_id' => 'required|exists:postazioni,id',
            'coperti' => 'required|integer|min:0',
            'metodo_pagamento' => 'required|in:contante,pos,misto',
            'importo_contante' => 'nullable|numeric',
            'importo_pos' => 'nullable|numeric',
            'comanda_id' => 'nullable|exists:comande,id',
            'motivo' => 'nullable|string|max:255',
            'version' => 'nullable|integer|min:1',
            'righe' => 'required|array|min:1',
            'righe.*.menu_item_id' => 'required|exists:menu_items,id',
            'righe.*.quantita' => 'required|integer|min:1',
        ]);

        $serata = Serata::corrente();
        if (! $serata) {
            return response()->json(['error' => 'Nessuna serata aperta.'], 422);
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
            );

            return response()->json([
                'ok' => true,
                'comanda_id' => $comanda->id,
                'numero' => $comanda->numero_progressivo,
                'version' => $comanda->version,
                'print_url' => route('cassa.stampa', $comanda, absolute: false),
                'stock' => app(StockService::class)->mappaResidui($serata->id),
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
            ->withCount('righe')
            ->where('serata_id', $serata->id)
            ->whereIn('stato', ['stampata', 'annullata'])
            ->orderByDesc('numero_progressivo')
            ->limit(40)
            ->get()
            ->map(fn (Comanda $c) => [
                'comanda_id' => $c->id,
                'numero' => $c->numero_progressivo,
                'version' => $c->version,
                'coperti' => $c->coperti,
                'metodo_pagamento' => $c->metodo_pagamento,
                'totale' => (float) $c->totale,
                'stato' => $c->stato,
                'motivo_annullo' => $c->motivo_annullo,
                'n_righe' => $c->righe_count,
            ]);

        return response()->json(['comande' => $comande]);
    }

    public function richiamo(int $numero): JsonResponse
    {
        $comanda = Comanda::query()
            ->with(['righe.menuItem'])
            ->where('numero_progressivo', $numero)
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
            'version' => $comanda->version,
            'coperti' => $comanda->coperti,
            'metodo_pagamento' => $comanda->metodo_pagamento,
            'totale' => (float) $comanda->totale,
            'correzioni_count' => $comanda->correzioni()->count(),
            'righe' => $comanda->righe->map(fn ($r) => [
                'menu_item_id' => $r->menu_item_id,
                'quantita' => $r->quantita,
                'prezzo_unitario' => (float) $r->prezzo_unitario,
                'nome' => $r->menuItem->nome,
            ]),
        ]);
    }

    public function stampa(Comanda $comanda): View
    {
        $comanda->load(['righe.menuItem.categoria', 'serata']);
        $impostazioni = Impostazione::corrente();

        $righe = $comanda->righe->map(function ($r) {
            return [
                'quantita' => $r->quantita,
                'nome' => $r->menuItem->nome,
                'importo' => round($r->quantita * (float) $r->prezzo_unitario, 2),
                'area_stampa' => $r->menuItem->areaStampaEffettiva(),
            ];
        });

        return view('print.comanda', [
            'comanda' => $comanda,
            'righe' => $righe,
            'impostazioni' => $impostazioni,
            'autoPrint' => request()->boolean('print', true),
        ]);
    }

    public function annulla(Request $request, Comanda $comanda, ComandaService $service): JsonResponse
    {
        $data = $request->validate([
            'motivo' => 'required|string|min:2',
        ]);

        try {
            $service->annulla($comanda, $data['motivo']);

            return response()->json(['ok' => true]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
