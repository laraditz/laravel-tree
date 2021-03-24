<?php

namespace Laraditz\LaravelTree\Tests;

use Orchestra\Testbench\TestCase;
use Laraditz\LaravelTree\LaravelTreeServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [LaravelTreeServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
