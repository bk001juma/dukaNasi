<?php

namespace App\Models;

use Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table(name: 'subscription_plans')]
#[Fillable(['title', 'amount', 'description', 'duration_days'])]
#[Hidden(['deleted_at'])]
class SubscriptionPlan extends Model
{
    /** @use HasFactory<SubscriptionPlanFactory> */
    use HasFactory, SoftDeletes;

    public const DURATION_DAYS = 30;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'duration_days' => self::DURATION_DAYS,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'duration_days' => 'integer',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return array{id: int, title: string|null, amount: float, description: string|null, durationDays: int, createdAt: mixed, updatedAt: mixed}
     */
    public function toLegacyArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'durationDays' => $this->duration_days,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
