<div>
    <h1>Report / Stampe</h1>
    <div class="panel no-print" style="margin-bottom:1rem">
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
                    <select class="input" wire:model.live="puntoCassaId" style="margin-top:.5rem">
                        @foreach ($punti as $p)
                            <option value="{{ $p->id }}">{{ $p->nome }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>
        <button class="btn" onclick="window.print()">Stampa / PDF</button>
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
