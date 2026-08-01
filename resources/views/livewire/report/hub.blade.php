<div>
    <x-gestione.subnav />
    <x-gestione.page-header title="Report / Stampe" subtitle="Cucina, griglia, bevande, economico e consegna">
        <x-slot:actions>
            <button class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark print:hidden" type="button" onclick="window.print()">Stampa / PDF</button>
        </x-slot:actions>
    </x-gestione.page-header>

    <div class="mb-4 rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80 print:hidden">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Tipo</label>
                <select class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model.live="tipo">
                    <option value="cucina">Cucina</option>
                    <option value="griglia">Griglia</option>
                    <option value="bevande">Bevande</option>
                    <option value="statistiche">Statistiche</option>
                    <option value="economico">Economico</option>
                    <option value="consegna">Consegna incassi</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Serata (fino a / di riferimento)</label>
                <select class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model.live="serataId">
                    @foreach ($serate as $s)
                        <option value="{{ $s->id }}">{{ $s->data->format('d/m/Y') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Ambito</label>
                <label class="text-sm font-medium text-sagra-ink"><input type="checkbox" wire:model.live="completo"> Completo (tutta la sagra)</label>
                @if ($tipo === 'consegna')
                    <select class="mt-2 block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model.live="puntoCassaId">
                        @foreach ($punti as $p)
                            <option value="{{ $p->id }}">{{ $p->nome }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>
    </div>

    @if (!$serata)
        <x-ui.alert type="warn">Nessuna serata selezionata.</x-ui.alert>
    @elseif ($tipo === 'cucina' || $tipo === 'griglia')
        @include('livewire.report.partials.reparto', ['dati' => $dati, 'serata' => $serata, 'impostazioni' => $impostazioni])
    @elseif ($tipo === 'bevande')
        @include('livewire.report.partials.bevande', ['dati' => $dati, 'serata' => $serata, 'impostazioni' => $impostazioni])
    @elseif ($tipo === 'statistiche')
        @include('livewire.report.partials.statistiche', ['dati' => $dati, 'serata' => $serata, 'impostazioni' => $impostazioni, 'completo' => $completo])
    @elseif ($tipo === 'economico')
        @include('livewire.report.partials.economico', ['dati' => $dati, 'serata' => $serata, 'impostazioni' => $impostazioni])
    @elseif ($tipo === 'consegna')
        @include('livewire.report.partials.consegna', ['dati' => $dati, 'serata' => $serata, 'impostazioni' => $impostazioni])
    @endif
</div>
