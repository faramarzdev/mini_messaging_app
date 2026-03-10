<?php

namespace App\Models\Concerns;

trait HasRole
{
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    public function assignRole(string $role): bool
    {
        return $this->update(['role' => $role]);
    }
}
