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
        if (! Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table): void {
                $table->id();
                $table->string('username')->nullable()->index();
                $table->string('password')->nullable();
                $table->string('email')->nullable()->index();
                $table->longText('emails')->nullable();
                $table->string('activation_code')->nullable();
                $table->string('forgotten_password_code')->nullable();
                $table->string('forgotten_password_time')->nullable();
                $table->string('remember_code')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->string('last_login')->nullable();
                $table->tinyInteger('active')->nullable()->default(1);
                $table->string('first_name')->nullable();
                $table->string('middlename')->nullable();
                $table->string('last_name')->nullable();
                $table->string('phone')->nullable();
                $table->date('joining_date')->nullable();
                $table->date('expiring_date')->nullable();
                $table->text('postal_address')->nullable();
                $table->text('physical_address')->nullable();
                $table->integer('super_user')->nullable();
                $table->string('defaultpass')->nullable();
                $table->text('hash')->nullable();
                $table->text('photo')->nullable();
                $table->string('profile_image')->nullable();
                $table->integer('payment_status')->nullable();
                $table->integer('lang')->nullable();
                $table->integer('admin_id')->nullable();
                $table->integer('email_sub')->nullable();
                $table->unsignedBigInteger('createdby')->nullable();
                $table->integer('bundle_id')->nullable();
                $table->integer('bundle_qty')->nullable();
                $table->string('last_pay')->nullable();
                $table->integer('shop_limit')->nullable();
                $table->unsignedBigInteger('editedby')->nullable();
                $table->dateTime('edited_on')->nullable();
                $table->string('company_name')->nullable();
                $table->string('country')->nullable();
                $table->string('state')->nullable();
                $table->string('city')->nullable();
                $table->string('postcode')->nullable();
                $table->string('website')->nullable();
                $table->integer('user_id')->nullable()->index();
                $table->string('user_type')->nullable()->index();
                $table->string('secret_key')->nullable()->index();
                $table->text('shops_ids')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->tinyInteger('first_login')->nullable();
            });
        }

        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name');
                $table->text('details')->nullable();
                $table->integer('type')->nullable()->default(1)->index();
                $table->integer('status')->nullable()->default(1)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('shops')) {
            Schema::create('shops', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->text('details')->nullable();
                $table->string('capital')->nullable()->default('0');
                $table->string('equity')->nullable()->default('0');
                $table->string('rent')->nullable()->default('0');
                $table->string('img')->nullable();
                $table->string('equipment')->nullable();
                $table->integer('admin_id')->nullable()->index();
                $table->string('address')->nullable();
                $table->string('pobox')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('location')->nullable();
                $table->string('category')->nullable();
                $table->integer('type')->nullable()->default(1)->index();
                $table->integer('status')->nullable()->default(1)->index();
                $table->integer('license_assigned')->nullable()->default(1)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->string('symbol')->nullable();
                $table->integer('admin_id')->nullable()->index();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stock')) {
            Schema::create('stock', function (Blueprint $table): void {
                $table->increments('stock_id');
                $table->string('barcode')->nullable();
                $table->string('invoice')->nullable();
                $table->integer('type')->default(0)->index();
                $table->integer('mode')->nullable()->default(0);
                $table->string('name', 64)->default('')->index();
                $table->longText('img')->nullable();
                $table->double('stock')->nullable()->default(0);
                $table->string('qty_mapping')->nullable();
                $table->string('qty_mapping_unit')->nullable();
                $table->string('qty_actual')->nullable();
                $table->integer('cat_id')->nullable();
                $table->double('purchase_price')->default(0);
                $table->double('sale_price')->default(0);
                $table->double('sale_robo_price')->nullable();
                $table->double('sale_nusu_price')->nullable();
                $table->double('sale_nusurobo_price')->nullable();
                $table->integer('min_stock')->default(1);
                $table->string('unit')->nullable();
                $table->integer('status')->default(0)->index();
                $table->string('expr_date')->nullable();
                $table->dateTime('app_updated_at')->nullable();
                $table->timestamps();
                $table->integer('store_id')->nullable();
                $table->integer('app_id')->default(0)->index();
                $table->integer('shop_id')->nullable()->index();
                $table->integer('admin_id')->nullable()->index();
                $table->integer('origin_stock')->default(0);
                $table->string('category')->nullable();
                $table->string('sub_category')->nullable();
                $table->longText('imgs')->nullable();
                $table->string('brand')->nullable();
                $table->double('total_sale')->nullable();
                $table->double('total_purchase')->nullable();

                $table->index(['admin_id', 'shop_id']);
            });
        }

        if (! Schema::hasTable('stock_variants')) {
            Schema::create('stock_variants', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('type')->nullable();
                $table->string('color')->nullable();
                $table->string('size')->nullable();
                $table->string('brand')->nullable();
                $table->longText('imgs')->nullable();
                $table->string('expire_date')->nullable();
                $table->string('value')->nullable();
                $table->double('purchase_price')->default(0);
                $table->double('sale_price')->default(0);
                $table->double('qty')->default(0);
                $table->integer('stock_id')->index();
                $table->integer('shop_id')->index();
                $table->integer('admin_id')->index();
                $table->timestamps();
                $table->string('sku')->nullable();

                $table->index(['stock_id', 'admin_id', 'shop_id']);
            });
        }

        if (! Schema::hasTable('shopings')) {
            Schema::create('shopings', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('type')->nullable()->index();
                $table->integer('cust_id')->nullable();
                $table->string('cust_name')->nullable();
                $table->integer('stock_id')->nullable()->index();
                $table->string('invoice')->nullable()->index();
                $table->string('file', 2000)->nullable();
                $table->double('discount')->nullable();
                $table->double('qty')->nullable();
                $table->double('remaining_qty')->nullable();
                $table->double('amount')->nullable();
                $table->double('sale')->nullable();
                $table->string('stock_name')->nullable();
                $table->string('variant')->nullable();
                $table->double('purchase_price')->nullable();
                $table->double('credit_paid')->nullable();
                $table->double('credit_pending')->nullable();
                $table->string('paydesc')->nullable();
                $table->double('vat')->nullable();
                $table->double('shipping')->nullable();
                $table->double('total')->nullable();
                $table->integer('payment_mode')->nullable();
                $table->text('payment_channels')->nullable();
                $table->string('expr_date')->nullable();
                $table->string('details')->nullable();
                $table->string('tin')->nullable();
                $table->integer('user_id')->nullable();
                $table->integer('admin_id')->nullable()->index();
                $table->bigInteger('app_id')->nullable()->index();
                $table->timestamps();
                $table->integer('shop_id')->nullable()->index();
                $table->integer('app_sync')->nullable();
                $table->string('app_created_at')->nullable();
                $table->string('unit')->nullable();
                $table->integer('variant_id')->nullable();
                $table->double('item_discount')->nullable();

                $table->index(['admin_id', 'shop_id', 'created_at']);
                $table->index(['admin_id', 'shop_id', 'invoice']);
            });
        }

        if (! Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name', 16);
                $table->string('amount', 16);
                $table->string('details', 110)->nullable();
                $table->integer('type')->index();
                $table->integer('user_id')->index();
                $table->integer('admin_id')->index();
                $table->timestamps();
                $table->integer('shop_id')->nullable()->index();
                $table->integer('store_id')->nullable();
                $table->string('user_name')->nullable();
                $table->string('date')->nullable();
                $table->string('attachment')->nullable();
                $table->string('category')->nullable();
                $table->integer('sync')->nullable();
                $table->string('paid_from')->nullable();
                $table->text('project')->nullable();
                $table->string('VAT')->nullable();

                $table->index(['admin_id', 'shop_id']);
            });
        }

        if (! Schema::hasTable('lenders')) {
            Schema::create('lenders', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name', 110);
                $table->string('phone', 110)->nullable();
                $table->integer('dealer')->default(0)->index();
                $table->integer('type')->default(2)->index();
                $table->string('email', 110)->nullable();
                $table->integer('admin_id')->index();
                $table->integer('shop_id')->nullable()->index();
                $table->string('address', 210)->nullable();
                $table->string('location', 100)->nullable();
                $table->string('details', 110)->nullable();
                $table->date('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->index(['admin_id', 'shop_id', 'dealer']);
            });
        }

        if (! Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('reference')->nullable()->index();
                $table->string('gl_type')->nullable();
                $table->double('open_balance')->nullable()->default(0);
                $table->double('balance')->nullable()->default(0);
                $table->string('date')->nullable();
                $table->double('amount')->nullable()->default(0);
                $table->string('crdr')->nullable()->default('CR');
                $table->integer('shop_id')->nullable()->index();
                $table->string('details')->nullable();
                $table->integer('user_id')->nullable();
                $table->integer('admin_id')->nullable()->index();
                $table->dateTime('created_at')->nullable();
                $table->string('name')->nullable()->index();
                $table->string('type')->nullable();
                $table->string('acc_type')->nullable();

                $table->index(['admin_id', 'name', 'id']);
            });
        }

        if (! Schema::hasTable('lender_statements')) {
            Schema::create('lender_statements', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('lender_id')->nullable()->index();
                $table->integer('type')->nullable();
                $table->integer('is_collection')->nullable()->default(0);
                $table->string('paydesc')->nullable();
                $table->string('details')->nullable();
                $table->string('amount')->nullable();
                $table->date('date')->nullable();
                $table->integer('admin_id')->nullable()->index();
                $table->integer('shop_id')->nullable()->index();
                $table->integer('user_id')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->string('reference')->nullable()->index();
            });
        }

        if (! Schema::hasTable('rate_limit_block')) {
            Schema::create('rate_limit_block', function (Blueprint $table): void {
                $table->id();
                $table->string('key_type')->index();
                $table->string('key_value')->index();
                $table->unsignedBigInteger('blocked_until')->default(0);
                $table->integer('current_penalty')->default(0);
                $table->dateTime('created_at')->nullable();

                $table->unique(['key_type', 'key_value']);
            });
        }

        if (! Schema::hasTable('rate_limit_block_log')) {
            Schema::create('rate_limit_block_log', function (Blueprint $table): void {
                $table->id();
                $table->string('key_type')->index();
                $table->string('key_value')->index();
                $table->integer('penalty_level')->default(0);
                $table->unsignedBigInteger('blocked_until')->default(0);
                $table->string('reason')->nullable();
                $table->dateTime('timestamp')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_limit_block_log');
        Schema::dropIfExists('rate_limit_block');
        Schema::dropIfExists('lender_statements');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('lenders');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('shopings');
        Schema::dropIfExists('stock_variants');
        Schema::dropIfExists('stock');
        Schema::dropIfExists('units');
        Schema::dropIfExists('shops');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('admins');
    }
};
