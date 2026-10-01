<?php

namespace Tests;

use App\Domain\Firm\FirmContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(FirmContext::class)->forget();
    }
}
