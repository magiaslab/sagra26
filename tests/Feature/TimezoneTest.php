<?php

it('usa Europe/Rome come timezone applicativo', function () {
    expect(config('app.timezone'))->toBe('Europe/Rome')
        ->and(now()->timezoneName)->toBe('Europe/Rome');
});
