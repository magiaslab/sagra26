<?php

namespace App\Http\Controllers;

use App\Models\Impostazione;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentiController extends Controller
{
    public function guida(): View
    {
        $path = base_path('docs/guida-operatore.md');
        abort_unless(File::exists($path), 404, 'Guida non trovata.');

        $html = Str::markdown(File::get($path), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return view('gestione.guida', [
            'html' => $html,
            'impostazioni' => Impostazione::corrente(),
        ]);
    }

    public function downloadGuida(): BinaryFileResponse
    {
        $path = base_path('docs/guida-operatore.md');
        abort_unless(File::exists($path), 404, 'Guida non trovata.');

        return response()->download($path, 'guida-operatore-cassa.md', [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    public function downloadLiberatoria(): BinaryFileResponse
    {
        $path = public_path('docs/liberatoria-volontari-minori.pdf');
        abort_unless(File::exists($path), 404, 'File liberatoria non trovato.');

        return response()->download($path, 'liberatoria-volontari-minori.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
