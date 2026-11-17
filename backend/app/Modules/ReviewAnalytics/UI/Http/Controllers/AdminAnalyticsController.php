<?php

declare(strict_types=1);

namespace App\Modules\ReviewAnalytics\UI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ReviewAnalytics\Application\Actions\GetAdminAnalyticsOverviewAction;
use Illuminate\Http\JsonResponse;

final class AdminAnalyticsController extends Controller
{
    public function __construct(
        private readonly GetAdminAnalyticsOverviewAction $overviewAction,
    ) {}

    public function overview(): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->overviewAction->execute(),
        ], 200);
    }
}
