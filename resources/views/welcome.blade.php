@extends('layouts.app')

@section('title', 'Home')

@section('content')
{{-- Hero --}}
<section class="relative overflow-hidden rounded-2xl text-white shadow-xl mb-16" style="background: linear-gradient(to bottom right, {{ $site->primary_color ?? '#2563eb' }}, #4338ca);">
    @if($homepage->hero_image_url)
        <div class="absolute inset-0">
            <img src="{{ $homepage->hero_image_url }}" alt="" class="h-full w-full object-cover opacity-25">
        </div>
    @endif
    <div class="absolute inset-0 opacity-10">
        <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white"></div>
        <div class="absolute -bottom-32 -left-32 w-80 h-80 rounded-full bg-white"></div>
    </div>

    <div class="relative px-8 py-16 md:py-24 text-center max-w-4xl mx-auto">
        <p class="text-blue-100 font-semibold tracking-wide uppercase text-sm mb-4">{{ $site->welcome_badge }}</p>
        <h1 class="text-4xl md:text-5xl font-bold mb-6 leading-tight">
            {{ $site->welcome_headline }}
        </h1>
        <p class="text-lg md:text-xl text-blue-100 mb-10 max-w-2xl mx-auto">
            {{ $site->welcome_subheadline }}
        </p>

        @auth
            <div class="bg-white/10 backdrop-blur rounded-xl p-6 md:p-8 max-w-xl mx-auto">
                <p class="text-blue-50 mb-2">
                    Welcome back, <span class="font-bold text-white">{{ Auth::user()->name }}</span>
                </p>
                <p class="text-blue-200 text-sm mb-6">Your repair requests are just a click away.</p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <a href="{{ route('tickets.create') }}" class="bg-white text-blue-700 font-bold py-3 px-8 rounded-lg hover:bg-blue-50 transition shadow-lg">
                        + Book a Repair
                    </a>
                    <a href="{{ route('tickets.index') }}" class="bg-blue-500/80 text-white font-bold py-3 px-8 rounded-lg hover:bg-blue-400 border border-blue-400 transition">
                        My Tickets
                    </a>
                    <a href="{{ route('dashboard') }}" class="bg-transparent text-white font-bold py-3 px-8 rounded-lg border-2 border-white/40 hover:bg-white/10 transition">
                        Dashboard
                    </a>
                </div>
            </div>
        @else
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="{{ route('register') }}" class="bg-white text-blue-700 font-bold py-3 px-8 rounded-lg hover:bg-blue-50 transition shadow-lg">
                    Get Started — It's Free
                </a>
                <a href="{{ route('login') }}" class="bg-transparent text-white font-bold py-3 px-8 rounded-lg border-2 border-white/60 hover:bg-white/10 transition">
                    Sign In
                </a>
            </div>
            <p class="mt-6 text-blue-200 text-sm">
                Already have an account but forgot your password?
                <a href="{{ route('password.request') }}" class="underline hover:text-white font-semibold">Reset it here</a>
            </p>
        @endauth
    </div>
</section>

@if($homepage->show_features && count($homepage->features) > 0)
{{-- Feature cards --}}
<section class="mb-16">
    <div class="text-center mb-10">
        <h2 class="text-3xl font-bold text-gray-900">{{ $homepage->features_title }}</h2>
        @if($homepage->features_subtitle)
            <p class="text-gray-600 mt-3 max-w-2xl mx-auto">{{ $homepage->features_subtitle }}</p>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($homepage->features as $feature)
            <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
                @if(!empty($feature['icon']))
                    <div class="text-3xl mb-3">{{ $feature['icon'] }}</div>
                @endif
                <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $feature['title'] }}</h3>
                <p class="text-gray-600 text-sm">{{ $feature['description'] }}</p>
            </div>
        @endforeach
    </div>
</section>
@endif

@if($homepage->show_steps && count($homepage->steps) > 0)
{{-- How it works --}}
<section class="mb-16 bg-white rounded-2xl shadow p-8 md:p-12">
    <div class="text-center mb-10">
        <h2 class="text-3xl font-bold text-gray-900">{{ $homepage->steps_title }}</h2>
        @if($homepage->steps_subtitle)
            <p class="text-gray-600 mt-3">{{ $homepage->steps_subtitle }}</p>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        @foreach($homepage->steps as $index => $step)
            <div class="text-center">
                <div class="w-12 h-12 bg-brand text-white rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-4">{{ $index + 1 }}</div>
                <h3 class="font-bold text-gray-900 mb-2">{{ $step['title'] }}</h3>
                <p class="text-gray-600 text-sm">{{ $step['description'] }}</p>
            </div>
        @endforeach
    </div>
</section>
@endif

@foreach($homepage->image_sections as $sectionIndex => $section)
<section class="mb-16">
    <div class="grid grid-cols-1 gap-8 items-center {{ $section['image_url'] ? 'md:grid-cols-2' : '' }}">
        @if($section['image_url'])
            <div class="{{ $sectionIndex % 2 === 1 ? 'md:order-2' : '' }}">
                <img src="{{ $section['image_url'] }}" alt="{{ $section['title'] }}" class="w-full rounded-2xl shadow-lg object-cover max-h-80">
            </div>
        @endif
        @if($section['title'] || $section['subtitle'])
            <div class="{{ $section['image_url'] && $sectionIndex % 2 === 1 ? 'md:order-1' : '' }} {{ $section['image_url'] ? '' : 'text-center max-w-2xl mx-auto' }}">
                @if($section['title'])
                    <h2 class="text-3xl font-bold text-gray-900 mb-3">{{ $section['title'] }}</h2>
                @endif
                @if($section['subtitle'])
                    <p class="text-gray-600">{{ $section['subtitle'] }}</p>
                @endif
            </div>
        @endif
    </div>
</section>
@endforeach

{{-- Service categories --}}
@if(isset($categories) && $categories->isNotEmpty())
<section class="mb-16">
    <div class="text-center mb-10">
        <h2 class="text-3xl font-bold text-gray-900">Repair Services We Offer</h2>
        <p class="text-gray-600 mt-3">Select a category when you submit your repair request</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($categories as $category)
            <div class="bg-white rounded-xl shadow p-5 border border-gray-100 hover:border-blue-200 transition">
                <h3 class="font-bold text-gray-900 mb-2">{{ $category->name }}</h3>
                <p class="text-gray-600 text-sm">{{ $category->description ?: 'Professional repair service for your device.' }}</p>
            </div>
        @endforeach
    </div>

    @guest
        <div class="text-center mt-8">
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand px-6 py-3 text-sm font-semibold text-white transition">
                Book a Repair Now
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    @endguest
</section>
@endif

{{-- Contact / support --}}
@if(!empty($site->contact_email) || !empty($site->contact_phone) || !empty($site->support_hours))
<section class="mb-16 rounded-2xl border border-gray-100 bg-white p-8 md:p-10 text-center">
    <h2 class="text-2xl font-bold text-gray-900 mb-3">Need Help Before Booking?</h2>
    <p class="text-gray-600 mb-6 max-w-xl mx-auto">Our team is here to answer questions about your repair.</p>
    <div class="flex flex-wrap justify-center gap-6 text-sm text-gray-700">
        @if(!empty($site->contact_email))
            <a href="mailto:{{ $site->contact_email }}" class="flex items-center gap-2 hover:text-brand transition">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                {{ $site->contact_email }}
            </a>
        @endif
        @if(!empty($site->contact_phone))
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                {{ $site->contact_phone }}
            </span>
        @endif
        @if(!empty($site->support_hours))
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $site->support_hours }}
            </span>
        @endif
    </div>
</section>
@endif

{{-- Bottom CTA --}}
@guest
@if($homepage->show_cta)
<section class="text-center rounded-2xl text-white py-12 px-8" style="background: linear-gradient(to right, {{ $site->primary_color ?? '#2563eb' }}, #4338ca);">
    <h2 class="text-2xl md:text-3xl font-bold mb-4">{{ $homepage->cta_title }}</h2>
    @if($homepage->cta_subtitle)
        <p class="text-blue-100 mb-8 max-w-xl mx-auto">{{ $homepage->cta_subtitle }}</p>
    @endif
    <div class="flex flex-wrap gap-4 justify-center">
        <a href="{{ route('register') }}" class="bg-white text-blue-700 font-bold py-3 px-8 rounded-lg hover:bg-blue-50 transition shadow-lg">
            Create Free Account
        </a>
        <a href="{{ route('login') }}" class="text-white font-bold py-3 px-8 rounded-lg border-2 border-white/50 hover:bg-white/10 transition">
            Sign In
        </a>
    </div>
</section>
@endif
@endguest
@endsection
