<?php

namespace AdityaDarma\LaravelDuitku\Excepstions;

use Exception;

class TransactionNotFoundException extends Exception
{
    protected $message = 'Transaction not found.';
}