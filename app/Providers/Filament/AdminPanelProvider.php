<?php

namespace App\Providers\Filament;

use App\Http\Middleware\PinGestione;
use App\Models\Impostazione;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Pannello Gestione (Filament v5).
 *
 * Path dedicato `gestione-fi` — non intercetta `/cassa/*` né le rotte Livewire
 * legacy `/gestione/*` (restano disponibili per confronto/fallback in fase di test).
 * Gate d'accesso: middleware PinGestione (session gestione_sbloccata), non login Filament.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $brand = 'Gestione';
        try {
            $nome = Impostazione::corrente()->intestazione_nome ?? null;
            if (is_string($nome) && $nome !== '') {
                $brand = $nome.' — Gestione';
            }
        } catch (\Throwable) {
            // DB non pronto in install/migrate
        }

        return $panel
            ->default()
            ->id('gestione')
            ->path('gestione-fi')
            ->brandName($brand)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                PinGestione::class,
            ])
            ->authMiddleware([]);
    }
}
