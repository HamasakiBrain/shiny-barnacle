<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = config('database.default');
        $database = config("database.connections.$connection.database");

        if ($database !== ':memory:' && !str_ends_with((string) $database, '_testing')) {
            throw new \RuntimeException('Tests may only use :memory: or a database ending in _testing.');
        }

        return $app;
    }
}
