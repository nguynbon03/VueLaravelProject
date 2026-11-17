<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_reviews', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name', 150);
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->boolean('is_verified_purchase')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_reviews');
    }
};
