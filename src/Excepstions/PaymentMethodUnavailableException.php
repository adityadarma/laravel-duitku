<?php

namespace AdityaDarma\LaravelDuitku\Excepstions;

use Exception;

class PaymentMethodUnavailableException extends Exception
{
    protected $message = 'Error payment method unavailabe';
}