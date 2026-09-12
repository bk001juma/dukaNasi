<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'business_category')) {
                $table->string('business_category')
                    ->nullable()
                    ->after('company_name')
                    ->comment('Type of business: retail, grocery, pharmacy, electronics, etc.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('business_category');
        });
    }
};