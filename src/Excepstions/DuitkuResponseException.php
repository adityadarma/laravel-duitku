<?php

namespace AdityaDarma\LaravelDuitku\Excepstions;

use Exception;

class DuitkuResponseException extends Exception
{
    protected $message = 'Error response code http.';
}