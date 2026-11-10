<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_filters_hidden_products_and_eager_loads_category(): void
    {
        Product::factory()->create(['price' => 1500]);
        Product::factory()->create(['is_active' => false]);
        Product::factory()->create(['price' => 500]);
        $results = app(CatalogRepositoryInterface::class)->products(['min_price' => 1000]);
        $this->assertSame(1, $results->total());
        $this->assertTrue($results->first()->relationLoaded('category'));
    }

    public function test_all_repository_interfaces_resolve(): void
    {
        foreach (['User', 'Catalog', 'Cart', 'Order', 'Engagement'] as $name) {
            $contract = "App\\Repositories\\Contracts\\{$name}RepositoryInterface";
            $this->assertInstanceOf($contract, app($contract));
        }
    }
}
