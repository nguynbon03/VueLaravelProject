<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('aggregate_type', 100);
            $table->string('aggregate_id', 100)->index();
            $table->string('event_type', 150)->index();
            $table->json('payload');
            $table->timestamp('dispatched_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('inbox_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 50);
            $table->string('external_event_id', 191);
            $table->json('payload');
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->unique(['provider', 'external_event_id']);
        });

        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 191);
            $table->string('method', 16);
            $table->string('path', 255);
            $table->string('request_hash', 64);
            $table->unsignedSmallInteger('status_code');
            $table->json('response_body');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['key', 'method', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('inbox_messages');
        Schema::dropIfExists('outbox_messages');
    }
};
