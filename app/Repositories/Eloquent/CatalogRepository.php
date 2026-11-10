<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Models\Product;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CatalogRepository implements CatalogRepositoryInterface
{
    public function products(array $filters): LengthAwarePaginator
    {
        return Product::with('category')
            ->withAvg('reviews', 'rating')
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where('name', 'like', '%' . $value . '%'))
            ->when($filters['category_id'] ?? null, fn ($q, $value) => $q->where('category_id', $value))
            ->when(isset($filters['min_price']), fn ($q) => $q->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn ($q) => $q->where('price', '<=', $filters['max_price']))
            ->orderBy($filters['sort'] ?? 'id', $filters['direction'] ?? 'asc')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function product(int $id): Product
    {
        return Product::with('category')
            ->withAvg('reviews', 'rating')
            ->findOrFail($id);
    }

    public function categories(): Collection
    {
        return Category::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function category(int $id): Category
    {
        return Category::findOrFail($id);
    }

    public function saveProduct(array $data, ?Product $product = null): Product
    {
        $product ??= new Product();
        $product->fill($data)->save();

        return $product->load('category');
    }

    public function saveCategory(array $data, ?Category $category = null): Category
    {
        $category ??= new Category();
        $category->fill($data)->save();

        return $category;
    }

    public function lockProducts(array $ids): Collection
    {
        return Product::with('category')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    public function stock(Product $product, int $delta): void
    {
        $product->increment('stock', $delta);
    }
}
