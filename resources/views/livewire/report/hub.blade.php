<div>
    @php
        $reportLandscape = in_array($tipo, [
            'cumulativo', 'cucina_1', 'cucina_2', 'griglia', 'bevande', 'economico', 'confronto',
        ], true);
    @endphp

    <style>
        @media print {
            @page {
                size: A4 {{ $reportLandscape ? 'landscape' : 'portrait' }};
                margin: 8mm;
            }
        }
    </style>

    <x-gestione.subnav />
    <x-gestione.page-header
        class="print:hidden"
        title="Report / Stampe"
        subtitle="Cumulativo produzione, dettagli reparto, bevande, economico, confronto e CSV"
    >
        <x-slot:actions>
            <button class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line hover:bg-sagra-softer" type="button" wire:click="exportCsv">Export CSV</button>
            <button class="inline-flex items-center rounded-md bg-sagra px-3 py-2 text-sm font-semibold text-white hover:bg-sagra-dark" type="button" onclick="window.print()">Stampa / PDF</button>
        </x-slot:actions>
    </x-gestione.page-header>

    <div class="mb-4 rounded-lg bg-white p-5 shadow-sm ring-1 ring-sagra-line/80 print:hidden">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-sagra-ink">Tipo</label>
                <select class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model.live="tipo">
                    <option value="cumulativo">Cumulativo produzione</option>
                    <option value="cucina_1">Dettaglio Cucina 1</option>
                    <option value="cucina_2">Dettaglio Cucina 2</option>
                    <option value="griglia">Dettaglio Griglia</option>
                    <option value="bevande">Bevande</option>
                    <option value="statistiche">Statistiche</option>
                    <option value="economico">Economico</option>
                    <option value="consegna">Consegna incassi</option>
                    <option value="confronto">Confronto serate</option>
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
                @if ($tipo === 'confronto')
                    <label class="mt-2 mb-1 block text-sm font-medium text-sagra-ink">Confronta con</label>
                    <select class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model.live="serataConfrontoId">
                        <option value="">—</option>
                        @foreach ($serate as $s)
                            @if ((int) $s->id !== (int) $serataId)
                                <option value="{{ $s->id }}">{{ $s->data->format('d/m/Y') }}</option>
                            @endif
                        @endforeach
                    </select>
                @endif
            </div>
        </div>
        <p class="mt-1 text-xs text-sagra-muted">
            Stampa:
            @if ($reportLandscape)
                A4 orizzontale
            @else
                A4 verticale
            @endif
            (impostato automaticamente per questo report).
        </p>
    </div>

    <div @class(['report-print', 'report-print--landscape' => $reportLandscape])>
        @if (!$serata)
            <x-ui.alert type="warn">Nessuna serata selezionata.</x-ui.alert>
        @elseif (in_array($tipo, ['cumulativo', 'cucina_1', 'cucina_2', 'griglia'], true))
            @include('livewire.report.partials.reparto', ['dati' => $dati, 'serata' => $serata, 'impostazioni' => $impostazioni])
        @elseif ($tipo === 'bevande')
            @include('livewire.report.partials.bevande', ['dati' => $dati, 'serata' => $serata, 'impostazioni' => $impostazioni])
        @elseif ($tipo === 'statistiche')
            @include('livewire.report.partials.statistiche', ['dati' => $dati, 'serata' => $serata, 'impostazioni' => $impostazioni, 'completo' => $completo])
        @elseif ($tipo === 'economico')
            @include('livewire.report.partials.economico', ['dati' => $dati, 'serata' => $serata, 'impostazioni' => $impostazioni])
        @elseif ($tipo === 'consegna')
            @include('livewire.report.partials.consegna', ['dati' => $dati, 'serata' => $serata, 'impostazioni' => $impostazioni])
        @elseif ($tipo === 'confronto')
            @include('livewire.report.partials.confronto', ['dati' => $dati, 'serata' => $serata, 'impostazioni' => $impostazioni])
        @endif
    </div>
</div>
