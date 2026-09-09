<?php

namespace App\Models;

use Database\Factories\StockVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'stock_variants')]
#[Fillable([
    'type',
    'color',
    'size',
    'brand',
    'imgs',
    'expire_date',
    'value',
    'purchase_price',
    'sale_price',
    'qty',
    'stock_id',
    'shop_id',
    'admin_id',
    'sku',
])]
class StockVariant extends Model
{
    /** @use HasFactory<StockVariantFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_price' => 'float',
            'sale_price' => 'float',
            'qty' => 'float',
            'stock_id' => 'integer',
            'shop_id' => 'integer',
            'admin_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
