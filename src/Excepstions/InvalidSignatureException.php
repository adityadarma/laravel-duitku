<?php

namespace AdityaDarma\LaravelDuitku\Excepstions;

use Exception;

class InvalidSignatureException extends Exception
{
    protected $message = 'Error invalid signature.';
}