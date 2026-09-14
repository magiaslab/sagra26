<div>
    @php
        $reportLandscape = in_array($tipo, [
            'cumulativo', 'cucina_1', 'cucina_2', 'griglia', 'bevande', 'economico', 'confronto', 'piatti',
        ], true);
        $usaRangeDate = $tipo === 'piatti';
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
        subtitle="Cumulativo menù (tutti i reparti) con range date, produzione, economico, confronto e CSV"
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
                    <option value="piatti">Cumulativo menù (cucina + bibite + bar)</option>
                    <option value="cumulativo">Cumulativo produzione</option>
                    <option value="cucina_1">Dettaglio Cucina 1</option>
                    <option value="cucina_2">Dettaglio Cucina 2</option>
                    <option value="griglia">Dettaglio Griglia</option>
                    <option value="bevande">Bevande</option>
                    <option value="statistiche">Statistiche</option>
                    <option value="economico">Economico</option>
                    <option value="consegna">Consegna incassi</option>
                    <option value="confronto">Confronto / range serate</option>
                </select>
            </div>

            @if ($usaRangeDate)
                <div class="mb-3 md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Periodo</label>
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="min-w-[9rem] flex-1">
                            <label class="mb-1 block text-xs text-sagra-muted" for="report-data-da">Da</label>
                            <input id="report-data-da" type="date"
                                   class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra"
                                   wire:model.live="dataDa"
                                   @if (!empty($minData)) min="{{ $minData }}" @endif
                                   @if (!empty($maxData)) max="{{ $maxData }}" @endif>
                        </div>
                        <div class="min-w-[9rem] flex-1">
                            <label class="mb-1 block text-xs text-sagra-muted" for="report-data-a">A</label>
                            <input id="report-data-a" type="date"
                                   class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra"
                                   wire:model.live="dataA"
                                   @if (!empty($minData)) min="{{ $minData }}" @endif
                                   @if (!empty($maxData)) max="{{ $maxData }}" @endif>
                        </div>
                        <button type="button"
                                class="inline-flex items-center rounded-md bg-sagra-softer px-3 py-2 text-sm font-semibold text-sagra-ink ring-1 ring-inset ring-sagra-line hover:bg-white"
                                wire:click="tuttaLaSagra">
                            Tutta la sagra
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-sagra-muted">
                        Seleziona i giorni da includere (serate dell’edizione). «Tutta la sagra» = prima → ultima serata.
                        @if (!empty($minData) && !empty($maxData))
                            Disponibili dal {{ \Carbon\Carbon::parse($minData)->format('d/m/Y') }}
                            al {{ \Carbon\Carbon::parse($maxData)->format('d/m/Y') }}.
                        @endif
                    </p>
                </div>
            @else
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">
                        {{ $tipo === 'confronto' ? 'Serata fine range' : 'Serata' }}
                    </label>
                    <select class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model.live="serataId">
                        @foreach ($serate as $s)
                            <option value="{{ $s->id }}">{{ $s->data->format('d/m/Y') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-sagra-ink">Ambito</label>
                    @if ($tipo !== 'confronto' && $tipo !== 'consegna')
                        <label class="text-sm font-medium text-sagra-ink">
                            <input type="checkbox" wire:model.live="completo">
                            Completo (tutta l’edizione{{ isset($edizione) && $edizione ? ' '.$edizione->anno : '' }})
                        </label>
                        <p class="mt-1 text-xs text-sagra-muted">
                            Spunta tolta = solo la serata selezionata.
                        </p>
                    @endif
                    @if ($tipo === 'consegna')
                        <select class="mt-2 block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model.live="puntoCassaId">
                            @foreach ($punti as $p)
                                <option value="{{ $p->id }}">{{ $p->nome }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if ($tipo === 'confronto')
                        <label class="mt-1 mb-1 block text-sm font-medium text-sagra-ink">Serata inizio range</label>
                        <select class="block w-full rounded-md bg-white px-3 py-2 text-sm text-sagra-ink shadow-sm ring-1 ring-inset ring-sagra-line focus:ring-2 focus:ring-sagra" wire:model.live="serataDaId">
                            @foreach ($serate as $s)
                                <option value="{{ $s->id }}">{{ $s->data->format('d/m/Y') }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-sagra-muted">Include tutte le serate tra inizio e fine.</p>
                    @endif
                </div>
            @endif
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
        @if ($tipo === 'piatti')
            @include('livewire.report.partials.piatti', ['dati' => $dati, 'impostazioni' => $impostazioni])
        @elseif (!$serata)
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

    @if ($autoPrint)
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () { window.print(); }, 150);
            });
        </script>
    @endif
</div>
