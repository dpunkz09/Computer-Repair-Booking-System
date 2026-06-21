@extends('layouts.app')

@section('title', 'Service Categories')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Service Categories</h1>
    <p class="text-gray-600 mt-2">Manage repair service types available for bookings</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Add Category</h2>
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 font-bold mb-2">Name</label>
                    <input type="text" name="name" id="name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-gray-700 font-bold mb-2">Description</label>
                    <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <label class="flex items-center mb-4">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300">
                    <span class="ml-2 text-gray-700">Active</span>
                </label>
                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700">
                    Create Category
                </button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-bold">{{ $category->name }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ Str::limit($category->description, 60) ?: '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 text-xs font-bold rounded-full {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <details class="inline">
                                    <summary class="text-blue-600 hover:text-blue-700 font-bold cursor-pointer">Edit</summary>
                                    <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="mt-3 p-4 bg-gray-50 rounded-lg space-y-3">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ $category->name }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ $category->description }}</textarea>
                                        <label class="flex items-center">
                                            <input type="checkbox" name="is_active" value="1" {{ $category->is_active ? 'checked' : '' }} class="rounded border-gray-300">
                                            <span class="ml-2 text-gray-700">Active</span>
                                        </label>
                                        <button type="submit" class="bg-blue-600 text-white font-bold py-1 px-4 rounded-lg text-sm">Save</button>
                                    </form>
                                </details>
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Delete this category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-700 font-bold text-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">No categories yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $categories->links() }}</div>
    </div>
</div>
@endsection
