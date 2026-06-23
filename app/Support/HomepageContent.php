<?php

namespace App\Support;

class HomepageContent
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'hero_image_path' => null,
            'show_features' => true,
            'features_title' => 'Why Book With Us?',
            'features_subtitle' => 'Simple, transparent repair booking designed for you — track every step from submission to pickup.',
            'features' => [
                ['icon' => '📋', 'title' => 'Easy Online Booking', 'description' => 'Describe your device and issue in minutes — no phone calls or waiting on hold.'],
                ['icon' => '🔄', 'title' => 'Real-Time Status Updates', 'description' => 'Follow your repair from submission through diagnosis, repair, and completion.'],
                ['icon' => '💬', 'title' => 'Direct Technician Chat', 'description' => 'Message your assigned technician directly on your ticket thread.'],
                ['icon' => '🔔', 'title' => 'Instant Notifications', 'description' => 'Get alerted when your ticket status changes or when you receive a new reply.'],
                ['icon' => '📱', 'title' => 'All Devices Welcome', 'description' => 'Laptops, desktops, and more — tell us your device type, brand, and operating system.'],
                ['icon' => '🔒', 'title' => 'Your Data, Protected', 'description' => 'Only you and your assigned technician can see your ticket details and messages.'],
            ],
            'show_steps' => true,
            'steps_title' => 'How It Works',
            'steps_subtitle' => 'Three simple steps to get your device repaired',
            'steps' => [
                ['title' => 'Create Your Account', 'description' => 'Sign up for free and set up your customer profile in seconds.'],
                ['title' => 'Submit a Repair Request', 'description' => 'Tell us about your device, describe the problem, and pick a service category.'],
                ['title' => 'Track & Stay in Touch', 'description' => 'Monitor progress and chat with your technician until your device is ready.'],
            ],
            'image_sections' => [],
            'show_cta' => true,
            'cta_title' => 'Ready to Get Your Device Fixed?',
            'cta_subtitle' => 'Create a free account and submit your first repair request in under two minutes.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $raw = SiteSettings::get('homepage_content');

        if (! is_string($raw) || trim($raw) === '') {
            return self::defaults();
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return self::defaults();
        }

        return self::mergeWithDefaults($decoded);
    }

    /**
     * @return object{
     *     hero_image_url: ?string,
     *     show_features: bool,
     *     features_title: string,
     *     features_subtitle: string,
     *     features: array<int, array{icon: string, title: string, description: string}>,
     *     show_steps: bool,
     *     steps_title: string,
     *     steps_subtitle: string,
     *     steps: array<int, array{title: string, description: string}>,
     *     image_sections: array<int, array{title: string, subtitle: string, image_path: ?string, image_url: ?string}>,
     *     show_cta: bool,
     *     cta_title: string,
     *     cta_subtitle: string
     * }
     */
    /**
     * @return object{
     *     hero_image_path: ?string,
     *     hero_image_url: ?string,
     *     show_features: bool,
     *     features_title: string,
     *     features_subtitle: string,
     *     features: array<int, array{icon: string, title: string, description: string}>,
     *     show_steps: bool,
     *     steps_title: string,
     *     steps_subtitle: string,
     *     steps: array<int, array{title: string, description: string}>,
     *     image_sections: array<int, array{title: string, subtitle: string, image_path: ?string, image_url: ?string}>,
     *     show_cta: bool,
     *     cta_title: string,
     *     cta_subtitle: string
     * }
     */
    public static function forAdmin(): object
    {
        $content = self::get();

        $imageSections = collect($content['image_sections'] ?? [])
            ->map(fn (array $section) => [
                'title' => (string) ($section['title'] ?? ''),
                'subtitle' => (string) ($section['subtitle'] ?? ''),
                'image_path' => $section['image_path'] ?? null,
                'image_url' => self::imageUrl($section['image_path'] ?? null),
            ])
            ->values()
            ->all();

        return (object) [
            'hero_image_path' => $content['hero_image_path'] ?? null,
            'hero_image_url' => self::imageUrl($content['hero_image_path'] ?? null),
            'show_features' => (bool) ($content['show_features'] ?? true),
            'features_title' => (string) ($content['features_title'] ?? ''),
            'features_subtitle' => (string) ($content['features_subtitle'] ?? ''),
            'features' => array_values($content['features'] ?? []),
            'show_steps' => (bool) ($content['show_steps'] ?? true),
            'steps_title' => (string) ($content['steps_title'] ?? ''),
            'steps_subtitle' => (string) ($content['steps_subtitle'] ?? ''),
            'steps' => array_values($content['steps'] ?? []),
            'image_sections' => $imageSections,
            'show_cta' => (bool) ($content['show_cta'] ?? true),
            'cta_title' => (string) ($content['cta_title'] ?? ''),
            'cta_subtitle' => (string) ($content['cta_subtitle'] ?? ''),
        ];
    }

    public static function forPublic(string $siteName): object
    {
        $content = self::get();

        $featuresTitle = str_replace('{site_name}', $siteName, (string) $content['features_title']);
        $featuresTitle = str_replace('Us?', $siteName.'?', $featuresTitle);

        if ($featuresTitle === 'Why Book With Us?') {
            $featuresTitle = 'Why Book With '.$siteName.'?';
        }

        $imageSections = collect($content['image_sections'] ?? [])
            ->map(function (array $section) {
                $path = $section['image_path'] ?? null;

                return [
                    'title' => (string) ($section['title'] ?? ''),
                    'subtitle' => (string) ($section['subtitle'] ?? ''),
                    'image_path' => $path,
                    'image_url' => ($path && $path !== '') ? asset('storage/'.$path) : null,
                ];
            })
            ->filter(fn (array $section) => $section['image_url'] || $section['title'] !== '' || $section['subtitle'] !== '')
            ->values()
            ->all();

        return (object) [
            'hero_image_url' => self::imageUrl($content['hero_image_path'] ?? null),
            'show_features' => (bool) ($content['show_features'] ?? true),
            'features_title' => $featuresTitle,
            'features_subtitle' => (string) ($content['features_subtitle'] ?? ''),
            'features' => array_values($content['features'] ?? []),
            'show_steps' => (bool) ($content['show_steps'] ?? true),
            'steps_title' => (string) ($content['steps_title'] ?? ''),
            'steps_subtitle' => (string) ($content['steps_subtitle'] ?? ''),
            'steps' => array_values($content['steps'] ?? []),
            'image_sections' => $imageSections,
            'show_cta' => (bool) ($content['show_cta'] ?? true),
            'cta_title' => (string) ($content['cta_title'] ?? ''),
            'cta_subtitle' => (string) ($content['cta_subtitle'] ?? ''),
        ];
    }

    public static function imageUrl(?string $path): ?string
    {
        return ($path && $path !== '') ? asset('storage/'.$path) : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function save(array $payload): void
    {
        SiteSettings::set('homepage_content', json_encode(
            self::mergeWithDefaults($payload),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mergeWithDefaults(array $data): array
    {
        $defaults = self::defaults();
        $merged = array_merge($defaults, array_intersect_key($data, $defaults));

        $merged['features'] = self::normalizeFeatures($data['features'] ?? $defaults['features']);
        $merged['steps'] = self::normalizeSteps($data['steps'] ?? $defaults['steps']);
        $merged['image_sections'] = self::normalizeImageSections($data['image_sections'] ?? $defaults['image_sections']);

        $merged['show_features'] = filter_var($merged['show_features'], FILTER_VALIDATE_BOOLEAN);
        $merged['show_steps'] = filter_var($merged['show_steps'], FILTER_VALIDATE_BOOLEAN);
        $merged['show_cta'] = filter_var($merged['show_cta'], FILTER_VALIDATE_BOOLEAN);

        return $merged;
    }

    /**
     * @param  mixed  $features
     * @return array<int, array{icon: string, title: string, description: string}>
     */
    public static function normalizeFeatures(mixed $features): array
    {
        if (! is_array($features)) {
            return self::defaults()['features'];
        }

        $normalized = [];

        foreach ($features as $feature) {
            if (! is_array($feature)) {
                continue;
            }

            $title = trim((string) ($feature['title'] ?? ''));
            $description = trim((string) ($feature['description'] ?? ''));

            if ($title === '' && $description === '') {
                continue;
            }

            $normalized[] = [
                'icon' => trim((string) ($feature['icon'] ?? '📋')) ?: '📋',
                'title' => $title,
                'description' => $description,
            ];
        }

        return $normalized !== [] ? array_slice($normalized, 0, 8) : self::defaults()['features'];
    }

    /**
     * @param  mixed  $steps
     * @return array<int, array{title: string, description: string}>
     */
    public static function normalizeSteps(mixed $steps): array
    {
        if (! is_array($steps)) {
            return self::defaults()['steps'];
        }

        $normalized = [];

        foreach ($steps as $step) {
            if (! is_array($step)) {
                continue;
            }

            $title = trim((string) ($step['title'] ?? ''));
            $description = trim((string) ($step['description'] ?? ''));

            if ($title === '' && $description === '') {
                continue;
            }

            $normalized[] = [
                'title' => $title,
                'description' => $description,
            ];
        }

        return $normalized !== [] ? array_slice($normalized, 0, 6) : self::defaults()['steps'];
    }

    /**
     * @param  mixed  $sections
     * @return array<int, array{title: string, subtitle: string, image_path: ?string}>
     */
    public static function normalizeImageSections(mixed $sections): array
    {
        if (! is_array($sections)) {
            return [];
        }

        $normalized = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $title = trim((string) ($section['title'] ?? ''));
            $subtitle = trim((string) ($section['subtitle'] ?? ''));
            $imagePath = $section['image_path'] ?? null;

            if ($title === '' && $subtitle === '' && empty($imagePath)) {
                continue;
            }

            $normalized[] = [
                'title' => $title,
                'subtitle' => $subtitle,
                'image_path' => is_string($imagePath) && $imagePath !== '' ? $imagePath : null,
            ];
        }

        return array_slice($normalized, 0, 4);
    }
}
