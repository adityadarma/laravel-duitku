<?php

namespace AdityaDarma\LaravelDuitku\Enums;

enum PaymentCode: string
{
    public const Success = '00';
    public const Pending = '01';
    public const Failed = '02';

    public function codeName(string $code): string
    {
        return match ($code) {
            '00' => 'Success',
            '01' => 'Pending',
            '02' => 'Failed',
        };
    }
}