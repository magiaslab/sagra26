<div>
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Sospesi"
        subtitle="Comande da saldare durante la serata"
    >
        <x-slot:actions>
            @if ($serata)
                <span class="inline-flex min-h-9 items-center rounded-md bg-sagra-amber-soft px-3 py-1.5 text-sm font-medium text-sagra-warn">
                    Aperti {{ $sospesi->count() }} · {{ number_format($totaleAperto, 2, ',', '.') }} €
                </span>
            @else
                <span class="inline-flex min-h-9 items-center rounded-md bg-sagra-amber-soft px-3 py-1.5 text-sm font-medium text-sagra-warn">
                    Nessuna serata aperta
                </span>
            @endif
        </x-slot:actions>
    </x-gestione.page-header>

    @if ($errore)
        <x-ui.alert type="danger" class="mb-4">{{ $errore }}</x-ui.alert>
    @endif

    @if (! $serata)
        <p class="text-sm text-sagra-muted">Apri una serata per gestire i sospesi.</p>
    @elseif ($sospesi->isEmpty())
        <p class="rounded-lg bg-white px-5 py-8 text-center text-sm text-sagra-muted shadow-sm ring-1 ring-sagra-line/80">
            Nessun sospeso aperto.
        </p>
    @else
        <div class="overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-sagra-line/80">
            <table class="w-full min-w-[36rem] text-left text-sm">
                <thead class="bg-sagra-softer text-xs uppercase tracking-wide text-sagra-muted">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">N.</th>
                        <th class="px-4 py-2.5 font-semibold">Nominativo</th>
                        <th class="px-4 py-2.5 font-semibold">Autoriz.</th>
                        <th class="px-4 py-2.5 font-semibold">Cassa</th>
                        <th class="px-4 py-2.5 font-semibold text-right">Totale</th>
                        <th class="px-4 py-2.5 font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sagra-line">
                    @foreach ($sospesi as $c)
                        <tr wire:key="sospeso-{{ $c->id }}">
                            <td class="px-4 py-3 font-mono font-semibold">#{{ $c->numero_progressivo }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-sagra-ink">{{ $c->nominativo ?: '—' }}</div>
                                @if ($c->pagamento_note)
                                    <div class="text-xs text-sagra-muted">{{ $c->pagamento_note }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sagra-muted">{{ $c->autorizzato_da ?: '—' }}</td>
                            <td class="px-4 py-3 text-sagra-muted">{{ $c->postazione?->nome }}</td>
                            <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums">{{ number_format($c->totale, 2, ',', '.') }} €</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-sagra-ink ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer"
                                       href="{{ route('cassa.stampa', $c, absolute: false) }}" target="_blank">Stampa</a>
                                    <button type="button" class="inline-flex items-center rounded-md bg-sagra px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-sagra-dark"
                                            wire:click="apriChiusura({{ $c->id }})">Chiudi / Incassa</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($chiudiId)
        @php $target = $sospesi->firstWhere('id', $chiudiId); @endphp
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/45 p-3">
            <div class="flex max-h-[min(92vh,720px)] w-[min(420px,96vw)] flex-col overflow-hidden rounded-lg bg-white shadow-xl ring-1 ring-sagra-line">
                <div class="shrink-0 px-5 pt-5">
                    <h2 class="text-lg font-semibold text-sagra-ink">
                        Chiudi sospeso #{{ $target?->numero_progressivo }}
                    </h2>
                    <p class="mt-1 text-sm text-sagra-muted">
                        {{ $target?->nominativo }} · {{ number_format((float) ($target?->totale ?? 0), 2, ',', '.') }} €
                    </p>
                    @if ($errore)
                        <x-ui.alert type="danger" class="mt-3 mb-0">{{ $errore }}</x-ui.alert>
                    @endif
                </div>

                <div class="mt-4 min-h-0 flex-1 space-y-3 overflow-y-auto px-5">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-sagra-muted">Metodo</label>
                        <select class="block w-full rounded-md bg-white px-2 py-2 text-sm ring-1 ring-inset ring-sagra-line" wire:model.live="metodo">
                            <option value="contante">Contante</option>
                            <option value="pos">POS</option>
                            <option value="misto">Misto</option>
                            <option value="omaggio">Omaggio</option>
                        </select>
                    </div>

                    @if ($metodo === 'misto')
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-sagra-muted">Contante €</label>
                                <input class="block w-full rounded-md px-2 py-2 text-sm ring-1 ring-inset ring-sagra-line" type="number" step="0.01" min="0" wire:model="importoContante">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-sagra-muted">POS €</label>
                                <input class="block w-full rounded-md px-2 py-2 text-sm ring-1 ring-inset ring-sagra-line" type="number" step="0.01" min="0" wire:model="importoPos">
                            </div>
                        </div>
                    @endif

                    @if ($metodo === 'omaggio')
                        <div>
                            <label class="mb-1 block text-xs font-medium text-sagra-muted">PIN gestione</label>
                            <input class="block w-full rounded-md px-2 py-2 text-sm ring-1 ring-inset ring-sagra-line" type="password" wire:model="pin" autocomplete="off">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-sagra-muted">Autorizzato da</label>
                            <input class="block w-full rounded-md px-2 py-2 text-sm ring-1 ring-inset ring-sagra-line" type="text" maxlength="80" wire:model="autorizzatoDa">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-sagra-muted">Nome ospite</label>
                            <input class="block w-full rounded-md px-2 py-2 text-sm ring-1 ring-inset ring-sagra-line" type="text" maxlength="80" wire:model="nominativo">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-sagra-muted">Note</label>
                            <input class="block w-full rounded-md px-2 py-2 text-sm ring-1 ring-inset ring-sagra-line" type="text" maxlength="255" wire:model="pagamentoNote">
                        </div>
                    @endif
                </div>

                <div class="mt-5 grid shrink-0 grid-cols-2 gap-2 px-5 pb-5">
                    <button type="button" class="rounded-md bg-white px-3 py-2.5 text-sm font-semibold text-sagra-ink ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" wire:click="annullaChiusura">Annulla</button>
                    <button type="button" class="rounded-md bg-sagra px-3 py-2.5 text-sm font-semibold text-white hover:bg-sagra-dark" wire:click="confermaChiusura">Conferma</button>
                </div>
            </div>
        </div>
    @endif
</div>
