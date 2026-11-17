<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('catalog_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('catalog_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('brand');
            $table->text('short_description');
            $table->longText('description');
            $table->string('image_url', 500);
            $table->string('currency', 10)->default('VND');
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('catalog_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('catalog_products')->cascadeOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('color', 100);
            $table->string('size', 50);
            $table->unsignedBigInteger('price_minor')->index();
            $table->unsignedBigInteger('compare_at_minor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_variants');
        Schema::dropIfExists('catalog_products');
        Schema::dropIfExists('catalog_categories');
    }
};
