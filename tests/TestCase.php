<?php

namespace JeffersonGoncalves\Privacy\Tests;

use JeffersonGoncalves\Privacy\PrivacyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PrivacyServiceProvider::class,
        ];
    }
}
