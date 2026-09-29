<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Starter\ServerDocumentation\Database\Factories\RoleFactory;

class Role extends Model
{
    protected $fillable = ['name'];

    public const ROOT_ADMIN = 'root_admin';

    /** @var array<int, string> */
    public static array $customDefaultPermissions = [];

    /** @var array<string, string|\BackedEnum> */
    public static array $customModelIcons = [];

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public static function factory()
    {
        return RoleFactory::new();
    }

    /**
     * Test double for App\Models\Role::registerCustomDefaultPermissions().
     */
    public static function registerCustomDefaultPermissions(string $model): void
    {
        static::$customDefaultPermissions[] = $model;
    }

    /**
     * Test double for App\Models\Role::registerCustomModelIcon().
     */
    public static function registerCustomModelIcon(string $model, string|\BackedEnum $icon): void
    {
        static::$customModelIcons[$model] = $icon;
    }
}
