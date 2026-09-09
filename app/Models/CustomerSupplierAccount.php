<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\CustomerSupplierAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'lenders', timestamps: false)]
#[Fillable([
    'name',
    'phone',
    'dealer',
    'type',
    'email',
    'admin_id',
    'shop_id',
    'address',
    'location',
    'details',
    'created_at',
    'updated_at',
])]
class CustomerSupplierAccount extends Model
{
    /** @use HasFactory<CustomerSupplierAccountFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dealer' => 'integer',
            'type' => 'integer',
            'admin_id' => 'integer',
            'shop_id' => 'integer',
            'created_at' => 'date:Y-m-d',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CustomerSupplierAccount $account): void {
            $now = now();
            $account->created_at ??= $now->toDateString();
            $account->updated_at = $now;
        });

        static::updating(function (CustomerSupplierAccount $account): void {
            $account->updated_at = now();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function toLegacyArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'is_customer' => $this->dealer,
            'type' => $this->type,
            'email' => $this->email,
            'profile_image' => $this->getAttribute('profile_image'),
            'admin_id' => $this->admin_id,
            'shop_id' => $this->shop_id,
            'user_id' => $this->getAttribute('user_id'),
            'address' => $this->address,
            'location' => $this->location,
            'details' => $this->details,
            'sync' => $this->getAttribute('sync'),
            'status' => $this->getAttribute('status'),
            'created_at' => $this->formatLegacyDate($this->created_at),
            'updated_at' => $this->formatLegacyDateTime($this->updated_at),
        ];
    }

    private function formatLegacyDate(mixed $value): mixed
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        return $value;
    }

    private function formatLegacyDateTime(mixed $value): mixed
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d\TH:i:s');
        }

        return $value;
    }
}
