<?php

namespace App\Models;

use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'admins')]
#[Fillable([
    'username',
    'password',
    'email',
    'emails',
    'activation_code',
    'forgotten_password_code',
    'forgotten_password_time',
    'remember_code',
    'created_on',
    'last_login',
    'active',
    'first_name',
    'middlename',
    'last_name',
    'phone',
    'joining_date',
    'expiring_date',
    'postal_address',
    'physical_address',
    'super_user',
    'defaultpass',
    'hash',
    'photo',
    'profile_image',
    'payment_status',
    'lang',
    'admin_id',
    'email_sub',
    'createdby',
    'bundle_id',
    'bundle_qty',
    'last_pay',
    'shop_limit',
    'editedby',
    'edited_on',
    'company_name',
    'country',
    'state',
    'city',
    'postcode',
    'website',
    'user_id',
    'user_type',
    'secret_key',
    'shops_ids',
    'first_login',
])]
#[Hidden([
    'id',
    'password',
    'activation_code',
    'forgotten_password_code',
    'forgotten_password_time',
    'remember_code',
    'created_on',
    'active',
    'super_user',
    'defaultpass',
    'hash',
    'createdby',
    'editedby',
    'edited_on',
    'created_at',
    'updated_at',
])]
class Admin extends Model
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_on' => 'datetime',
            'joining_date' => 'date:Y-m-d',
            'expiring_date' => 'date:Y-m-d',
            'edited_on' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'active' => 'integer',
            'super_user' => 'integer',
            'payment_status' => 'integer',
            'lang' => 'integer',
            'admin_id' => 'integer',
            'email_sub' => 'integer',
            'createdby' => 'integer',
            'bundle_id' => 'integer',
            'bundle_qty' => 'integer',
            'shop_limit' => 'integer',
            'editedby' => 'integer',
            'user_id' => 'integer',
            'first_login' => 'integer',
        ];
    }
}
