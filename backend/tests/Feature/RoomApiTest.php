<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoomApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_room_types(): void
    {
        $this->actingAs($this->user('admin'), 'sanctum');

        $create = $this->postJson('/api/room-types', [
            'name' => 'Deluxe',
            'base_rate' => 9500,
            'max_occupancy' => 3,
            'amenities' => ['wifi', 'minibar'],
        ]);

        $create->assertCreated()
            ->assertJsonPath('name', 'Deluxe');

        $roomType = RoomType::first();

        $this->getJson('/api/room-types')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Deluxe']);

        $this->putJson("/api/room-types/{$roomType->id}", [
            'name' => 'Deluxe Plus',
            'base_rate' => 10500,
            'max_occupancy' => 3,
        ])->assertOk()
            ->assertJsonPath('name', 'Deluxe Plus');

        $this->deleteJson("/api/room-types/{$roomType->id}")
            ->assertNoContent();
    }

    public function test_front_desk_cannot_create_room_type(): void
    {
        $this->actingAs($this->user('front_desk'), 'sanctum');

        $this->postJson('/api/room-types', [
            'name' => 'Suite',
            'base_rate' => 15000,
            'max_occupancy' => 4,
        ])->assertForbidden();
    }

    public function test_front_desk_can_manage_rooms_and_status_logs(): void
    {
        $this->actingAs($this->user('front_desk'), 'sanctum');
        $roomType = $this->roomType();

        $create = $this->postJson('/api/rooms', [
            'room_type_id' => $roomType->id,
            'room_number' => '201',
            'floor' => 2,
            'status' => 'available',
        ]);

        $create->assertCreated()
            ->assertJsonPath('room_number', '201');

        $room = Room::first();

        $this->getJson('/api/rooms?status=available&floor=2')
            ->assertOk()
            ->assertJsonFragment(['room_number' => '201']);

        $this->patchJson("/api/rooms/{$room->id}/status", [
            'status' => 'cleaning',
            'notes' => 'Post checkout clean',
        ])->assertOk()
            ->assertJsonPath('status', 'cleaning');

        $this->assertDatabaseHas('room_status_logs', [
            'room_id' => $room->id,
            'previous_status' => 'available',
            'new_status' => 'cleaning',
        ]);
    }

    public function test_room_validation_errors_are_returned(): void
    {
        $this->actingAs($this->user('manager'), 'sanctum');

        $this->postJson('/api/rooms', [
            'room_type_id' => 999,
            'room_number' => '',
            'status' => 'broken',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['room_type_id', 'room_number', 'status']);
    }

    private function roomType(): RoomType
    {
        return RoomType::create([
            'name' => 'Standard',
            'slug' => 'standard',
            'base_rate' => 6500,
            'max_occupancy' => 2,
        ]);
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
