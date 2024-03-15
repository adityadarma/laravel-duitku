<?php

namespace AdityaDarma\LaravelDuitku\Enums;

enum ResponseCode: string
{
    public const Success = '00';

    public function codeName(string $code): string
    {
        return match ($code) {
            '00' => 'Success',
        };
    }
}