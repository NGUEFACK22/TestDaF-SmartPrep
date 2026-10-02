<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ManualCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'corrector_id', 'evaluable_type', 'evaluable_id',
        'points', 'max_points', 'comments', 'status', 'corrected_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'max_points' => 'decimal:2',
            'corrected_at' => 'datetime',
        ];
    }

    public function evaluable(): MorphTo
    {
        return $this->morphTo();
    }

    public function corrector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrector_id');
    }
}
