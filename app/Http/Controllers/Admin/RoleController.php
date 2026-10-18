<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Roles shipped with the application that cannot be deleted.
     */
    private const PROTECTED_ROLES = ['super_admin', 'admin', 'editor', 'viewer', 'operations_staff', 'finance'];

    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::withCount('users', 'permissions')->get();
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = $this->assignablePermissions();
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles', 'regex:/^[a-z_]+$/'],
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $this->ensurePermissionsAssignable($validated['permissions'] ?? []);

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->attach($validated['permissions']);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $role->load('permissions', 'users');
        return view('admin.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        // Prevent editing super_admin role
        if ($role->name === 'super_admin') {
            abort(403, 'Super Admin role cannot be edited.');
        }

        $this->ensureRoleEditable($role);

        $permissions = $this->assignablePermissions();
        $role->load('permissions');

        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        // Prevent editing super_admin role
        if ($role->name === 'super_admin') {
            abort(403, 'Super Admin role cannot be edited.');
        }

        $this->ensureRoleEditable($role);

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $this->ensurePermissionsAssignable($validated['permissions'] ?? []);

        $role->update([
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        // Prevent deletion of super_admin role
        if ($role->name === 'super_admin') {
            abort(403, 'Super Admin role cannot be deleted.');
        }

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return back()->with('error', 'Built-in roles cannot be deleted.');
        }

        $this->ensureRoleEditable($role);

        if ($role->users()->exists()) {
            return back()->with('error', 'Reassign the users of this role before deleting it.');
        }

        $role->permissions()->detach();

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Permissions the acting admin may hand out: only ones they hold themselves.
     */
    private function assignablePermissions()
    {
        $held = auth()->user()->permissionNames();

        return Permission::all()->filter(fn (Permission $p) => in_array($p->name, $held, true))->values();
    }

    /**
     * Abort unless every submitted permission id is one the acting admin holds.
     */
    private function ensurePermissionsAssignable(array $permissionIds): void
    {
        $held = auth()->user()->permissionNames();
        $names = Permission::whereIn('id', $permissionIds)->pluck('name')->all();

        abort_unless(count(array_diff($names, $held)) === 0, 403, 'You cannot grant permissions you do not hold.');
    }

    /**
     * Abort if the role contains permissions the acting admin does not hold.
     */
    private function ensureRoleEditable(Role $role): void
    {
        abort_unless(auth()->user()->canGrantRole($role), 403, 'You cannot modify a role with permissions you do not hold.');
    }
}
