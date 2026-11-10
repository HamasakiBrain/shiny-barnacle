<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AdminCatalogController extends Controller
{
    public function __construct(private CatalogService $catalog)
    {
    }

    public function storeProduct(ProductRequest $request): JsonResponse
    {
        return (new ProductResource($this->catalog->saveProduct($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function updateProduct(ProductRequest $request, int $product): ProductResource
    {
        return new ProductResource($this->catalog->saveProduct($request->validated(), $product));
    }

    public function destroyProduct(int $product): Response
    {
        $this->catalog->deactivateProduct($product);

        return response()->noContent();
    }

    public function storeCategory(CategoryRequest $request): JsonResponse
    {
        return (new CategoryResource($this->catalog->saveCategory($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function updateCategory(CategoryRequest $request, int $category): CategoryResource
    {
        return new CategoryResource($this->catalog->saveCategory($request->validated(), $category));
    }

    public function destroyCategory(int $category): Response
    {
        $this->catalog->deactivateCategory($category);

        return response()->noContent();
    }
}
