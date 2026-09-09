<?php

namespace App\Models;

use Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'stock', key: 'stock_id')]
#[Fillable([
    'barcode',
    'invoice',
    'type',
    'mode',
    'name',
    'img',
    'stock',
    'qty_mapping',
    'qty_mapping_unit',
    'qty_actual',
    'cat_id',
    'purchase_price',
    'sale_price',
    'sale_robo_price',
    'sale_nusu_price',
    'sale_nusurobo_price',
    'min_stock',
    'unit',
    'status',
    'expr_date',
    'app_updated_at',
    'store_id',
    'app_id',
    'shop_id',
    'admin_id',
    'origin_stock',
    'category',
    'sub_category',
    'imgs',
    'brand',
    'total_sale',
    'total_purchase',
])]
class Stock extends Model
{
    /** @use HasFactory<StockFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'mode' => 'integer',
            'stock' => 'float',
            'cat_id' => 'integer',
            'purchase_price' => 'float',
            'sale_price' => 'float',
            'sale_robo_price' => 'float',
            'sale_nusu_price' => 'float',
            'sale_nusurobo_price' => 'float',
            'min_stock' => 'integer',
            'status' => 'integer',
            'app_updated_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'store_id' => 'integer',
            'app_id' => 'integer',
            'shop_id' => 'integer',
            'admin_id' => 'integer',
            'origin_stock' => 'integer',
            'total_sale' => 'float',
            'total_purchase' => 'float',
        ];
    }
}
