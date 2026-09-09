<?php

namespace App\Models;

use Database\Factories\LenderStatementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'lender_statements', timestamps: false)]
#[Fillable([
    'lender_id',
    'type',
    'is_collection',
    'paydesc',
    'details',
    'amount',
    'date',
    'admin_id',
    'shop_id',
    'user_id',
    'created_at',
    'reference',
])]
class LenderStatement extends Model
{
    /** @use HasFactory<LenderStatementFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lender_id' => 'integer',
            'type' => 'integer',
            'is_collection' => 'integer',
            'date' => 'date:Y-m-d',
            'admin_id' => 'integer',
            'shop_id' => 'integer',
            'user_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LenderStatement $statement): void {
            $statement->date ??= now()->toDateString();
            $statement->created_at ??= now();
            $statement->is_collection ??= 0;
        });
    }
}
