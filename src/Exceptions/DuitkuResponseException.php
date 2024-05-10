<?php

namespace AdityaDarma\LaravelDuitku\Exceptions;

use Exception;

class DuitkuResponseException extends Exception
{
    protected $message = 'Error response code http.';
}
