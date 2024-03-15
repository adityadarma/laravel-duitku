<?php

namespace AdityaDarma\LaravelDuitku\Excepstions;

use Exception;

class MissingParamaterException extends Exception
{
    protected $message = 'Missing parameter data required.';
}