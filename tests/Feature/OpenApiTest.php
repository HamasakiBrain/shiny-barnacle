<?php

namespace Tests\Feature;

use cebe\openapi\Reader;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OpenApiTest extends TestCase
{
    public function test_spec_is_valid_and_covers_every_api_operation(): void
    {
        $spec = Reader::readFromJsonFile(public_path('openapi.json'));
        $this->assertTrue($spec->validate(), json_encode($spec->getErrors()));
        $document = json_decode(file_get_contents(public_path('openapi.json')), true, 512, JSON_THROW_ON_ERROR);
        $actual = [];

        foreach (Route::getRoutes() as $route) {
            if (!str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }

            foreach ($route->methods() as $method) {
                if ($method === 'HEAD') {
                    continue;
                }

                $path = '/' . substr($route->uri(), 7);
                $actual[] = strtolower($method) . ' ' . $path;
                $this->assertArrayHasKey(strtolower($method), $document['paths'][$path] ?? [], $method . ' ' . $path);
            }
        }

        $documented = [];

        foreach ($document['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                $documented[] = $method . ' ' . $path;
            }
        }
        sort($actual);
        sort($documented);
        $this->assertSame($actual, $documented);
    }

    public function test_swagger_ui_is_served(): void
    {
        $this->get('/docs')
            ->assertOk()
            ->assertSee('SwaggerUIBundle');
    }
}
