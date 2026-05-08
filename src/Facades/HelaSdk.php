<?php

namespace Ometra\HelaSdk\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array config()
 * @method static \Ometra\HelaSdk\Clients\AusterClient auster()
 * @method static string baseUrl()
 * @method static string|null apiKey()
 * @method static \Illuminate\Http\Client\PendingRequest http()
 * @method static \Illuminate\Http\Client\Response get(string $uri, array $query = [])
 * @method static \Illuminate\Http\Client\Response post(string $uri, array $data = [])
 *
 * @see \Ometra\HelaSdk\HelaSdk
 */
class HelaSdk extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Ometra\HelaSdk\HelaSdk::class;
    }
}
