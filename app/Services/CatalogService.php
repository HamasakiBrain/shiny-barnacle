<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CatalogService
{
    public function __construct(private CatalogRepositoryInterface $catalog)
    {
    }

    public function products(array $filters): LengthAwarePaginator
    {
        return $this->catalog->products($filters);
    }

    public function product(int $id): Product
    {
        $product = $this->catalog->product($id);
        abort_unless($product->is_active && $product->category->is_active, 404);

        return $product;
    }

    public function categories(): Collection
    {
        return $this->catalog->categories();
    }

    public function saveProduct(array $data, ?int $id = null): Product
    {
        return $this->catalog->saveProduct($data, $id ? $this->catalog->product($id) : null);
    }

    public function saveCategory(array $data, ?int $id = null): Category
    {
        return $this->catalog->saveCategory($data, $id ? $this->catalog->category($id) : null);
    }

    public function deactivateProduct(int $id): void
    {
        $this->saveProduct(['is_active' => false], $id);
    }

    public function deactivateCategory(int $id): void
    {
        $this->saveCategory(['is_active' => false], $id);
    }
}
