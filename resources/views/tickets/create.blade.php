@extends('layouts.app')

@section('title', 'New Repair Booking')

@section('content')
<div class="mx-auto max-w-3xl">
    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-800 transition mb-4">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to tickets
        </a>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Book a Repair</h1>
        <p class="mt-1 text-gray-500">Tell us about your device and the issue. Add photos so we can assess the condition faster.</p>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
            <ul class="list-inside list-disc text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data"
          x-data="{
              previews: [],
              handleFiles(event) {
                  this.previews = [];
                  Array.from(event.target.files).slice(0, 8).forEach(file => {
                      if (file.type.startsWith('image/')) {
                          this.previews.push({ name: file.name, url: URL.createObjectURL(file) });
                      }
                  });
              },
              removePreview(index) {
                  this.previews.splice(index, 1);
                  $refs.photoInput.value = '';
              }
          }">
        @csrf

        {{-- Device info --}}
        <section class="mb-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-3 mb-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Device Information</h2>
                    <p class="text-sm text-gray-500">What device needs repair?</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="device_type" class="block text-sm font-medium text-gray-700 mb-1.5">Device Type <span class="text-rose-500">*</span></label>
                    <select name="device_type" id="device_type" required
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select device type</option>
                        @foreach(['Desktop', 'Laptop', 'Tablet', 'Smartphone'] as $type)
                            <option value="{{ $type }}" {{ old('device_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="brand" class="block text-sm font-medium text-gray-700 mb-1.5">Brand <span class="text-rose-500">*</span></label>
                    <input type="text" name="brand" id="brand" value="{{ old('brand') }}" required
                        placeholder="e.g. Dell, HP, Apple"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="sm:col-span-2">
                    <label for="os" class="block text-sm font-medium text-gray-700 mb-1.5">Operating System <span class="text-rose-500">*</span></label>
                    <select name="os" id="os" required
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select operating system</option>
                        @foreach(['Windows 10', 'Windows 11', 'macOS', 'Ubuntu', 'iOS', 'Android'] as $os)
                            <option value="{{ $os }}" {{ old('os') === $os ? 'selected' : '' }}>{{ $os }}</option>
                        @endforeach
                    </select>
                </div>
                @if($categories->isNotEmpty())
                    <div class="sm:col-span-2">
                        <label for="service_category_id" class="block text-sm font-medium text-gray-700 mb-1.5">Service Category</label>
                        <select name="service_category_id" id="service_category_id"
                            class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Optional — select repair type</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('service_category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </section>

        {{-- Issue details --}}
        <section class="mb-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-3 mb-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Issue Details</h2>
                    <p class="text-sm text-gray-500">Describe the problem as clearly as you can</p>
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <label for="issue_summary" class="block text-sm font-medium text-gray-700 mb-1.5">Issue Summary <span class="text-rose-500">*</span></label>
                    <input type="text" name="issue_summary" id="issue_summary" value="{{ old('issue_summary') }}" required
                        placeholder="Brief headline, e.g. Screen flickering on startup"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Detailed Description <span class="text-rose-500">*</span></label>
                    <textarea name="description" id="description" rows="5" required
                        placeholder="When did it start? Any error messages? Steps to reproduce..."
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1.5">Urgency</label>
                    <select name="priority" id="priority"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach([1 => 'Low — can wait a few days', 2 => 'Medium-low', 3 => 'Medium — standard turnaround', 4 => 'High — need it soon', 5 => 'Urgent — device unusable'] as $value => $label)
                            <option value="{{ $value }}" {{ (string) old('priority', '3') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        {{-- Device photos --}}
        <section class="mb-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-3 mb-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Device Photos</h2>
                    <p class="text-sm text-gray-500">Upload photos of the device and any visible damage (optional, up to 8)</p>
                </div>
            </div>

            <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 px-6 py-10 transition hover:border-blue-300 hover:bg-blue-50/50">
                <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <span class="mt-3 text-sm font-semibold text-gray-700">Click to upload photos</span>
                <span class="mt-1 text-xs text-gray-500">JPEG, PNG, or WebP · Max 5 MB each</span>
                <input type="file" name="photos[]" x-ref="photoInput" accept="image/jpeg,image/png,image/webp" multiple
                    @change="handleFiles($event)"
                    class="sr-only">
            </label>

            <div x-show="previews.length > 0" class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
                <template x-for="(preview, index) in previews" :key="index">
                    <div class="relative aspect-square overflow-hidden rounded-xl border border-gray-200 bg-gray-100">
                        <img :src="preview.url" :alt="preview.name" class="h-full w-full object-cover">
                        <button type="button" @click="removePreview(index)"
                            class="absolute top-1.5 right-1.5 rounded-full bg-black/50 p-1 text-white hover:bg-black/70">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            <p class="mt-3 text-xs text-gray-500">
                Tip: Include a full device shot and close-ups of scratches, cracks, or error screens.
            </p>
        </section>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:brightness-105">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Submit Repair Request
            </button>
            <a href="{{ route('tickets.index') }}" class="rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                Cancel
            </a>
        </div>
    </form>
</div>

@endsection
