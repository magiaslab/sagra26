<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComandaCorrezione extends Model
{
    protected $table = 'comanda_correzioni';

    public const UPDATED_AT = null;

    protected $fillable = [
        'comanda_id',
        'postazione_id',
        'righe_precedenti',
        'totale_precedente',
        'pagamento_precedente',
        'motivo',
    ];

    protected function casts(): array
    {
        return [
            'righe_precedenti' => 'array',
            'pagamento_precedente' => 'array',
            'totale_precedente' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    /** Etichetta metodo precedente dallo snapshot (es. POS, SCONTO 30%). */
    public function etichettaPagamentoPrecedente(): string
    {
        $p = $this->pagamento_precedente ?? [];
        $metodo = (string) ($p['metodo'] ?? '');
        $sconto = (int) ($p['sconto_percentuale'] ?? 0);

        if ($metodo === 'omaggio') {
            return 'OMAGGIO';
        }
        if ($sconto > 0 && $sconto < 100) {
            $base = match ($metodo) {
                'pos' => 'POS',
                'misto' => 'MISTO',
                'contante' => 'CONTANTE',
                default => strtoupper($metodo ?: '—'),
            };

            return 'SCONTO '.$sconto.'% · '.$base;
        }

        return match ($metodo) {
            'pos' => 'POS',
            'misto' => 'MISTO',
            'contante' => 'CONTANTE',
            'sospeso' => 'SOSPESO',
            'omaggio' => 'OMAGGIO',
            default => $metodo !== '' ? strtoupper($metodo) : '—',
        };
    }

    public function comanda(): BelongsTo
    {
        return $this->belongsTo(Comanda::class);
    }

    public function postazione(): BelongsTo
    {
        return $this->belongsTo(Postazione::class);
    }
}
