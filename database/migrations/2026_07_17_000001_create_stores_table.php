<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('document', 30)->nullable()->index();
            $table->string('status', 20)->index();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('store_user', function (Blueprint $table): void {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->index();
            $table->timestamps();
            $table->primary(['store_id', 'user_id']);
        });

        Schema::create('store_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('primary_color', 7)->default('#7c3aed');
            $table->string('secondary_color', 7)->default('#1e1b4b');
            $table->string('accent_color', 7)->default('#f59e0b');
            $table->string('font_family')->default('Inter');
            $table->string('currency', 3)->default('BRL');
            $table->string('locale', 10)->default('pt_BR');
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('store_user');
        Schema::dropIfExists('stores');
    }
};
