<?php

namespace App\Models;

use Database\Factories\RateLimitBlockLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'rate_limit_block_log', timestamps: false)]
#[Fillable([
    'key_type',
    'key_value',
    'penalty_level',
    'blocked_until',
    'reason',
    'timestamp',
])]
class RateLimitBlockLog extends Model
{
    /** @use HasFactory<RateLimitBlockLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'penalty_level' => 'integer',
            'blocked_until' => 'integer',
            'timestamp' => 'datetime',
        ];
    }
}
