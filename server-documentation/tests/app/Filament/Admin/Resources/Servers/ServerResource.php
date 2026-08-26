<?php

namespace App\Filament\Admin\Resources\Servers;

/**
 * Test double for Pelican's ServerResource (App\Traits\Filament\CanCustomizeRelations).
 * Records what plugins register so provider wiring can be asserted.
 */
class ServerResource
{
    /** @var array<int, class-string> */
    public static array $customRelations = [];

    public static function registerCustomRelations(string ...$customRelations): void
    {
        static::$customRelations = array_merge(static::$customRelations, $customRelations);
    }
}
