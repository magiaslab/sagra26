<?php

namespace App\Support;

use App\Models\Postazione;
use App\Models\PuntoCassa;

/**
 * Guardrail di eliminazione già usati in ImpostazioniPage.
 * Condivisi con le Resource Filament — stessa regola, zero logica nuova.
 */
final class GestioneEliminaGuardrail
{
    public static function motivoBloccoPostazione(Postazione $postazione): ?string
    {
        $nComande = $postazione->comande()->count();
        if ($nComande > 0) {
            return "Non eliminabile: {$nComande} comande già registrate su questa postazione";
        }

        $nMappature = $postazione->mappature()->count();
        if ($nMappature > 0) {
            return "Non eliminabile: {$nMappature} mappature punto cassa collegate a questa postazione";
        }

        return null;
    }

    public static function motivoBloccoPuntoCassa(PuntoCassa $punto): ?string
    {
        $nComande = $punto->comande()->count();
        if ($nComande > 0) {
            return "Non eliminabile: {$nComande} comande già registrate su questo punto cassa";
        }

        $nChiusure = $punto->chiusure()->count();
        if ($nChiusure > 0) {
            return "Non eliminabile: {$nChiusure} chiusure collegate a questo punto cassa";
        }

        $nMappature = $punto->mappature()->count();
        if ($nMappature > 0) {
            return "Non eliminabile: {$nMappature} mappature postazione collegate a questo punto cassa";
        }

        return null;
    }
}
