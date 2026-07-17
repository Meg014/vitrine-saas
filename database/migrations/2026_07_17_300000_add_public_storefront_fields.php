<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $t): void {
            $t->string('hero_title')->nullable();
            $t->string('hero_subtitle')->nullable();
            $t->string('hero_image_path')->nullable();
            $t->string('hero_button_text')->nullable();
            $t->string('hero_button_url')->nullable();
            $t->string('about_title')->nullable();
            $t->text('about_text')->nullable();
        });
        Schema::table('customers', function (Blueprint $t): void {
            $t->string('password')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->rememberToken();
        });
        Schema::table('customer_messages', function (Blueprint $t): void {
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_messages', fn (Blueprint $t) => $t->dropConstrainedForeignId('product_id'));
        Schema::table('customers', fn (Blueprint $t) => $t->dropColumn(['password', 'email_verified_at', 'remember_token']));
        Schema::table('store_settings', fn (Blueprint $t) => $t->dropColumn(['hero_title', 'hero_subtitle', 'hero_image_path', 'hero_button_text', 'hero_button_url', 'about_title', 'about_text']));
    }
};
