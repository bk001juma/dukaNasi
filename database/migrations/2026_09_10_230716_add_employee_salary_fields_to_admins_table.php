<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            if (! Schema::hasColumn('admins', 'shop_id')) {
                $table->integer('shop_id')->nullable()->index();
            }

            if (! Schema::hasColumn('admins', 'salary')) {
                $table->decimal('salary', total: 15, places: 2)->nullable();
            }

            if (! Schema::hasColumn('admins', 'salary_period')) {
                $table->string('salary_period')->nullable();
            }

            if (! Schema::hasColumn('admins', 'salary_expense_id')) {
                $table->unsignedInteger('salary_expense_id')->nullable()->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                'shop_id',
                'salary',
                'salary_period',
                'salary_expense_id',
            ], fn (string $column): bool => Schema::hasColumn('admins', $column)));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
