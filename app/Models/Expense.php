<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'expenses')]
#[Fillable([
    'name',
    'amount',
    'details',
    'type',
    'user_id',
    'admin_id',
    'shop_id',
    'store_id',
    'user_name',
    'date',
    'attachment',
    'category',
    'sync',
    'paid_from',
    'project',
    'VAT',
])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'user_id' => 'integer',
            'admin_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'shop_id' => 'integer',
            'store_id' => 'integer',
            'sync' => 'integer',
        ];
    }
}
