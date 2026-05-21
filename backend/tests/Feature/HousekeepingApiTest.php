<?php

namespace Tests\Feature;

use App\Models\HousekeepingTask;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HousekeepingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_housekeeping_user_can_list_create_update_and_complete_tasks(): void
    {
        $this->actingAs($this->user('housekeeping'), 'sanctum');
        $room = $this->room('201', 'cleaning');

        $create = $this->postJson('/api/housekeeping/tasks', [
            'room_id' => $room->id,
            'priority' => 'urgent',
            'status' => 'pending',
            'notes' => 'Guest arriving soon',
        ]);

        $create->assertCreated()
            ->assertJsonPath('priority', 'urgent');

        $task = HousekeepingTask::first();

        $this->getJson('/api/housekeeping/tasks?status=pending&priority=urgent&floor=2')
            ->assertOk()
            ->assertJsonFragment(['notes' => 'Guest arriving soon']);

        $this->patchJson("/api/housekeeping/tasks/{$task->id}", [
            'status' => 'in_progress',
        ])->assertOk()
            ->assertJsonPath('status', 'in_progress');

        $this->patchJson("/api/housekeeping/tasks/{$task->id}/complete")
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'available',
        ]);
        $this->assertDatabaseHas('room_status_logs', [
            'room_id' => $room->id,
            'new_status' => 'available',
        ]);
    }

    public function test_housekeeping_validation_and_authorization_are_enforced(): void
    {
        $this->actingAs($this->user('housekeeping'), 'sanctum');

        $this->postJson('/api/housekeeping/tasks', [
            'room_id' => 999,
            'priority' => 'immediate',
            'status' => 'unknown',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['room_id', 'priority', 'status']);

        $this->actingAs($this->user('pos_staff', 'pos@example.com'), 'sanctum');

        $this->getJson('/api/housekeeping/tasks')
            ->assertForbidden();
    }

    private function room(string $roomNumber, string $status): Room
    {
        $roomType = RoomType::create([
            'name' => 'Deluxe',
            'slug' => 'deluxe',
            'base_rate' => 9500,
            'max_occupancy' => 3,
        ]);

        return Room::create([
            'room_type_id' => $roomType->id,
            'room_number' => $roomNumber,
            'floor' => 2,
            'status' => $status,
            'is_active' => true,
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
