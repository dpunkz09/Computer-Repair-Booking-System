<?php

namespace App\Http\Controllers;

use App\Support\SiteSettings;
use Illuminate\Http\Request;

class LegalController extends Controller
{
    public function privacy()
    {
        $content = trim((string) SiteSettings::get('privacy_policy_content', ''));

        if ($content === '') {
            abort(404);
        }

        return view('legal.show', [
            'title' => SiteSettings::getOrDefault('privacy_policy_title'),
            'content' => $content,
        ]);
    }

    public function terms()
    {
        $content = trim((string) SiteSettings::get('terms_of_service_content', ''));

        if ($content === '') {
            abort(404);
        }

        return view('legal.show', [
            'title' => SiteSettings::getOrDefault('terms_of_service_title'),
            'content' => $content,
        ]);
    }
}
