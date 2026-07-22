<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Employee = 'employee';
    case ApiConsumer = 'api_consumer';
}
