<?php

namespace App\Utils;

enum RevieweeType: string
{
    case DOCTOR = 'DOCTOR';
    case ESTABLISHMENT = 'ESTABLISHMENT';
    case PHARMACY = 'PHARMACY';
}