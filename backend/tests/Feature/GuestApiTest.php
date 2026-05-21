<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_desk_can_create_search_view_and_update_guests(): void
    {
        $this->actingAs($this->user('front_desk'), 'sanctum');

        $create = $this->postJson('/api/guests', [
            'first_name' => 'Mary',
            'last_name' => 'Wanjiku',
            'email' => 'mary@example.com',
            'phone' => '0700000002',
            'country' => 'Kenya',
        ]);

        $create->assertCreated()
            ->assertJsonPath('email', 'mary@example.com');

        $guest = Guest::first();

        $this->getJson('/api/guests?search=mary')
            ->assertOk()
            ->assertJsonFragment(['email' => 'mary@example.com']);

        $this->putJson("/api/guests/{$guest->id}", [
            'phone' => '0711111111',
        ])->assertOk()
            ->assertJsonPath('phone', '0711111111');
    }

    public function test_guest_detail_includes_booking_history(): void
    {
        $this->actingAs($this->user('front_desk'), 'sanctum');
        [$guest] = $this->bookingFixture();

        $this->getJson("/api/guests/{$guest->id}")
            ->assertOk()
            ->assertJsonFragment(['booking_reference' => 'SS-TEST-001']);
    }

    public function test_guest_validation_and_authorization_are_enforced(): void
    {
        $this->actingAs($this->user('housekeeping'), 'sanctum');

        $this->getJson('/api/guests')
            ->assertForbidden();

        $this->actingAs($this->user('front_desk', 'frontdesk@example.com'), 'sanctum');

        $this->postJson('/api/guests', [
            'first_name' => '',
            'last_name' => '',
            'email' => 'invalid',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email']);
    }

    private function bookingFixture(): array
    {
        $roomType = RoomType::create([
            'name' => 'Standard',
            'slug' => 'standard',
            'base_rate' => 6500,
            'max_occupancy' => 2,
        ]);
        $room = Room::create([
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'floor' => 1,
            'status' => 'available',
            'is_active' => true,
        ]);
        $guest = Guest::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        Booking::create([
            'booking_reference' => 'SS-TEST-001',
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'room_type_id' => $roomType->id,
            'check_in_date' => now()->addDay()->toDateString(),
            'check_out_date' => now()->addDays(3)->toDateString(),
            'status' => 'confirmed',
            'source' => 'website',
            'subtotal' => 13000,
            'tax_amount' => 2080,
            'discount_amount' => 0,
            'total_price' => 15080,
        ]);

        return [$guest, $room, $roomType];
    }

    private function user(string $role, ?string $email = null): User
    {
        return User::create([
            'name' => ucfirst($role) . ' User',
            'email' => $email ?? $role . '@example.com',
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
