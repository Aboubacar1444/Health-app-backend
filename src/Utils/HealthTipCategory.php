<?php

namespace App\Utils;

enum HealthTipCategory: string
{
    case NUTRITION = 'NUTRITION';
    case EXERCISE = 'EXERCISE';
    case MENTAL_HEALTH = 'MENTAL_HEALTH';
    case PREVENTION = 'PREVENTION';
    case CHRONIC_DISEASES = 'CHRONIC_DISEASES';
    case MATERNAL_HEALTH = 'MATERNAL_HEALTH';
    case CHILD_HEALTH = 'CHILD_HEALTH';
    case ELDERLY_CARE = 'ELDERLY_CARE';
    case GENERAL = 'GENERAL';
}