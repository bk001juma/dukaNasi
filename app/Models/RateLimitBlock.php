<?php

namespace App\Models;

use Database\Factories\RateLimitBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'rate_limit_block', timestamps: false)]
#[Fillable([
    'key_type',
    'key_value',
    'blocked_until',
    'current_penalty',
    'created_at',
])]
class RateLimitBlock extends Model
{
    /** @use HasFactory<RateLimitBlockFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'blocked_until' => 'integer',
            'current_penalty' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RateLimitBlock $block): void {
            $block->created_at ??= now();
        });
    }
}
