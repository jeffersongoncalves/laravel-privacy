<?php

namespace JeffersonGoncalves\Privacy\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\Privacy\Privacy
 */
class Privacy extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-privacy';
    }
}
