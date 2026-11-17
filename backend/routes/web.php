<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', static fn (): JsonResponse => new JsonResponse([
    'service' => 'CleanCommerce Headless REST API',
    'version' => '1.0.0',
    'health' => '/api/v1/health',
], 200));
