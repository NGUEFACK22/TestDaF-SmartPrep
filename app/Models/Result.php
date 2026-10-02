<?php

namespace App\Models;

use App\Enums\Skill;
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
}
