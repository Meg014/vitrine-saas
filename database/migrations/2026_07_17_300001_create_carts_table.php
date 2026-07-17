<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('store_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->string('session_id')->nullable();
            $t->string('status', 20)->index();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
            $t->index(['store_id', 'session_id', 'status']);
            $t->index(['store_id', 'customer_id', 'status']);
        });
        Schema::create('cart_items', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $t->unsignedInteger('quantity');
            $t->unsignedBigInteger('unit_price');
            $t->unsignedBigInteger('promotional_unit_price')->nullable();
            $t->string('product_name_snapshot');
            $t->string('variant_name_snapshot')->nullable();
            $t->string('sku_snapshot')->nullable();
            $t->timestamps();
            $t->unique(['cart_id', 'product_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
