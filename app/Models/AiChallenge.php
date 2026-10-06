<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'modell_test_id', 'skill', 'status', 'level',
        'weak_snapshot', 'generated_test_id', 'error',
        'requested_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'weak_snapshot' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceTest(): BelongsTo
    {
        return $this->belongsTo(ModellTest::class, 'modell_test_id');
    }

    public function generatedTest(): BelongsTo
    {
        return $this->belongsTo(ModellTest::class, 'generated_test_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['generating', 'ready'], true);
    }
}
