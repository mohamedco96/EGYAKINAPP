<?php

namespace Tests\Feature\Modules\Auth;

use App\Models\User;
use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Patients\Models\Patients;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocalizedNotificationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    private User $anotherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'locale' => 'en',
        ]);

        $this->anotherUser = User::factory()->create([
            'locale' => 'ar',
        ]);
    }

    /** @test */
    public function it_can_get_all_localized_notifications()
    {
        Sanctum::actingAs($this->user);

        $patient = Patients::factory()->create();

        // Create test notifications
        AppNotification::factory()->count(3)->create([
            'doctor_id' => $this->user->id,
            'patient_id' => $patient->id,
            'localization_key' => 'api.test_notification',
            'localization_params' => ['name' => 'Test Doctor'],
            'content' => 'Test notification content',
        ]);

        $response = $this->getJson('/api/v1/notifications/localized');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'value',
                'data' => [
                    'notifications' => [
                        '*' => [
                            'id',
                            'type',
                            'content',
                            'localized_content',
                            'read',
                            'created_at',
                            'patient',
                            'type_doctor',
                        ],
                    ],
                    'pagination' => [
                        'current_page',
                        'total',
                        'per_page',
                    ],
                ],
            ])
            ->assertJson([
                'value' => true,
            ]);
    }

    /** @test */
    public function it_can_get_new_notifications_only()
    {
        Sanctum::actingAs($this->user);

        $patient = Patients::factory()->create();

        // Create read and unread notifications
        AppNotification::factory()->create([
            'doctor_id' => $this->user->id,
            'patient_id' => $patient->id,
            'read' => true,
            'localization_key' => 'api.test_notification',
            'content' => 'Read notification',
        ]);

        AppNotification::factory()->count(2)->create([
            'doctor_id' => $this->user->id,
            'patient_id' => $patient->id,
            'read' => false,
            'localization_key' => 'api.test_notification',
            'content' => 'Unread notification',
        ]);

        $response = $this->getJson('/api/v1/notifications/localized/new');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'value',
                'data' => [
                    'notifications' => [
                        '*' => [
                            'id',
                            'type',
                            'content',
                            'localized_content',
                            'read',
                            'created_at',
                        ],
                    ],
                    'unread_count',
                ],
            ])
            ->assertJson([
                'value' => true,
                'data' => [
                    'unread_count' => 2,
                ],
            ]);

        // Verify all returned notifications are unread
        $notifications = $response->json('data.notifications');
        foreach ($notifications as $notification) {
            $this->assertFalse($notification['read']);
        }
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        Sanctum::actingAs($this->user);

        $patient = Patients::factory()->create();

        $notification = AppNotification::factory()->create([
            'doctor_id' => $this->user->id,
            'patient_id' => $patient->id,
            'read' => false,
            'localization_key' => 'api.test_notification',
            'content' => 'Test notification',
        ]);

        $response = $this->postJson("/api/v1/notifications/localized/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'Notification marked as read',
            ]);

        // Verify notification is marked as read
        $notification->refresh();
        $this->assertTrue($notification->read);
    }

    /** @test */
    public function it_can_mark_all_notifications_as_read()
    {
        Sanctum::actingAs($this->user);

        $patient = Patients::factory()->create();

        // Create multiple unread notifications
        AppNotification::factory()->count(3)->create([
            'doctor_id' => $this->user->id,
            'patient_id' => $patient->id,
            'read' => false,
            'localization_key' => 'api.test_notification',
            'content' => 'Test notification',
        ]);

        $response = $this->postJson('/api/v1/notifications/localized/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'All notifications marked as read',
            ]);

        // Verify all user's notifications are marked as read
        $unreadCount = AppNotification::where('doctor_id', $this->user->id)
            ->where('read', false)
            ->count();

        $this->assertEquals(0, $unreadCount);
    }

    /** @test */
    public function it_returns_404_for_non_existent_notification()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/notifications/localized/99999/read');

        $response->assertStatus(404)
            ->assertJson([
                'value' => false,
                'message' => 'Notification not found',
            ]);
    }

    /** @test */
    public function it_prevents_marking_other_users_notifications_as_read()
    {
        Sanctum::actingAs($this->user);

        $patient = Patients::factory()->create();

        // Create notification for another user
        $notification = AppNotification::factory()->create([
            'doctor_id' => $this->anotherUser->id,
            'patient_id' => $patient->id,
            'read' => false,
            'localization_key' => 'api.test_notification',
            'content' => 'Test notification',
        ]);

        $response = $this->postJson("/api/v1/notifications/localized/{$notification->id}/read");

        $response->assertStatus(403)
            ->assertJson([
                'value' => false,
                'message' => 'Unauthorized to access this notification',
            ]);

        // Verify notification remains unread
        $notification->refresh();
        $this->assertFalse($notification->read);
    }

    /** @test */
    public function it_returns_localized_content_based_on_user_locale()
    {
        // Test with Arabic user
        Sanctum::actingAs($this->anotherUser);

        $patient = Patients::factory()->create();

        $notification = AppNotification::factory()->create([
            'doctor_id' => $this->anotherUser->id,
            'patient_id' => $patient->id,
            'localization_key' => 'api.test_notification',
            'localization_params' => ['name' => 'دكتور أحمد'],
            'content' => 'Test notification in English',
        ]);

        $response = $this->getJson('/api/v1/notifications/localized');

        $response->assertStatus(200);

        $notifications = $response->json('data.notifications');
        $this->assertNotEmpty($notifications);

        // Verify localized content is different from original content
        $firstNotification = $notifications[0];
        $this->assertArrayHasKey('localized_content', $firstNotification);
        $this->assertNotEquals(
            $firstNotification['content'],
            $firstNotification['localized_content']
        );
    }

    /** @test */
    public function it_handles_pagination_for_notifications()
    {
        Sanctum::actingAs($this->user);

        $patient = Patients::factory()->create();

        // Create many notifications
        AppNotification::factory()->count(25)->create([
            'doctor_id' => $this->user->id,
            'patient_id' => $patient->id,
            'localization_key' => 'api.test_notification',
            'content' => 'Test notification',
        ]);

        $response = $this->getJson('/api/v1/notifications/localized?page=1&per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'notifications',
                    'pagination' => [
                        'current_page',
                        'total',
                        'per_page',
                        'last_page',
                    ],
                ],
            ]);

        $pagination = $response->json('data.pagination');
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(25, $pagination['total']);
        $this->assertCount(10, $response->json('data.notifications'));
    }

    /** @test */
    public function it_filters_notifications_by_type()
    {
        Sanctum::actingAs($this->user);

        $patient = Patients::factory()->create();

        // Create notifications of different types
        AppNotification::factory()->create([
            'doctor_id' => $this->user->id,
            'patient_id' => $patient->id,
            'type' => 'Consultation',
            'localization_key' => 'api.consultation_notification',
            'content' => 'Consultation notification',
        ]);

        AppNotification::factory()->create([
            'doctor_id' => $this->user->id,
            'patient_id' => $patient->id,
            'type' => 'Comment',
            'localization_key' => 'api.comment_notification',
            'content' => 'Comment notification',
        ]);

        $response = $this->getJson('/api/v1/notifications/localized?type=Consultation');

        $response->assertStatus(200);

        $notifications = $response->json('data.notifications');
        foreach ($notifications as $notification) {
            $this->assertEquals('Consultation', $notification['type']);
        }
    }

    /** @test */
    public function it_requires_authentication_for_all_endpoints()
    {
        $endpoints = [
            ['method' => 'get', 'uri' => '/api/v1/notifications/localized'],
            ['method' => 'get', 'uri' => '/api/v1/notifications/localized/new'],
            ['method' => 'post', 'uri' => '/api/v1/notifications/localized/1/read'],
            ['method' => 'post', 'uri' => '/api/v1/notifications/localized/read-all'],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->{$endpoint['method'].'Json'}($endpoint['uri']);

            $response->assertStatus(401);
        }
    }

    /** @test */
    public function it_handles_empty_notifications_gracefully()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications/localized');

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'data' => [
                    'notifications' => [],
                    'pagination' => [
                        'total' => 0,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_handles_notifications_without_localization_key()
    {
        Sanctum::actingAs($this->user);

        $patient = Patients::factory()->create();

        // Create notification without localization key
        AppNotification::factory()->create([
            'doctor_id' => $this->user->id,
            'patient_id' => $patient->id,
            'localization_key' => null,
            'localization_params' => null,
            'content' => 'Plain notification content',
        ]);

        $response = $this->getJson('/api/v1/notifications/localized');

        $response->assertStatus(200);

        $notifications = $response->json('data.notifications');
        $this->assertNotEmpty($notifications);

        // Should fall back to original content
        $notification = $notifications[0];
        $this->assertEquals(
            $notification['content'],
            $notification['localized_content']
        );
    }
}
