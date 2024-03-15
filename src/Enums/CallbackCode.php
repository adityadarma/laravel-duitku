<?php

namespace AdityaDarma\LaravelDuitku\Enums;

enum CallbackCode: string
{
    public const Success = '00';
    public const Failed = '01';

    public function codeName(string $code): string
    {
        return match ($code) {
            '00' => 'Success',
            '01' => 'Failed',
        };
    }
}