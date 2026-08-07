<div>
    <x-gestione.subnav />
    <x-gestione.page-header
        title="Backup"
        subtitle="Elenco, download, backup immediato e ripristino del database"
    >
        <x-slot:actions>
            <button
                type="button"
                class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark"
                wire:click="eseguiOra"
                wire:loading.attr="disabled"
            >Esegui backup ora</button>
        </x-slot:actions>
    </x-gestione.page-header>

    <div class="mb-4 rounded-lg bg-sagra-softer px-4 py-3 text-sm text-sagra-ink ring-1 ring-inset ring-sagra-line/70">
        <p class="m-0">
            I backup automatici (cron ogni 5′) finiscono in
            <code class="text-xs">storage/backups/</code>.
            Si tengono gli ultimi {{ $retention }} file (~24 ore).
            @if (! $scriptExists)
                <span class="font-semibold text-sagra-warn"> Script {{ 'deploy/backup.sh' }} non trovato.</span>
            @endif
        </p>
        <p class="mt-1 mb-0 text-xs text-sagra-muted">
            Cartella: {{ $backupDir }}
        </p>
    </div>

    @if ($fileDaRipristinare)
        <div class="mb-4 rounded-lg bg-sagra-danger-soft px-4 py-4 text-sagra-ink ring-2 ring-inset ring-sagra-danger/40" role="alertdialog">
            <p class="m-0 text-base font-semibold text-sagra-danger">Conferma ripristino</p>
            <p class="mt-1 mb-3 text-sm">
                Stai per sostituire il database attuale con
                <strong>{{ $fileDaRipristinare }}</strong>.
                Prima verrà creato un backup di sicurezza automatico.
                Digita <strong>RIPRISTINA</strong> per continuare.
            </p>
            <div class="flex flex-wrap items-end gap-2">
                <div class="min-w-[12rem] flex-1">
                    <label class="mb-1 block text-sm font-medium">Conferma</label>
                    <input
                        class="block w-full rounded-md bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra"
                        type="text"
                        wire:model="testoConferma"
                        placeholder="RIPRISTINA"
                        autocomplete="off"
                    >
                </div>
                <button type="button" class="inline-flex items-center rounded-md bg-sagra-danger px-3 py-2 text-sm font-semibold text-white hover:opacity-90" wire:click="eseguiRipristino">Ripristina</button>
                <button type="button" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line" wire:click="annullaRipristino">Annulla</button>
            </div>
        </div>
    @endif

    @if ($fileDaEliminare)
        <div class="mb-4 rounded-lg bg-sagra-warn-soft px-4 py-4 text-sagra-ink ring-2 ring-inset ring-sagra-warn/40" role="alertdialog">
            <p class="m-0 text-base font-semibold text-sagra-warn">Eliminare {{ $fileDaEliminare }}?</p>
            <p class="mt-1 mb-3 text-sm">L’operazione non si può annullare.</p>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="inline-flex items-center rounded-md bg-sagra-danger px-3 py-2 text-sm font-semibold text-white" wire:click="confermaElimina">Elimina</button>
                <button type="button" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line" wire:click="annullaElimina">Annulla</button>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-sagra-line/80">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-sagra-line text-sm">
                <thead>
                    <tr class="bg-sagra-softer text-left">
                        <th class="px-3 py-2 font-semibold text-sagra-ink">File</th>
                        <th class="px-3 py-2 font-semibold text-sagra-ink">Data</th>
                        <th class="px-3 py-2 font-semibold text-sagra-ink">Dimensione</th>
                        <th class="px-3 py-2 font-semibold text-sagra-ink">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sagra-line">
                @forelse ($backups as $b)
                    <tr>
                        <td class="px-3 py-2 font-mono text-xs text-sagra-ink sm:text-sm">{{ $b['filename'] }}</td>
                        <td class="px-3 py-2 tabular-nums text-sagra-ink">{{ $b['created_label'] }}</td>
                        <td class="px-3 py-2 tabular-nums text-sagra-muted">{{ $b['size_label'] }}</td>
                        <td class="px-3 py-2">
                            <div class="flex flex-wrap gap-2">
                                <a
                                    class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer"
                                    href="{{ route('gestione.backup.download', ['filename' => $b['filename']], absolute: false) }}"
                                >Scarica</a>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md bg-sagra px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-sagra-dark"
                                    wire:click="chiediRipristino('{{ $b['filename'] }}')"
                                >Ripristina</button>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-sagra-danger shadow-sm ring-1 ring-inset ring-sagra-danger/30 hover:bg-sagra-danger-soft"
                                    wire:click="chiediElimina('{{ $b['filename'] }}')"
                                >Elimina</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3 py-6 text-sagra-muted">
                            Nessun backup presente. Premi «Esegui backup ora» oppure verifica il cron.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
