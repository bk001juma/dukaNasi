<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;  

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'username' => 'superadmin',
            'email' => 'admin@dukanasi.com',
            'password' => Hash::make('password'),  
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'super_user' => 1,
            'parent_admin_id' => null, 
            'active' => 1,
            'company_name' => 'DukaNasi',
            'country' => 'Tanzania',
            'phone' => '+255 6805 22 062',
        ]);
    }
}