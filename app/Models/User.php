<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    protected ?array $roleNamesCache = null;

    protected ?array $permissionNamesCache = null;

    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Role names held by the user (memoized per instance).
     */
    protected function roleNames(): array
    {
        return $this->roleNamesCache ??= $this->roles()->pluck('name')->all();
    }

    /**
     * Permission names granted through the user's roles (memoized per instance).
     * Super admins implicitly hold every permission.
     */
    public function permissionNames(): array
    {
        if ($this->permissionNamesCache !== null) {
            return $this->permissionNamesCache;
        }

        if (in_array('super_admin', $this->roleNames(), true)) {
            return $this->permissionNamesCache = Permission::pluck('name')->all();
        }

        return $this->permissionNamesCache = Permission::whereHas('roles', function ($query) {
            $query->whereIn('roles.id', $this->roles()->pluck('roles.id'));
        })->pluck('name')->all();
    }

    /**
     * Clear memoized role/permission lookups after roles change.
     */
    public function flushAccessCache(): void
    {
        $this->roleNamesCache = null;
        $this->permissionNamesCache = null;
        $this->unsetRelation('roles');
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string|array $roles): bool
    {
        return $this->hasAnyRole((array) $roles);
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return count(array_intersect($roles, $this->roleNames())) > 0;
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles(array $roles): bool
    {
        return count(array_diff($roles, $this->roleNames())) === 0;
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissionNames(), true);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return count(array_intersect($permissions, $this->permissionNames())) > 0;
    }

    /**
     * Whether this user may assign the given role: super admins may assign any,
     * others only roles whose permissions they already hold (never super_admin).
     */
    public function canGrantRole(Role $role): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($role->name === 'super_admin') {
            return false;
        }

        return count(array_diff($role->permissions()->pluck('name')->all(), $this->permissionNames())) === 0;
    }

    /**
     * Whether this user may modify the target admin account (no outranking).
     */
    public function canManageUser(User $target): bool
    {
        if ($this->isSuperAdmin() || $target->id === $this->id) {
            return true;
        }

        if ($target->isSuperAdmin()) {
            return false;
        }

        return count(array_diff($target->permissionNames(), $this->permissionNames())) === 0;
    }

    /**
     * Whether this is the only active super admin.
     */
    public function isLastActiveSuperAdmin(): bool
    {
        if (!$this->isSuperAdmin()) {
            return false;
        }

        return !static::active()
            ->where('id', '!=', $this->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->exists();
    }

    /**
     * Assign role to user
     */
    public function assignRole(Role|string $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching([$role->id]);
        $this->flushAccessCache();
    }

    /**
     * Remove role from user
     */
    public function removeRole(Role|string $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->detach($role->id);
        $this->flushAccessCache();
    }

    /**
     * Sync roles for user
     */
    public function syncRoles(array $roles): void
    {
        $roleIds = Role::whereIn('name', $roles)->pluck('id')->toArray();
        $this->roles()->sync($roleIds);
        $this->flushAccessCache();
    }

    /**
     * Check if user is a super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Check if user is an admin (any admin role)
     */
    public function isAdmin(): bool
    {
        return $this->roles()->exists();
    }

    /**
     * Scope to only active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only inactive users
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to only admin users (users with roles)
     */
    public function scopeAdmins($query)
    {
        return $query->whereHas('roles');
    }
}
