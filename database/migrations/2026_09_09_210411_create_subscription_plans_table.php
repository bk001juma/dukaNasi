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
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['deleted_at', 'title']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
