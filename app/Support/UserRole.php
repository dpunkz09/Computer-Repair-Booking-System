<?php

namespace App\Support;

class UserRole
{
    public const ADMIN = 'admin';

    public const DEMO_ADMIN = 'demo_admin';

    public const TECHNICIAN = 'technician';

    public const CUSTOMER = 'customer';

    /** @var array<int, string> */
    public const ALL = [
        self::ADMIN,
        self::DEMO_ADMIN,
        self::TECHNICIAN,
        self::CUSTOMER,
    ];

    /** @var array<int, string> */
    public const ADMIN_PANEL = [
        self::ADMIN,
        self::DEMO_ADMIN,
    ];

    public static function label(string $role): string
    {
        return match ($role) {
            self::DEMO_ADMIN => 'Demo Admin',
            self::ADMIN => 'Admin',
            self::TECHNICIAN => 'Technician',
            default => 'Customer',
        };
    }
}
