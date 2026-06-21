<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePictureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_profile_picture(): void
    {
        Storage::fake('public');

        $user = User::factory()->customer()->create();

        $file = UploadedFile::fake()->image('avatar.png', 800, 600)->size(500);

        $this->actingAs($user)
            ->post(route('profile.picture.store'), [
                'profile_picture' => $file,
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertNotNull($user->profile_picture);
        Storage::disk('public')->assertExists($user->profile_picture);

        $stored = Storage::disk('public')->get($user->profile_picture);
        $this->assertStringStartsWith("\xFF\xD8\xFF", $stored);
    }

    public function test_user_can_remove_profile_picture(): void
    {
        Storage::fake('public');

        $user = User::factory()->customer()->create();
        $path = "profile-pictures/{$user->id}.jpg";
        Storage::disk('public')->put($path, 'fake-image');
        $user->update(['profile_picture' => $path]);

        $this->actingAs($user)
            ->delete(route('profile.picture.destroy'))
            ->assertRedirect();

        $user->refresh();

        $this->assertNull($user->profile_picture);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_profile_picture_rejects_invalid_file_type(): void
    {
        Storage::fake('public');

        $user = User::factory()->customer()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->post(route('profile.picture.store'), [
                'profile_picture' => $file,
            ])
            ->assertSessionHasErrors('profile_picture');
    }
}
