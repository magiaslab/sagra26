<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Impostazione;
use Illuminate\View\View;

class FacsimileController extends Controller
{
    public function index(): View
    {
        $categorie = Categoria::query()
            ->with(['menuItems' => fn ($q) => $q->where('attivo', true)->orderBy('ordinamento')])
            ->orderBy('ordinamento')
            ->get()
            ->filter(fn (Categoria $c) => $c->menuItems->isNotEmpty())
            ->values();

        return view('print.facsimile', [
            'categorie' => $categorie,
            'impostazioni' => Impostazione::corrente(),
            'autoPrint' => request()->boolean('print'),
        ]);
    }
}
