<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_desk_can_create_booking_and_fetch_details(): void
    {
        $this->actingAs($this->user('front_desk'), 'sanctum');
        [$room, $guest] = $this->fixture();

        $response = $this->postJson('/api/bookings', [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => '2026-06-01',
            'check_out_date' => '2026-06-03',
            'num_adults' => 2,
            'source' => 'website',
            'addons' => [
                ['description' => 'Airport pickup', 'quantity' => 1, 'unit_price' => 2500],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('guest.email', 'guest@example.com')
            ->assertJsonPath('room.room_number', '101');

        $booking = Booking::first();

        $this->getJson("/api/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonFragment(['booking_reference' => $booking->booking_reference])
            ->assertJsonFragment(['description' => 'Airport pickup']);
    }

    public function test_booking_validation_errors_are_returned(): void
    {
        $this->actingAs($this->user('front_desk'), 'sanctum');

        $this->postJson('/api/bookings', [
            'room_id' => 999,
            'check_in_date' => '2026-06-05',
            'check_out_date' => '2026-06-04',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['guest_id', 'room_id', 'check_out_date']);
    }

    public function test_conflicting_booking_returns_conflict(): void
    {
        $this->actingAs($this->user('front_desk'), 'sanctum');
        [$room, $guest] = $this->fixture();

        Booking::create([
            'booking_reference' => 'SS-CONFLICT-001',
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'room_type_id' => $room->room_type_id,
            'check_in_date' => '2026-06-01',
            'check_out_date' => '2026-06-04',
            'status' => 'confirmed',
            'source' => 'website',
            'subtotal' => 19500,
            'tax_amount' => 3120,
            'discount_amount' => 0,
            'total_price' => 22620,
        ]);

        $this->postJson('/api/bookings', [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => '2026-06-03',
            'check_out_date' => '2026-06-05',
        ])->assertStatus(409)
            ->assertJsonPath('message', 'Room is not available for the selected dates.');
    }

    public function test_booking_lifecycle_updates_status_room_and_housekeeping(): void
    {
        $this->actingAs($this->user('front_desk'), 'sanctum');
        [$room, $guest] = $this->fixture();
        $booking = Booking::create([
            'booking_reference' => 'SS-LIFE-001',
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'room_type_id' => $room->room_type_id,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'status' => 'confirmed',
            'source' => 'walk_in',
            'subtotal' => 13000,
            'tax_amount' => 2080,
            'discount_amount' => 0,
            'total_price' => 15080,
        ]);

        $this->patchJson("/api/bookings/{$booking->id}/check-in")
            ->assertOk()
            ->assertJsonPath('status', 'checked_in');

        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => 'occupied']);
        $this->assertDatabaseHas('folio_charges', ['booking_id' => $booking->id, 'charge_type' => 'room']);

        $this->patchJson("/api/bookings/{$booking->id}/check-out")
            ->assertOk()
            ->assertJsonPath('status', 'checked_out');

        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => 'cleaning']);
        $this->assertDatabaseHas('housekeeping_tasks', ['room_id' => $room->id, 'status' => 'pending']);
    }

    public function test_tape_chart_and_availability_endpoints_return_booking_context(): void
    {
        $this->actingAs($this->user('manager'), 'sanctum');
        [$room, $guest] = $this->fixture();

        Booking::create([
            'booking_reference' => 'SS-TAPE-001',
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'room_type_id' => $room->room_type_id,
            'check_in_date' => '2026-06-01',
            'check_out_date' => '2026-06-04',
            'status' => 'confirmed',
            'source' => 'website',
            'subtotal' => 19500,
            'tax_amount' => 3120,
            'discount_amount' => 0,
            'total_price' => 22620,
        ]);

        $this->getJson('/api/tape-chart?start_date=2026-06-01&end_date=2026-06-07')
            ->assertOk()
            ->assertJsonFragment(['guest_name' => 'Jane Guest'])
            ->assertJsonFragment(['color' => '#3B82F6']);

        $this->getJson('/api/rooms/availability?check_in=2026-06-05&check_out=2026-06-07')
            ->assertOk()
            ->assertJsonFragment(['room_number' => '101']);
    }

    private function fixture(): array
    {
        Setting::create(['key' => 'tax_rate', 'value' => '0.16']);

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
            'first_name' => 'Jane',
            'last_name' => 'Guest',
            'email' => 'guest@example.com',
        ]);

        return [$room, $guest, $roomType];
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' User',
            'email' => $role . '@example.com',
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
