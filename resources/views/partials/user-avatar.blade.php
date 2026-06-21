@php
    $sizeClasses = match($size ?? 'md') {
        'xs' => 'h-7 w-7 text-xs',
        'sm' => 'h-9 w-9 text-xs',
        'md-sm' => 'h-10 w-10 text-xs',
        'card' => 'h-12 w-12 text-sm',
        'md' => 'h-16 w-16 text-lg',
        'lg' => 'h-24 w-24 text-2xl',
        'xl' => 'h-32 w-32 text-3xl',
        default => 'h-16 w-16 text-lg',
    };
    $roundedClass = ($shape ?? 'circle') === 'rounded' ? 'rounded-xl' : 'rounded-full';
@endphp

@if($user->profilePictureUrl())
    <img
        src="{{ $user->profilePictureUrl() }}"
        alt="{{ $user->name }}"
        class="{{ $sizeClasses }} {{ $roundedClass }} shrink-0 object-cover ring-2 ring-white shadow-sm {{ $class ?? '' }}"
    >
@else
    <span class="{{ $sizeClasses }} shrink-0 inline-flex items-center justify-center {{ $roundedClass }} bg-gradient-to-br from-blue-500 to-indigo-600 font-bold text-white ring-2 ring-white shadow-sm {{ $class ?? '' }}">
        {{ $user->initials() }}
    </span>
@endif
