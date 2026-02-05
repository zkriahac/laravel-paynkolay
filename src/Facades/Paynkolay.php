<?php

namespace Zkriahac\Paynkolay\Facades;

use Illuminate\Support\Facades\Facade;

class Paynkolay extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'paynkolay';
    }
}