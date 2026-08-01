<div>
    <x-gestione.subnav />
    <x-gestione.page-header title="Report / Stampe" subtitle="Cucina, griglia, bevande, economico e consegna">
        <x-slot:actions>
            <button class="btn btn-primary no-print" type="button" onclick="window.print()">Stampa / PDF</button>
        </x-slot:actions>
    </x-gestione.page-header>

    <div class="panel no-print panel-stack">
        <div class="grid-3">
            <div class="field">
                <label class="label">Tipo</label>
                <select class="input" wire:model.live="tipo">
                    <option value="cucina">Cucina</option>
                    <option value="griglia">Griglia</option>
                    <option value="bevande">Bevande</option>
                    <option value="statistiche">Statistiche</option>
                    <option value="economico">Economico</option>
                    <option value="consegna">Consegna incassi</option>
                </select>
            </div>
            <div class="field">
                <label class="label">Serata (fino a / di riferimento)</label>
                <select class="input" wire:model.live="serataId">
                    @foreach ($serate as $s)
                        <option value="{{ $s->id }}">{{ $s->data->format('d/m/Y') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label">Ambito</label>
                <label><input type="checkbox" wire:model.live="completo"> Completo (tutta la sagra)</label>
                @if ($tipo === 'consegna')
                    <select class="input mt-tight" wire:model.live="puntoCassaId">
                        @foreach ($punti as $p)
                            <option value="{{ $p->id }}">{{ $p->nome }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>
    </div>

    @if (!$serata)
        <div class="alert alert-warn">Nessuna serata selezionata.</div>
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
