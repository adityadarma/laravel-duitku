<?php

namespace AdityaDarma\LaravelDuitku\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static static config(string $merchantCode, string $apiKey)
 * @method static object createTransaction(array $data)
 * @method static object getNotificationTransaction()
 *
 * @see \AdityaDarma\LaravelDuitku\LaravelDuitku
 */
class DuitkuPOP extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-duitku-pop';
    }
}
