<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\FolioCharge;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_reports(): void
    {
        $this->actingAs($this->user('manager'), 'sanctum');
        $this->reportFixture();

        $this->getJson('/api/reports/occupancy?start_date=2026-06-01&end_date=2026-06-03')
            ->assertOk()
            ->assertJsonFragment(['date' => '2026-06-01']);

        $this->getJson('/api/reports/revenue?start_date=2026-06-01&end_date=2026-06-30&group_by=day')
            ->assertOk()
            ->assertJsonFragment(['period' => '2026-06-01']);

        $this->getJson('/api/reports/room-type-performance')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Standard']);

        $this->getJson('/api/reports/guest-statistics')
            ->assertOk()
            ->assertJsonStructure(['top_guests', 'repeat_guests', 'average_stay_length']);
    }

    public function test_reports_validate_dates_and_reject_unauthorized_roles(): void
    {
        $this->actingAs($this->user('front_desk'), 'sanctum');

        $this->getJson('/api/reports/occupancy?start_date=2026-06-01&end_date=2026-06-03')
            ->assertForbidden();

        $this->actingAs($this->user('manager', 'manager@example.com'), 'sanctum');

        $this->getJson('/api/reports/occupancy?start_date=bad-date&end_date=2026-06-03')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_manager_can_view_and_update_settings(): void
    {
        $this->actingAs($this->user('manager'), 'sanctum');

        Setting::create(['key' => 'tax_rate', 'value' => '0.16']);

        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('tax_rate', '0.16');

        $this->putJson('/api/settings', [
            'settings' => [
                'tax_rate' => '0.18',
                'currency' => 'KES',
            ],
        ])->assertOk()
            ->assertJsonPath('tax_rate', '0.18')
            ->assertJsonPath('currency', 'KES');
    }

    public function test_settings_validation_and_authorization_are_enforced(): void
    {
        $this->actingAs($this->user('housekeeping'), 'sanctum');

        $this->getJson('/api/settings')
            ->assertForbidden();

        $this->actingAs($this->user('admin', 'admin@example.com'), 'sanctum');

        $this->putJson('/api/settings', [
            'settings' => 'not-an-array',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['settings']);
    }

    private function reportFixture(): void
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
            'status' => 'occupied',
            'is_active' => true,
        ]);
        $guest = Guest::create([
            'first_name' => 'Report',
            'last_name' => 'Guest',
            'email' => 'report@example.com',
            'total_stays' => 2,
        ]);
        $booking = Booking::create([
            'booking_reference' => 'SS-REPORT-001',
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'room_type_id' => $roomType->id,
            'check_in_date' => '2026-06-01',
            'check_out_date' => '2026-06-03',
            'actual_check_out' => '2026-06-03 10:00:00',
            'status' => 'checked_out',
            'source' => 'website',
            'subtotal' => 13000,
            'tax_amount' => 2080,
            'discount_amount' => 0,
            'total_price' => 15080,
        ]);

        FolioCharge::create([
            'booking_id' => $booking->id,
            'charge_type' => 'room',
            'description' => 'Room charge',
            'amount' => 15080,
            'charged_at' => '2026-06-01 14:00:00',
        ]);
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
