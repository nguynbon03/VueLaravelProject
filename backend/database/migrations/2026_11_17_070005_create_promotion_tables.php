<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label', 255);
            $table->string('type', 32); // percentage | fixed | freeship
            $table->unsignedBigInteger('value')->default(0);
            $table->unsignedBigInteger('max_discount_minor')->nullable();
            $table->unsignedBigInteger('min_subtotal_minor')->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_coupons');
    }
};
