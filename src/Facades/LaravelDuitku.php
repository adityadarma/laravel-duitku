<?php

namespace AdityaDarma\LaravelDuitku\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static object getPaymentMethod(int $paymentAmount)
 * @method static object createTransaction(array $data)
 * @method static object checkTransactionStatus(string $merchantOrderId)
 * @method static object getNotificationTransaction()
 *
 * @see \AdityaDarma\LaravelDuitku\LaravelDuitku
 */
class LaravelDuitku extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'laravel-duitku';
    }
}