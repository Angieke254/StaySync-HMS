<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\FolioCharge;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FolioApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_staff_can_add_charges_and_payments_and_view_balance(): void
    {
        $this->actingAs($this->user('pos_staff'), 'sanctum');
        $booking = $this->booking();

        $charge = $this->postJson("/api/bookings/{$booking->id}/charges", [
            'charge_type' => 'restaurant',
            'description' => 'Dinner',
            'amount' => 3500,
            'charged_at' => '2026-06-02 20:00:00',
        ]);

        $charge->assertCreated()
            ->assertJsonPath('charge_type', 'restaurant');

        $payment = $this->postJson("/api/bookings/{$booking->id}/payments", [
            'amount' => 2000,
            'payment_method' => 'cash',
            'transaction_reference' => 'CASH-001',
        ]);

        $payment->assertCreated()
            ->assertJsonPath('status', 'completed');

        $this->getJson("/api/bookings/{$booking->id}/folio")
            ->assertOk()
            ->assertJsonPath('summary.totalCharges', 3500)
            ->assertJsonPath('summary.totalPayments', 2000)
            ->assertJsonPath('summary.balance', 1500);
    }

    public function test_manager_can_void_charge_but_pos_staff_cannot(): void
    {
        $booking = $this->booking();
        $charge = FolioCharge::create([
            'booking_id' => $booking->id,
            'charge_type' => 'spa',
            'description' => 'Massage',
            'amount' => 5000,
            'charged_at' => now(),
        ]);

        $this->actingAs($this->user('pos_staff'), 'sanctum');
        $this->deleteJson("/api/folio-charges/{$charge->id}")
            ->assertForbidden();

        $this->actingAs($this->user('manager', 'manager@example.com'), 'sanctum');
        $this->deleteJson("/api/folio-charges/{$charge->id}")
            ->assertOk()
            ->assertJsonFragment(['id' => $charge->id]);

        $this->assertNotNull($charge->refresh()->voided_at);
    }

    public function test_folio_validation_errors_are_returned(): void
    {
        $this->actingAs($this->user('pos_staff'), 'sanctum');
        $booking = $this->booking();

        $this->postJson("/api/bookings/{$booking->id}/charges", [
            'charge_type' => 'invalid',
            'description' => '',
            'amount' => 0,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['charge_type', 'description', 'amount']);

        $this->postJson("/api/bookings/{$booking->id}/payments", [
            'amount' => -1,
            'payment_method' => 'cheque',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['amount', 'payment_method']);
    }

    public function test_payments_endpoint_returns_recorded_payments(): void
    {
        $this->actingAs($this->user('front_desk'), 'sanctum');
        $booking = $this->booking();

        Payment::create([
            'booking_id' => $booking->id,
            'amount' => 5000,
            'payment_method' => 'card',
            'transaction_reference' => 'CARD-001',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $this->getJson("/api/bookings/{$booking->id}/payments")
            ->assertOk()
            ->assertJsonFragment(['transaction_reference' => 'CARD-001']);
    }

    private function booking(): Booking
    {
        $roomType = RoomType::create([
            'name' => 'Suite',
            'slug' => 'suite',
            'base_rate' => 15000,
            'max_occupancy' => 4,
        ]);
        $room = Room::create([
            'room_type_id' => $roomType->id,
            'room_number' => '301',
            'floor' => 3,
            'status' => 'occupied',
            'is_active' => true,
        ]);
        $guest = Guest::create([
            'first_name' => 'Folio',
            'last_name' => 'Guest',
            'email' => 'folio@example.com',
        ]);

        return Booking::create([
            'booking_reference' => 'SS-FOLIO-001',
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'room_type_id' => $roomType->id,
            'check_in_date' => '2026-06-01',
            'check_out_date' => '2026-06-03',
            'status' => 'checked_in',
            'source' => 'walk_in',
            'subtotal' => 30000,
            'tax_amount' => 4800,
            'discount_amount' => 0,
            'total_price' => 34800,
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
