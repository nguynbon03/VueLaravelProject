<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordering_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number', 64)->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('status', 32)->index();
            $table->string('payment_status', 32)->index();
            $table->string('payment_method', 32);
            $table->unsignedBigInteger('subtotal_minor');
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('shipping_fee_minor')->default(0);
            $table->unsignedBigInteger('grand_total_minor');
            $table->string('currency', 10)->default('VND');
            $table->string('coupon_code', 50)->nullable();
            $table->string('customer_name', 150);
            $table->string('customer_email', 150);
            $table->string('customer_phone', 50);
            $table->string('shipping_address', 500);
            $table->string('shipping_province', 120)->nullable();
            $table->text('note')->nullable();
            $table->json('payment_instruction')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ordering_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('ordering_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('variant_id')->index();
            $table->string('sku', 100);
            $table->string('product_name', 255);
            $table->string('variant_label', 150);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_minor');
            $table->unsignedBigInteger('line_total_minor');
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number', 64)->index();
            $table->string('provider', 50);
            $table->string('external_event_id', 191)->nullable();
            $table->string('transaction_ref', 191)->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 10)->default('VND');
            $table->string('status', 32);
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('ordering_order_lines');
        Schema::dropIfExists('ordering_orders');
    }
};
