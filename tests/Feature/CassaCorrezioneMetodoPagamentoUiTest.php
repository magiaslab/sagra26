<?php

it('in correzione la cassa chiede sempre contante o pos anche senza differenza', function () {
    $html = file_get_contents(resource_path('views/cassa/index.blade.php'));

    expect($html)
        ->toContain('Conferma o correggi il metodo di pagamento.')
        ->toContain('Metodo attuale')
        ->not->toContain('Correzione senza differenza: nessun incasso/resto, solo ristampa.')
        ->toContain('(corretto)');
});
