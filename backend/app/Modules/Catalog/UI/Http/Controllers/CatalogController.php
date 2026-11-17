<?php

declare(strict_types=1);

namespace App\Modules\Catalog\UI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Application\Actions\CatalogQueryActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogQueryActions $catalogQueries,
    ) {}

    public function categories(): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->catalogQueries->listCategories(),
        ], 200);
    }

    public function products(Request $request): JsonResponse
    {
        $sort = (string) $request->query('sort', 'featured');
        if (! in_array($sort, ['featured', 'price_asc', 'price_desc', 'newest'], true)) {
            $sort = 'featured';
        }

        $items = $this->catalogQueries->listProducts(
            search: $request->query('q') !== null ? (string) $request->query('q') : null,
            category: $request->query('category') !== null ? (string) $request->query('category') : null,
            sort: $sort,
        );

        return new JsonResponse(['data' => $items], 200);
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->catalogQueries->getProductBySlug($slug);

        if ($product === null) {
            return new JsonResponse([
                'message' => 'Không tìm thấy sản phẩm.',
            ], 404);
        }

        return new JsonResponse(['data' => $product], 200);
    }
}
