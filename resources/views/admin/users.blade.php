@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
    <p class="text-gray-600 mt-2">
        @if(Auth::user()->canManageUsers())
            Manage system users and their roles
        @else
            View system users (read-only in demo mode)
        @endif
    </p>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                @if(Auth::user()->canManageUsers())
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-bold text-gray-900">{{ $user->name }}</td>
                    <td class="px-6 py-4 text-gray-700">{{ $user->email }}</td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 text-xs font-bold rounded-full
                            @if($user->role === 'admin')
                                bg-red-100 text-red-800
                            @elseif($user->role === 'demo_admin')
                                bg-amber-100 text-amber-800
                            @elseif($user->role === 'technician')
                                bg-blue-100 text-blue-800
                            @else
                                bg-green-100 text-green-800
                            @endif">
                            {{ \App\Support\UserRole::label($user->role) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $user->created_at->format('M d, Y') }}</td>
                    @if(Auth::user()->canManageUsers())
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2">
                                @if($user->role === 'customer')
                                    <form action="{{ route('admin.upgrade-technician', $user) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-700 font-bold text-sm">
                                            Upgrade to Technician
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.promote-admin', $user) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-700 font-bold text-sm">
                                            Promote to Admin
                                        </button>
                                    </form>
                                @elseif($user->role === 'technician')
                                    <form action="{{ route('admin.downgrade-technician', $user) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-orange-600 hover:text-orange-700 font-bold text-sm">
                                            Downgrade to Customer
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.promote-admin', $user) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-700 font-bold text-sm">
                                            Promote to Admin
                                        </button>
                                    </form>
                                @elseif($user->role === 'admin' && $user->id !== Auth::id())
                                    <form action="{{ route('admin.demote-admin', $user) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-orange-600 hover:text-orange-700 font-bold text-sm">
                                            Demote to Customer
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ Auth::user()->canManageUsers() ? 5 : 4 }}" class="px-6 py-4 text-center text-gray-500">No users found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $users->links() }}
</div>
@endsection
