<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Backup: SQLite su file, senza RefreshDatabase (VACUUM INTO incompatibile con le transazioni).
pest()->extend(Tests\TestCase::class)
    ->in('Backup');
