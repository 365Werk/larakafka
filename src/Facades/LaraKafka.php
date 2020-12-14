<?php

namespace Werk365\LaraKafka\Facades;

use Illuminate\Support\Facades\Facade;

class LaraKafka extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'larakafka';
    }
}
