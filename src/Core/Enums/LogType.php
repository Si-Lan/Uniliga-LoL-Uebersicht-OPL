<?php

namespace App\Core\Enums;

enum LogType: string {
    case DB = 'db';
    case DEFAULT = 'default';
    case ADMIN_UPDATE = 'admin_update';
    case USER_UPDATE = 'user_update';
    case CRON_UPDATE = 'cron_update';
    case DDRAGON_UPDATE = 'ddragon_update';
}