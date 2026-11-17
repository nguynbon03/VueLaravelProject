<?php

declare(strict_types=1);

namespace Tests\Feature;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class DatabaseErrorMaskingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->get('/api/v1/__test-db-failure', function (): never {
            throw new QueryException(
                'pgsql',
                'select * from "users" where "email" = ?',
                ['admin@cleancommerce.vn'],
                new Exception('SQLSTATE[42P01]: Undefined table: relation "users" does not exist'),
                ['host' => 'pgbouncer', 'port' => 6432, 'database' => 'ecommerce'],
            );
        });
    }

    public function test_api_hides_sql_and_connection_details_even_when_debug_is_enabled(): void
    {
        config(['app.debug' => true]);

        $response = $this->getJson('/api/v1/__test-db-failure');

        $response->assertStatus(500)
            ->assertJsonStructure(['message', 'reference']);

        $body = $response->getContent();
        foreach (['SQLSTATE', 'select', 'pgbouncer', 'ecommerce', 'users', 'admin@cleancommerce.vn', 'trace', 'file'] as $leak) {
            $this->assertStringNotContainsStringIgnoringCase($leak, (string) $body, "Leaked: {$leak}");
        }
    }

    public function test_validation_errors_are_still_returned_normally(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }
}
