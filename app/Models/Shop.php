<?php

namespace App\Models;

use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'shops')]
#[Fillable([
    'name',
    'details',
    'capital',
    'equity',
    'rent',
    'img',
    'equipment',
    'admin_id',
    'address',
    'pobox',
    'phone',
    'email',
    'location',
    'category',
    'type',
    'status',
    'license_assigned',
])]
class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'admin_id' => 'integer',
            'type' => 'integer',
            'status' => 'integer',
            'license_assigned' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
