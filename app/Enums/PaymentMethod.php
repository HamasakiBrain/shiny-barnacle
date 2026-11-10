<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case Mock = 'mock';
}
