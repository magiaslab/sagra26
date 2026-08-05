<?php

it('la barra azioni cassa usa flex-wrap e non altezza fissa h-12', function () {
    $html = file_get_contents(resource_path('views/cassa/index.blade.php'));

    expect($html)
        ->toContain('flex flex-wrap items-center gap-2')
        ->not->toContain('flex h-12 items-center justify-between gap-2 border-b border-sagra-line bg-white')
        ->toContain("placeholder=\"Tavolo\"")
        ->toContain('Conferma')
        ->toContain('Richiama');
});
