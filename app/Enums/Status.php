<?php

namespace App\Enums;

enum Status: int
{
    case ACTIVE = 1;
    case INACTIVE = 2;
    case BANNED = 3;

    public function title(): string
    {
        return match($this){
            self::INACTIVE => 'Inactive',
            self::ACTIVE => 'Active',
            self::BANNED => 'Banned',
        };
    }

    public function label(): string
    {
        return match($this){
            self::INACTIVE => 'Inactive',
            self::ACTIVE => 'Active',
            self::BANNED => 'Banned',
        };
    }
}
