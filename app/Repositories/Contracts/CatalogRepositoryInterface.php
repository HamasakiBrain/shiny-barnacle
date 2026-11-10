<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CatalogRepositoryInterface
{
    public function products(array $filters): LengthAwarePaginator;

    public function product(int $id): Product;

    public function categories(): Collection;

    public function category(int $id): Category;

    public function saveProduct(array $data, ?Product $product = null): Product;

    public function saveCategory(array $data, ?Category $category = null): Category;

    public function lockProducts(array $ids): Collection;

    public function stock(Product $product, int $delta): void;
}
