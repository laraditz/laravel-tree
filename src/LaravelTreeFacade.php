<?php

namespace Laraditz\LaravelTree;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Laraditz\LaravelTree\Skeleton\SkeletonClass
 */
class LaravelTreeFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravelTree';
    }
}
