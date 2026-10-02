<?php

namespace App\Models;

use App\Enums\Skill;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModellTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'title', 'description', 'theme', 'difficulty',
        'status', 'total_duration_seconds', 'created_by',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function sectionFor(Skill $skill): ?Section
    {
        return $this->sections->firstWhere('skill', $skill->value);
    }

    /** Somme des durées d'exercices (recalculée). */
    public function computedDuration(): int
    {
        return (int) $this->sections->flatMap->exercises->sum('duration_seconds');
    }
}
