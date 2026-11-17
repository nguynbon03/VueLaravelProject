<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('variant_id')->unique();
            $table->integer('available_stock')->default(0)->index();
            $table->integer('reserved_stock')->default(0);
            $table->integer('sold_stock')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->timestamps();
        });

        Schema::create('inventory_reservations', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number', 64)->index();
            $table->unsignedBigInteger('variant_id')->index();
            $table->unsignedInteger('quantity');
            $table->string('status', 32)->default('reserved')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('variant_id')->index();
            $table->integer('delta');
            $table->integer('balance_after');
            $table->string('reason', 255);
            $table->string('reference_type', 64)->nullable();
            $table->string('reference_id', 64)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_reservations');
        Schema::dropIfExists('inventory_stocks');
    }
};
