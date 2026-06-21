<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_ticket_with_photos(): void
    {
        Storage::fake('public');
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)
            ->post(route('tickets.store'), [
                'device_type' => 'Laptop',
                'brand' => 'Dell',
                'os' => 'Windows 11',
                'issue_summary' => 'Cracked screen',
                'description' => 'Screen has a large crack in the corner.',
                'priority' => 4,
                'photos' => [
                    UploadedFile::fake()->image('device.jpg', 800, 600),
                    UploadedFile::fake()->image('damage.jpg', 600, 600),
                ],
            ]);

        $ticket = Ticket::query()->latest('id')->first();

        $response->assertRedirect(route('tickets.show', $ticket));

        $this->assertCount(2, $ticket->photos);
        Storage::disk('public')->assertExists($ticket->photos->first()->path);
    }

    public function test_customer_can_add_photos_to_existing_ticket(): void
    {
        Storage::fake('public');
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($customer)
            ->post(route('tickets.photos.store', $ticket), [
                'photos' => [UploadedFile::fake()->image('extra.jpg')],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertCount(1, $ticket->fresh()->photos);
    }

    public function test_technician_cannot_upload_photos_to_ticket(): void
    {
        Storage::fake('public');
        $technician = User::factory()->technician()->create();
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($technician)
            ->post(route('tickets.photos.store', $ticket), [
                'photos' => [UploadedFile::fake()->image('hack.jpg')],
            ])
            ->assertForbidden();
    }

    public function test_ticket_show_displays_photo_gallery(): void
    {
        Storage::fake('public');
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->post(route('tickets.store'), [
                'device_type' => 'Phone',
                'brand' => 'Samsung',
                'os' => 'Android',
                'issue_summary' => 'Battery issue',
                'description' => 'Battery drains fast.',
                'photos' => [UploadedFile::fake()->image('phone.jpg')],
            ]);

        $ticket = Ticket::query()->latest('id')->first();

        $this->actingAs($customer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Device Photos');
    }
}
