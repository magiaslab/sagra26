<?php

namespace App\Providers;

use App\Models\Impostazione;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            if (! isset($view->impostazioni)) {
                try {
                    $view->with('impostazioni', Impostazione::corrente());
                } catch (\Throwable) {
                    $view->with('impostazioni', null);
                }
            }
        });
    }
}
