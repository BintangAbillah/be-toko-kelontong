<?php

namespace App\Enums;

enum UserRole: string
{
    case ShopOwner = 'shop_owner';
    case ShopKeeper = 'shop_keeper';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::ShopOwner => 'Shop Owner',
            self::ShopKeeper => 'Shop Keeper',
            self::Customer => 'Customer',
        };
    }
}
