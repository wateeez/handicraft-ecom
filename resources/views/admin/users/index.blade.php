@extends('admin.layout')

@section('header')
    <div>
        <h2 class="text-xl font-semibold text-foreground leading-tight">Admin Users</h2>
        <p class="text-xs text-muted-foreground mt-0.5">Manage admin users, roles, and permissions</p>
    </div>
@endsection

@section('content')
<div>
    <!-- Header -->
    <div class="flex justify-end mb-6">
        <a href="{{ route('admin.users.create') }}"
            class="bg-green-premium text-white px-6 py-3 rounded-lg hover:bg-green-800 transition-colors flex items-center justify-center w-full sm:w-auto">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Admin User
        </a>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="bg-green-premium/20 border border-green-400 text-green-premium px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ session('error') }}
    </div>
    @endif

    <!-- Filters -->
    <div class="bg-cream rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search by name or email..."
                    class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>
            <div>
                <select name="role" class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">All Roles</option>
                    @foreach(\App\Models\Role::all() as $role)
                    <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                        {{ $role->display_name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-green-premium text-white px-4 py-2 rounded-lg hover:bg-green-800 transition-colors">
                    Filter
                </button>
                <a href="{{ route('admin.users.index') }}" class="flex-1 bg-[#E8E2D2] text-truffle-extra-dark px-4 py-2 rounded-lg hover:bg-[#E8E2D2] transition-colors text-center">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Admin Users Table -->
    <div class="bg-cream rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#F5F2EA]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Roles</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Last Login</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-cream divide-y divide-gray-200">
                    @forelse($admins as $admin)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-green-premium/20 rounded-full flex items-center justify-center">
                                    <span class="text-green-premium font-semibold">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-truffle-extra-dark">{{ $admin->name }}</div>
                                    <div class="text-sm text-truffle-extra-dark">{{ $admin->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($admin->roles as $role)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $role->name === 'super_admin' ? 'bg-truffle-medium/20 text-truffle-dark border border-truffle-medium' : 'bg-truffle-light text-truffle-dark' }}">
                                    {{ $role->display_name }}
                                </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($admin->is_active)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-premium/20 text-green-800">
                                Active
                            </span>
                            @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Inactive
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-truffle-extra-dark">
                            {{ $admin->last_login_at ? $admin->last_login_at->format('M d, Y H:i') : 'Never' }}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.users.show', $admin) }}" 
                                    class="text-blue-600 hover:text-blue-900" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>

                                @if($admin->id !== auth()->id() || !$admin->isSuperAdmin())
                                <a href="{{ route('admin.users.edit', $admin) }}" 
                                    class="text-green-premium hover:text-green-900" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>

                                @if(auth()->user()->isSuperAdmin())
                                <form action="{{ route('admin.users.toggle-status', $admin) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900" 
                                        title="{{ $admin->is_active ? 'Deactivate' : 'Activate' }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                d="M{{ $admin->is_active ? '18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' : '9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }}">
                                            </path>
                                        </svg>
                                    </button>
                                </form>

                                <a href="{{ route('admin.users.reset-password', $admin) }}" 
                                    class="text-purple-600 hover:text-purple-900" title="Reset Password">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                    </svg>
                                </a>

                                <form action="{{ route('admin.users.destroy', $admin) }}" method="POST" 
                                    class="inline" onsubmit="event.preventDefault(); openDeleteModal(this, 'Enter your password to delete this admin user.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-truffle-extra-dark">
                            No admin users found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $admins->links() }}
    </div>
</div>
@endsection
