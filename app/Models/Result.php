<?php

namespace App\Models;

use App\Enums\Skill;
use App\Services\Exam\TdnService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use HasFactory;

    protected $fillable = ['attempt_id', 'skill', 'points', 'max_points', 'percentage', 'details'];

    protected function casts(): array
    {
        return [
            'skill' => Skill::class,
            'details' => 'array',
            'points' => 'decimal:2',
            'max_points' => 'decimal:2',
            'percentage' => 'decimal:2',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    /**
     * Score converti sur l'échelle 0–20 du TestDaF (indicatif,
     * jamais une note officielle TestDaF).
     */
    protected function points20(): Attribute
    {
        return new Attribute(
            get: fn () => app(TdnService::class)->points20((float) $this->percentage)
        );
    }

    /**
     * Niveau TDN estimé (bande) pour cette compétence :
     * sous TDN 3 / TDN 3 / TDN 4 / TDN 5.
     */
    protected function tdn(): Attribute
    {
        return new Attribute(
            get: fn () => app(TdnService::class)->bandLabel(
                app(TdnService::class)->points20((float) $this->percentage)
            )
        );
    }
}
