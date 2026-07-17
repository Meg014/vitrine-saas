<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', fn (Blueprint $t) => $t->unsignedBigInteger('next_order_number')->default(1));
        Schema::table('store_settings', function (Blueprint $t): void {
            $t->boolean('pickup_enabled')->default(true);
            $t->text('pickup_instructions')->nullable();
            $t->boolean('local_delivery_enabled')->default(false);
            $t->unsignedBigInteger('local_delivery_fee')->default(0);
            $t->text('local_delivery_instructions')->nullable();
            $t->boolean('shipping_enabled')->default(false);
            $t->unsignedBigInteger('default_shipping_fee')->default(0);
            $t->text('shipping_instructions')->nullable();
            $t->unsignedBigInteger('minimum_order_amount')->nullable();
            $t->json('payment_methods')->nullable();
        });
        Schema::create('orders', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('store_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('cart_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedBigInteger('sequence_number');
            $t->string('number');
            $t->uuid('checkout_token');
            $t->string('status', 30)->index();
            $t->string('payment_status', 30)->index();
            $t->string('fulfillment_status', 30)->index();
            $t->string('payment_method', 30);
            $t->string('shipping_method', 30);
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('shipping_amount')->default(0);
            $t->unsignedBigInteger('discount_amount')->default(0);
            $t->unsignedBigInteger('total');
            $t->string('currency', 3)->default('BRL');
            $t->text('customer_notes')->nullable();
            $t->text('internal_notes')->nullable();
            $t->string('customer_name');
            $t->string('customer_email');
            $t->string('customer_phone')->nullable();
            $t->string('customer_document')->nullable();
            $t->string('tracking_code')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('confirmed_at')->nullable();
            $t->timestamp('canceled_at')->nullable();
            $t->timestamp('stock_restored_at')->nullable();
            $t->timestamps();
            $t->unique(['store_id', 'sequence_number']);
            $t->unique(['store_id', 'number']);
            $t->unique(['store_id', 'checkout_token']);
            $t->index(['store_id', 'created_at']);
        });
        Schema::create('order_items', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $t->string('product_name');
            $t->string('variant_name')->nullable();
            $t->string('sku')->nullable();
            $t->json('attributes')->nullable();
            $t->unsignedInteger('quantity');
            $t->unsignedBigInteger('unit_price');
            $t->unsignedBigInteger('subtotal');
            $t->timestamps();
        });
        Schema::create('order_addresses', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('type')->default('shipping');
            $t->string('recipient_name');
            $t->string('postal_code');
            $t->string('street');
            $t->string('number');
            $t->string('complement')->nullable();
            $t->string('neighborhood');
            $t->string('city');
            $t->string('state', 2);
            $t->string('country', 2)->default('BR');
            $t->string('phone')->nullable();
            $t->timestamps();
        });
        Schema::create('order_status_histories', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->string('from_status')->nullable();
            $t->string('to_status');
            $t->text('comment')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::table('store_settings', fn (Blueprint $t) => $t->dropColumn(['pickup_enabled', 'pickup_instructions', 'local_delivery_enabled', 'local_delivery_fee', 'local_delivery_instructions', 'shipping_enabled', 'default_shipping_fee', 'shipping_instructions', 'minimum_order_amount', 'payment_methods']));
        Schema::table('stores', fn (Blueprint $t) => $t->dropColumn('next_order_number'));
    }
};
