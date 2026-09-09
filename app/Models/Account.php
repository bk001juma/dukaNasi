<?php

namespace App\Models;

use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'accounts', timestamps: false)]
#[Fillable([
    'reference',
    'gl_type',
    'open_balance',
    'balance',
    'date',
    'amount',
    'crdr',
    'shop_id',
    'details',
    'user_id',
    'admin_id',
    'created_at',
    'name',
    'type',
    'acc_type',
])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'open_balance' => 'float',
            'balance' => 'float',
            'amount' => 'float',
            'shop_id' => 'integer',
            'user_id' => 'integer',
            'admin_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Account $account): void {
            $account->created_at ??= now();
            $account->open_balance ??= 0.0;
            $account->balance ??= 0.0;
            $account->amount ??= 0.0;
            $account->crdr = blank($account->crdr) ? 'CR' : $account->crdr;
        });
    }
}
