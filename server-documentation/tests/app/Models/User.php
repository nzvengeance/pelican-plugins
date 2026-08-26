<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Starter\ServerDocumentation\Database\Factories\UserFactory;

class User extends Model
{
    public function isRootAdmin(): bool
    {
        return $this->roles->contains('name', Role::ROOT_ADMIN);
    }

    public function can($ability, $arguments = []): bool
    {
        return false;
    }

    public static function factory()
    {
        return UserFactory::new();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }
}
