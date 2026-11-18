<?php

namespace JeffersonGoncalves\Privacy;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PrivacyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-privacy')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
