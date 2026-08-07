<?php

namespace App\Services;

use App\Models\Edizione;
use App\Models\Impostazione;
use App\Models\Serata;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EdizioneService
{
    /**
     * Bootstrap: se non esiste alcuna edizione, ne apre una.
     * Se ce ne sono ma nessuna è aperta (archiviata a fine sagra), restituisce null
     * senza riaprire in automatico.
     */
    public function assicuratiCorrente(?int $anno = null): ?Edizione
    {
        $corrente = Edizione::corrente();
        if ($corrente) {
            return $corrente;
        }

        if (Edizione::query()->exists()) {
            return null;
        }

        $anno ??= (int) (Impostazione::corrente()->intestazione_anno ?: date('Y'));

        return $this->apri($anno);
    }

    public function apri(int $anno, ?string $nome = null, ?string $note = null): Edizione
    {
        if ($anno < 2000 || $anno > 2100) {
            throw new RuntimeException('Anno non valido.');
        }

        if (Edizione::corrente()) {
            throw new RuntimeException('C’è già un’edizione aperta: chiudila prima di aprirne una nuova.');
        }

        if (Edizione::query()->where('anno', $anno)->exists()) {
            throw new RuntimeException("Esiste già un’edizione per l’anno {$anno}.");
        }

        return Edizione::query()->create([
            'anno' => $anno,
            'nome' => $nome ?: 'Sagra '.$anno,
            'stato' => 'aperta',
            'aperta_at' => now(),
            'chiusa_at' => null,
            'note' => $note,
        ]);
    }

    public function chiudi(Edizione $edizione, ?string $note = null): Edizione
    {
        if (! $edizione->isAperta()) {
            throw new RuntimeException('L’edizione non è aperta.');
        }

        if (Serata::corrente()) {
            throw new RuntimeException('Chiudi prima la serata aperta, poi l’edizione sagra.');
        }

        return DB::transaction(function () use ($edizione, $note) {
            $edizione->stato = 'archiviata';
            $edizione->chiusa_at = now();
            if ($note !== null && trim($note) !== '') {
                $edizione->note = trim($note);
            }
            $edizione->save();

            return $edizione;
        });
    }

    public function riapri(Edizione $edizione): Edizione
    {
        if ($edizione->isAperta()) {
            throw new RuntimeException('L’edizione è già aperta.');
        }

        if (Edizione::corrente()) {
            throw new RuntimeException('C’è già un’altra edizione aperta.');
        }

        $edizione->stato = 'aperta';
        $edizione->chiusa_at = null;
        if ($edizione->aperta_at === null) {
            $edizione->aperta_at = now();
        }
        $edizione->save();

        return $edizione;
    }
}
