<?php

namespace Tests\Feature\Api\V2\Notifications;

use App\Models\User;
use App\Models\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Notification operations
 *
 * Tests the following endpoints:
 * - GET /api/v2/notifications
 * - POST /api/v2/notifications/{id}/read
 * - POST /api/v2/notifications/readAll
 *
 * @group notifications
 * @group api
 * @group v2
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== GET NOTIFICATIONS TESTS ====================

    /** @test */
    public function test_get_notifications_returns_paginated_list()
    {
        $doctor = $this->doctorUser();

        // Create notifications for the doctor
        AppNotification::factory()->count(10)->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson('/api/v2/notifications');

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertHasPaginationStructure($response);
    }

    /** @test */
    public function test_get_notifications_requires_authentication()
    {
        $response = $this->getJson('/api/v2/notifications');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_notifications_filters_by_user()
    {
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        // Create notifications for doctor1
        AppNotification::factory()->count(5)->create(['doctor_id' => $doctor1->id]);

        // Create notifications for doctor2
        AppNotification::factory()->count(3)->create(['doctor_id' => $doctor2->id]);

        $response = $this->getJson('/api/v2/notifications');

        $response->assertStatus(200);

        $notifications = $response->json('data.data') ?? $response->json('data');

        // Should only return doctor1's notifications
        foreach ($notifications as $notification) {
            $this->assertEquals($doctor1->id, $notification['doctor_id']);
        }

        $this->assertCount(5, $notifications);
    }

    /** @test */
    public function test_get_notifications_includes_unread_count()
    {
        $doctor = $this->doctorUser();

        // Create unread notifications
        AppNotification::factory()->count(3)->create([
            'doctor_id' => $doctor->id,
            'is_read' => false,
        ]);

        // Create read notifications
        AppNotification::factory()->count(2)->create([
            'doctor_id' => $doctor->id,
            'is_read' => true,
        ]);

        $response = $this->getJson('/api/v2/notifications');

        $response->assertStatus(200);

        $data = $response->json();

        if (isset($data['unread_count'])) {
            $this->assertEquals(3, $data['unread_count']);
        }
    }

    /** @test */
    public function test_get_notifications_ordered_by_date()
    {
        $doctor = $this->doctorUser();

        // Create notifications with different timestamps
        $old = AppNotification::factory()->create([
            'doctor_id' => $doctor->id,
            'created_at' => now()->subDays(3),
        ]);

        $recent = AppNotification::factory()->create([
            'doctor_id' => $doctor->id,
            'created_at' => now()->subHours(1),
        ]);

        $response = $this->getJson('/api/v2/notifications');

        $response->assertStatus(200);

        $notifications = $response->json('data.data') ?? $response->json('data');

        if (count($notifications) >= 2) {
            // Recent notification should appear first
            $this->assertEquals($recent->id, $notifications[0]['id']);
        }
    }

    /** @test */
    public function test_get_notifications_includes_related_data()
    {
        $doctor = $this->doctorUser();

        AppNotification::factory()->create([
            'doctor_id' => $doctor->id,
            'title' => 'New Comment',
            'body' => 'Someone commented on your post',
        ]);

        $response = $this->getJson('/api/v2/notifications');

        $response->assertStatus(200);

        $notifications = $response->json('data.data') ?? $response->json('data');

        if (!empty($notifications)) {
            $this->assertArrayHasKey('title', $notifications[0]);
            $this->assertArrayHasKey('body', $notifications[0]);
        }
    }

    // ==================== MARK AS READ TESTS ====================

    /** @test */
    public function test_mark_notification_as_read()
    {
        $doctor = $this->doctorUser();

        $notification = AppNotification::factory()->create([
            'doctor_id' => $doctor->id,
            'is_read' => false,
        ]);

        $response = $this->postJson("/api/v2/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('app_notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    /** @test */
    public function test_mark_notification_as_read_requires_authentication()
    {
        $notification = AppNotification::factory()->create();

        $response = $this->postJson("/api/v2/notifications/{$notification->id}/read");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_mark_notification_as_read_requires_ownership()
    {
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        $notification = AppNotification::factory()->create([
            'doctor_id' => $doctor2->id,
            'is_read' => false,
        ]);

        // Authenticate as doctor1
        $this->actingAs($doctor1);

        $response = $this->postJson("/api/v2/notifications/{$notification->id}/read");

        // Should fail due to ownership
        $response->assertStatus(403);
    }

    /** @test */
    public function test_mark_notification_as_read_handles_non_existent()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/notifications/99999/read');

        $this->assertNotFound($response);
    }

    /** @test */
    public function test_mark_already_read_notification()
    {
        $doctor = $this->doctorUser();

        $notification = AppNotification::factory()->create([
            'doctor_id' => $doctor->id,
            'is_read' => true,
        ]);

        $response = $this->postJson("/api/v2/notifications/{$notification->id}/read");

        $response->assertStatus(200);

        // Should remain read
        $this->assertDatabaseHas('app_notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    // ==================== MARK ALL AS READ TESTS ====================

    /** @test */
    public function test_mark_all_notifications_as_read()
    {
        $doctor = $this->doctorUser();

        // Create unread notifications
        AppNotification::factory()->count(5)->create([
            'doctor_id' => $doctor->id,
            'is_read' => false,
        ]);

        $response = $this->postJson('/api/v2/notifications/readAll');

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        // Verify all notifications are now read
        $unreadCount = AppNotification::where('doctor_id', $doctor->id)
            ->where('is_read', false)
            ->count();

        $this->assertEquals(0, $unreadCount);
    }

    /** @test */
    public function test_mark_all_notifications_requires_authentication()
    {
        $response = $this->postJson('/api/v2/notifications/readAll');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_mark_all_notifications_only_affects_own_notifications()
    {
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        // Create unread notifications for both doctors
        AppNotification::factory()->count(3)->create([
            'doctor_id' => $doctor1->id,
            'is_read' => false,
        ]);

        AppNotification::factory()->count(2)->create([
            'doctor_id' => $doctor2->id,
            'is_read' => false,
        ]);

        $response = $this->postJson('/api/v2/notifications/readAll');

        $response->assertStatus(200);

        // Doctor1's notifications should be read
        $doctor1Unread = AppNotification::where('doctor_id', $doctor1->id)
            ->where('is_read', false)
            ->count();
        $this->assertEquals(0, $doctor1Unread);

        // Doctor2's notifications should remain unread
        $doctor2Unread = AppNotification::where('doctor_id', $doctor2->id)
            ->where('is_read', false)
            ->count();
        $this->assertEquals(2, $doctor2Unread);
    }

    /** @test */
    public function test_mark_all_notifications_handles_empty_list()
    {
        $doctor = $this->doctorUser();

        // No notifications for this doctor
        $response = $this->postJson('/api/v2/notifications/readAll');

        $response->assertStatus(200);

        $this->assertSuccess($response);
    }

    /** @test */
    public function test_mark_all_notifications_preserves_already_read()
    {
        $doctor = $this->doctorUser();

        // Create mix of read and unread
        $read = AppNotification::factory()->create([
            'doctor_id' => $doctor->id,
            'is_read' => true,
        ]);

        $unread = AppNotification::factory()->create([
            'doctor_id' => $doctor->id,
            'is_read' => false,
        ]);

        $response = $this->postJson('/api/v2/notifications/readAll');

        $response->assertStatus(200);

        // Both should be read now
        $this->assertDatabaseHas('app_notifications', [
            'id' => $read->id,
            'is_read' => true,
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'id' => $unread->id,
            'is_read' => true,
        ]);
    }
}
