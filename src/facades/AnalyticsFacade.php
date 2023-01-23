<?php

namespace zfhassaan\genlytics\facades;

use \Illuminate\Support\Facades\Facade;

/**
 * @see \zfhassaan\genlytics\Analytics
 */
class AnalyticsFacade extends Facade
{

    /**
     * Get the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'genlytics';
    }
}
