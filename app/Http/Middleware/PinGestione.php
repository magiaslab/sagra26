<?php

namespace App\Http\Middleware;

use App\Models\Impostazione;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PinGestione
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('gestione_sbloccata') === true) {
            return $next($request);
        }

        if ($request->is('gestione/pin') || $request->is('gestione/pin/*')) {
            return $next($request);
        }

        return redirect()->to(route('gestione.pin', absolute: false));
    }
}
