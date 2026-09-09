<?php

namespace App\Models;

use Database\Factories\ShopingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'shopings')]
#[Fillable([
    'type',
    'cust_id',
    'cust_name',
    'stock_id',
    'invoice',
    'file',
    'discount',
    'qty',
    'remaining_qty',
    'amount',
    'sale',
    'stock_name',
    'variant',
    'purchase_price',
    'credit_paid',
    'credit_pending',
    'paydesc',
    'vat',
    'shipping',
    'total',
    'payment_mode',
    'payment_channels',
    'expr_date',
    'details',
    'tin',
    'user_id',
    'admin_id',
    'app_id',
    'shop_id',
    'app_sync',
    'app_created_at',
    'unit',
    'variant_id',
    'item_discount',
    'created_at',
    'updated_at',
])]
class Shoping extends Model
{
    /** @use HasFactory<ShopingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'cust_id' => 'integer',
            'stock_id' => 'integer',
            'discount' => 'float',
            'qty' => 'float',
            'remaining_qty' => 'float',
            'amount' => 'float',
            'sale' => 'float',
            'purchase_price' => 'float',
            'credit_paid' => 'float',
            'credit_pending' => 'float',
            'vat' => 'float',
            'shipping' => 'float',
            'total' => 'float',
            'payment_mode' => 'integer',
            'user_id' => 'integer',
            'admin_id' => 'integer',
            'app_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'shop_id' => 'integer',
            'app_sync' => 'integer',
            'variant_id' => 'integer',
            'item_discount' => 'float',
        ];
    }
}
