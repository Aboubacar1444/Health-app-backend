<?php

namespace App\Utils;

enum NotificationType: string
{
    case SYSTEM = 'SYSTEM';
    case APPOINTMENT = 'APPOINTMENT';
    case REMINDER = 'REMINDER';
    case MESSAGE = 'MESSAGE';
    case ALERT = 'ALERT';
    case PROMOTION = 'PROMOTION';
}
