<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    /**
     * Display a listing of admin users.
     */
    public function index(Request $request)
    {
        $query = User::with('roles')->admins();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $admins = $query->latest()->paginate(15);

        return view('admin.users.index', compact('admins'));
    }

    /**
     * Show the form for creating a new admin user.
     */
    public function create()
    {
        $roles = $this->grantableRoles();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created admin user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
            'is_active' => ['boolean'],
        ]);

        $this->ensureRolesGrantable($validated['roles']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Assign roles
        $user->roles()->attach($validated['roles']);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Admin user created successfully.');
    }

    /**
     * Display the specified admin user.
     */
    public function show(User $user)
    {
        $user->load('roles.permissions');
        $roles = $this->grantableRoles();
        return view('admin.users.show', compact('user', 'roles'));
    }

    /**
     * Show the form for editing the specified admin user.
     */
    public function edit(User $user)
    {
        $this->authorizeManage($user, 'edit');

        $roles = $this->grantableRoles();
        $user->load('roles');
        
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified admin user.
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeManage($user, 'edit');

        // Prevent users from editing themselves out of super admin
        // and the last active super admin from losing the role
        if ($user->isSuperAdmin()) {
            $requestedRoles = Role::whereIn('id', $request->roles ?? [])->pluck('name')->toArray();
            if (!in_array('super_admin', $requestedRoles)) {
                if ($user->id === auth()->id()) {
                    return back()->with('error', 'You cannot remove your own Super Admin role.');
                }
                if ($user->isLastActiveSuperAdmin()) {
                    return back()->with('error', 'You cannot remove the Super Admin role from the last active Super Admin.');
                }
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
            'is_active' => ['boolean'],
        ]);

        $this->ensureRolesGrantable($validated['roles']);

        $isActive = $request->boolean('is_active', true);
        if (!$isActive && $user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }
        if (!$isActive && $user->isLastActiveSuperAdmin()) {
            return back()->with('error', 'You cannot deactivate the last active Super Admin.');
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $isActive,
        ]);

        // Sync roles
        $user->roles()->sync($validated['roles']);
        $user->flushAccessCache();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Admin user updated successfully.');
    }

    /**
     * Remove the specified admin user.
     */
    public function destroy(User $user)
    {
        $this->authorizeManage($user, 'delete');

        // Prevent users from deleting themselves
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isLastActiveSuperAdmin()) {
            return back()->with('error', 'You cannot delete the last active Super Admin.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Admin user deleted successfully.');
    }

    /**
     * Toggle the active status of an admin user.
     */
    public function toggleStatus(User $user)
    {
        $this->authorizeManage($user, 'deactivate');

        // Prevent users from deactivating themselves
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->is_active && $user->isLastActiveSuperAdmin()) {
            return back()->with('error', 'You cannot deactivate the last active Super Admin.');
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Admin user {$status} successfully.");
    }

    /**
     * Show the form for resetting admin user password.
     */
    public function showResetPasswordForm(User $user)
    {
        $this->authorizeManage($user, 'reset the password of');

        return view('admin.users.reset-password', compact('user'));
    }

    /**
     * Reset the password for an admin user.
     */
    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeManage($user, 'reset the password of');

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Password reset successfully.');
    }

    /**
     * Assign a role to an admin user (quick action).
     */
    public function assignRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        $this->authorizeManage($user, 'modify');

        if (!auth()->user()->canGrantRole($role)) {
            return back()->with('error', 'You cannot assign a role with permissions you do not hold.');
        }

        if (!$user->hasRole($role->name)) {
            $user->roles()->attach($role->id);
            $user->flushAccessCache();
        }

        return back()->with('success', 'Role assigned successfully.');
    }

    /**
     * Revoke a role from an admin user (quick action).
     */
    public function revokeRole(User $user, Role $role)
    {
        $this->authorizeManage($user, 'modify');

        if (!$user->roles()->where('roles.id', $role->id)->exists()) {
            return back()->with('error', 'User does not have that role.');
        }

        if (!auth()->user()->canGrantRole($role)) {
            return back()->with('error', 'You cannot revoke a role with permissions you do not hold.');
        }

        if ($role->name === 'super_admin' && $user->isLastActiveSuperAdmin()) {
            return back()->with('error', 'You cannot remove the Super Admin role from the last active Super Admin.');
        }

        // Prevent users from removing their own Super Admin role
        if ($user->id === auth()->id() && $role->name === 'super_admin') {
            return back()->with('error', 'You cannot remove your own Super Admin role.');
        }

        $user->roles()->detach($role->id);
        $user->flushAccessCache();

        return back()->with('success', 'Role revoked successfully.');
    }

    /**
     * Abort unless the acting admin may manage the target account.
     */
    private function authorizeManage(User $user, string $action): void
    {
        abort_unless(auth()->user()->canManageUser($user), 403, "You cannot {$action} an account with higher privileges than yours.");
    }

    /**
     * Roles the acting admin is allowed to hand out.
     */
    private function grantableRoles()
    {
        $actor = auth()->user();

        return Role::with('permissions')->get()->filter(fn (Role $role) => $actor->canGrantRole($role))->values();
    }

    /**
     * Abort unless every requested role id is grantable by the acting admin.
     */
    private function ensureRolesGrantable(array $roleIds): void
    {
        $actor = auth()->user();

        foreach (Role::whereIn('id', $roleIds)->get() as $role) {
            abort_unless($actor->canGrantRole($role), 403, 'You cannot assign a role with permissions you do not hold.');
        }
    }
}
