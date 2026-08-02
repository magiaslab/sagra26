<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Postazione extends Model
{
    public const CLAIM_TTL_SECONDS = 180;

    protected $table = 'postazioni';

    protected $fillable = [
        'nome',
        'claimed_session_id',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
        ];
    }

    public function mappature(): HasMany
    {
        return $this->hasMany(PostazionePuntoCassa::class);
    }

    public function comande(): HasMany
    {
        return $this->hasMany(Comanda::class);
    }

    public function puntoCassaAttivo(?string $data = null): ?PuntoCassa
    {
        $data = $data ?? now()->toDateString();

        $mappa = PostazionePuntoCassa::query()
            ->where('postazione_id', $this->id)
            ->whereDate('valido_da', '<=', $data)
            ->orderByDesc('valido_da')
            ->first();

        return $mappa?->puntoCassa;
    }

    public function isClaimedBy(string $sessionId): bool
    {
        return $this->claimed_session_id !== null
            && hash_equals((string) $this->claimed_session_id, $sessionId);
    }

    public function hasActiveClaim(?CarbonInterface $now = null): bool
    {
        if ($this->claimed_session_id === null || $this->claimed_at === null) {
            return false;
        }

        $now = $now ?? now();

        return $this->claimed_at->gt($now->copy()->subSeconds(self::CLAIM_TTL_SECONDS));
    }

    public function claimConflictFor(string $sessionId, ?CarbonInterface $now = null): bool
    {
        return $this->hasActiveClaim($now) && ! $this->isClaimedBy($sessionId);
    }

    public function claim(string $sessionId, ?CarbonInterface $at = null): void
    {
        $this->forceFill([
            'claimed_session_id' => $sessionId,
            'claimed_at' => $at ?? now(),
        ])->save();
    }

    public function touchClaim(?CarbonInterface $at = null): void
    {
        if ($this->claimed_session_id === null) {
            return;
        }

        $this->forceFill([
            'claimed_at' => $at ?? now(),
        ])->save();
    }

    public function claimAgeLabel(?CarbonInterface $now = null): string
    {
        if ($this->claimed_at === null) {
            return 'poco';
        }

        $now = $now ?? now();
        $seconds = max(0, $this->claimed_at->diffInSeconds($now));

        if ($seconds < 60) {
            return $seconds.' secondi';
        }

        $minutes = (int) floor($seconds / 60);

        return $minutes === 1 ? '1 minuto' : "{$minutes} minuti";
    }
}
