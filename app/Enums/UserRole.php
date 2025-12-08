<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Moderator = 'moderator';
    case User = 'user';

    public function label(): string
    {
        return match($this) {
            self::Admin => 'Administrator',
            self::Manager => 'Manager',
            self::Moderator => 'Moderator',
            self::User => 'User',
        };
    }
}
