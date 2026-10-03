@extends('admin.layout')

@section('header')
    <div>
        <h2 class="text-xl font-semibold text-foreground leading-tight">Roles &amp; Permissions</h2>
        <p class="text-xs text-muted-foreground mt-0.5">Manage roles and their associated permissions</p>
    </div>
@endsection

@section('content')
<div>
    <!-- Header -->
    <div class="flex justify-end mb-6">
        <a href="{{ route('admin.roles.create') }}"
            class="bg-green-premium text-white px-6 py-3 rounded-lg hover:bg-green-800 transition-colors flex items-center justify-center w-full sm:w-auto">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Role
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

    <!-- Roles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($roles as $role)
        <div class="bg-cream rounded-lg shadow-sm p-6 {{ $role->name === 'super_admin' ? 'border-2 border-truffle-medium/50' : '' }}">
            <!-- Role Header -->
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-xl font-semibold {{ $role->name === 'super_admin' ? 'text-truffle-dark' : 'text-truffle-extra-dark' }}">
                        {{ $role->display_name }}
                    </h3>
                    @if($role->description)
                    <p class="text-sm text-truffle-extra-dark mt-1">{{ $role->description }}</p>
                    @endif
                </div>
                @if($role->name === 'super_admin')
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-truffle-medium/20 text-truffle-dark border border-truffle-medium">
                    System
                </span>
                @endif
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="bg-[#F5F2EA] rounded-lg p-3">
                    <div class="text-sm text-truffle-extra-dark">Users</div>
                    <div class="text-2xl font-bold text-truffle-extra-dark">{{ $role->users_count }}</div>
                </div>
                <div class="bg-[#F5F2EA] rounded-lg p-3">
                    <div class="text-sm text-truffle-extra-dark">Permissions</div>
                    <div class="text-2xl font-bold text-truffle-extra-dark">{{ $role->permissions_count }}</div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-4 border-t">
                <a href="{{ route('admin.roles.show', $role) }}" 
                    class="flex-1 text-center bg-blue-50 text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-100 transition-colors text-sm font-medium">
                    View Details
                </a>
                
                @if($role->name !== 'super_admin')
                <a href="{{ route('admin.roles.edit', $role) }}" 
                    class="flex-1 text-center bg-green-premium/10 text-green-premium px-4 py-2 rounded-lg hover:bg-green-premium/20 transition-colors text-sm font-medium">
                    Edit
                </a>

                @if($role->users_count === 0)
                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" 
                    onsubmit="event.preventDefault(); openDeleteModal(this, 'Enter your password to delete this role.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                        class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition-colors text-sm font-medium">
                        Delete
                    </button>
                </form>
                @endif
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
