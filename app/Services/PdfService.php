<?php

namespace App\Services;

use App\Models\Impostazione;
use Illuminate\Support\Facades\File;
use RuntimeException;

class PdfService
{
    public function generaDaUrl(string $url, string $outputPath): string
    {
        $path = Impostazione::corrente()->chromium_path
            ?: env('CHROMIUM_PATH', '/usr/bin/chromium-browser');

        if (! is_executable($path) && ! File::exists($path)) {
            throw new RuntimeException("Chromium non trovato: {$path}");
        }

        $dir = dirname($outputPath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $cmd = escapeshellarg($path)
            .' --headless --disable-gpu --no-pdf-header-footer --print-to-pdf='
            .escapeshellarg($outputPath)
            .' '.escapeshellarg($url);

        exec($cmd.' 2>&1', $output, $code);

        if ($code !== 0 || ! File::exists($outputPath)) {
            throw new RuntimeException('Generazione PDF fallita: '.implode("\n", $output));
        }

        return $outputPath;
    }
}
