<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductFilterRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Services\CatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    public function __construct(private CatalogService $catalog)
    {
    }

    public function products(ProductFilterRequest $request): AnonymousResourceCollection
    {
        return ProductResource::collection($this->catalog->products($request->validated()));
    }

    public function product(int $product): ProductResource
    {
        return new ProductResource($this->catalog->product($product));
    }

    public function categories(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->catalog->categories());
    }
}
