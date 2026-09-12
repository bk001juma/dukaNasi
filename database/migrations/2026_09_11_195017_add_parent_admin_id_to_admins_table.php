<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'parent_admin_id')) {
                $table->unsignedBigInteger('parent_admin_id')
                    ->nullable()
                    ->after('admin_id')
                    ->index();
                
                $table->foreign('parent_admin_id')
                    ->references('id')
                    ->on('admins')
                    ->onDelete('restrict');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['parent_admin_id']);
            $table->dropColumn('parent_admin_id');
        });
    }
};