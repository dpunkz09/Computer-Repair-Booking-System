<?php

namespace App\Http\Controllers;

use App\Services\ProfilePictureService;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ProfileController extends Controller
{
    public function __construct(
        private ProfilePictureService $profilePictures
    ) {}

    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function uploadPicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        $user = Auth::user();
        $user->profile_picture = $this->profilePictures->store(
            $request->file('profile_picture'),
            $user
        );
        $user->save();

        return back()->with('success', 'Profile picture updated successfully.');
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'current_password' => 'nullable|required_with:password',
            'password' => ['nullable', 'confirmed', PasswordRule::min(8)],
        ]);

        if (! empty($validated['password'])) {
            if (! Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            $user->password = Hash::make($validated['password']);
        }

        $emailChanged = $validated['email'] !== $user->email;

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($emailChanged && SiteSettings::bool('require_email_verification')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged && SiteSettings::bool('require_email_verification')) {
            $user->sendEmailVerificationNotification();

            return redirect()
                ->route('verification.notice')
                ->with('success', 'Your email was updated. Please verify your new address to continue.');
        }

        return back()->with('success', 'Profile updated successfully.');
    }

    public function destroyPicture()
    {
        $this->profilePictures->delete(Auth::user());

        return back()->with('success', 'Profile picture removed.');
    }
}
