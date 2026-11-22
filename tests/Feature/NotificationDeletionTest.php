<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_can_delete_its_own_read_or_unread_notification(): void
    {
        foreach (['admin', 'staf', 'guru', 'wali', 'siswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $notification = $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'test',
                'data' => ['title' => 'Notifikasi uji'],
                'read_at' => $role === 'admin' ? now() : null,
            ]);

            $this->actingAs($user)
                ->delete(route('notifications.destroy', $notification))
                ->assertRedirect();

            $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
        }
    }

    public function test_user_cannot_delete_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = $owner->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => ['title' => 'Pribadi'],
        ]);

        $this->actingAs($otherUser)
            ->delete(route('notifications.destroy', $notification))
            ->assertNotFound();

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }

    public function test_opening_a_notification_removes_it_after_reading(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => ['title' => 'Aktivitas', 'url' => route('admin.dashboard')],
        ]);

        $this->actingAs($user)
            ->get(route('notifications.read', $notification))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_read_all_removes_all_of_the_users_notifications(): void
    {
        $user = User::factory()->create();
        $user->notifications()->createMany([
            ['id' => (string) Str::uuid(), 'type' => 'test', 'data' => ['title' => 'Satu']],
            ['id' => (string) Str::uuid(), 'type' => 'test', 'data' => ['title' => 'Dua']],
        ]);

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $user->fresh()->notifications()->count());
    }
}
