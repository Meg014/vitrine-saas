<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('store_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone', 20)->nullable();
            $t->string('document', 20)->nullable();
            $t->date('birth_date')->nullable();
            $t->text('notes')->nullable();
            $t->string('status', 20)->index();
            $t->boolean('accepts_marketing')->default(false)->index();
            $t->timestamp('last_purchase_at')->nullable();
            $t->timestamps();
            $t->unique(['store_id', 'email']);
            $t->unique(['store_id', 'document']);
            $t->index(['store_id', 'name']);
        });
        Schema::create('customer_addresses', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->string('label')->nullable();
            $t->string('recipient_name');
            $t->string('postal_code', 12);
            $t->string('street');
            $t->string('number', 30);
            $t->string('complement')->nullable();
            $t->string('neighborhood');
            $t->string('city');
            $t->string('state', 2);
            $t->string('country', 2)->default('BR');
            $t->string('phone', 20)->nullable();
            $t->boolean('is_default')->default(false)->index();
            $t->timestamps();
        });
        Schema::create('customer_messages', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('store_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone', 20)->nullable();
            $t->string('subject')->nullable();
            $t->text('message');
            $t->string('status', 20)->index();
            $t->string('source', 30)->index();
            $t->timestamp('read_at')->nullable();
            $t->timestamp('replied_at')->nullable();
            $t->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['store_id', 'created_at']);
        });
        Schema::create('customer_message_replies', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('customer_message_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->text('message');
            $t->boolean('is_internal')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_message_replies');
        Schema::dropIfExists('customer_messages');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
