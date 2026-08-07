<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    public function download(Request $request, string $filename, BackupService $backups): BinaryFileResponse
    {
        try {
            $path = $backups->resolve($filename);
        } catch (Throwable $e) {
            abort(404, $e->getMessage());
        }

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/x-sqlite3',
        ]);
    }
}
