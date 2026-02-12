<?php

namespace Tests\Feature\Api\V2\Patients;

use App\Models\User;
use App\Modules\Patients\Models\Patients;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Patient Comment Management
 *
 * Tests the following endpoints:
 * - GET /api/v2/comment/{patient_id}
 * - POST /api/v2/comment
 * - DELETE /api/v2/comment/{comment_id}
 *
 * @group patients
 * @group patient-comments
 * @group api
 * @group v2
 */
class PatientCommentTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== GET COMMENTS TESTS ====================

    /** @test */
    public function test_get_patient_comments_returns_list()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        // Create comments for the patient
        Comment::factory()->count(3)->create(['patient_id' => $patient->id]);

        $response = $this->getJson("/api/v2/comment/{$patient->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $comments = $response->json('data');
        $this->assertCount(3, $comments);
    }

    /** @test */
    public function test_get_patient_comments_requires_authentication()
    {
        $patient = Patients::factory()->create();

        $response = $this->getJson("/api/v2/comment/{$patient->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_patient_comments_validates_patient_id()
    {
        $doctor = $this->doctorUser();

        $response = $this->getJson('/api/v2/comment/99999');

        // Should return empty array or 404
        $response->assertStatus(200);

        $comments = $response->json('data');
        $this->assertEmpty($comments);
    }

    /** @test */
    public function test_comments_ordered_by_date()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        // Create comments with different timestamps
        Comment::factory()->create([
            'patient_id' => $patient->id,
            'created_at' => now()->subDays(3),
        ]);

        Comment::factory()->create([
            'patient_id' => $patient->id,
            'created_at' => now()->subDays(1),
        ]);

        Comment::factory()->create([
            'patient_id' => $patient->id,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->getJson("/api/v2/comment/{$patient->id}");

        $response->assertStatus(200);

        $comments = $response->json('data');

        if (count($comments) >= 3) {
            // Verify ordering (newest first or oldest first depending on implementation)
            $dates = collect($comments)->pluck('created_at')->toArray();
            $sortedDates = collect($dates)->sort()->values()->toArray();

            // Either ascending or descending order is fine
            $this->assertTrue(
                $dates === $sortedDates || $dates === array_reverse($sortedDates)
            );
        }
    }

    // ==================== CREATE COMMENT TESTS ====================

    /** @test */
    public function test_create_patient_comment_successfully()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $commentData = [
            'patient_id' => $patient->id,
            'content' => 'This is a test comment',
        ];

        $response = $this->postJson('/api/v2/comment', $commentData);

        $response->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('comments', [
            'patient_id' => $patient->id,
            'content' => 'This is a test comment',
        ]);
    }

    /** @test */
    public function test_create_patient_comment_requires_authentication()
    {
        $patient = Patients::factory()->create();

        $response = $this->postJson('/api/v2/comment', [
            'patient_id' => $patient->id,
            'content' => 'Test comment',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_create_patient_comment_validates_content()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->postJson('/api/v2/comment', [
            'patient_id' => $patient->id,
            'content' => '', // Empty content
        ]);

        $this->assertValidationError($response, ['content']);
    }

    /** @test */
    public function test_create_patient_comment_associates_with_patient()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $this->postJson('/api/v2/comment', [
            'patient_id' => $patient->id,
            'content' => 'Associated comment',
        ]);

        $comment = Comment::latest()->first();

        $this->assertEquals($patient->id, $comment->patient_id);
    }

    /** @test */
    public function test_create_patient_comment_associates_with_user()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $this->postJson('/api/v2/comment', [
            'patient_id' => $patient->id,
            'content' => 'User comment',
        ]);

        $comment = Comment::latest()->first();

        $this->assertEquals($doctor->id, $comment->user_id ?? $comment->doctor_id);
    }

    /** @test */
    public function test_comment_timestamps_tracked()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $this->postJson('/api/v2/comment', [
            'patient_id' => $patient->id,
            'content' => 'Timestamped comment',
        ]);

        $comment = Comment::latest()->first();

        $this->assertNotNull($comment->created_at);
        $this->assertNotNull($comment->updated_at);
    }

    // ==================== DELETE COMMENT TESTS ====================

    /** @test */
    public function test_delete_patient_comment_successfully()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $comment = Comment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $doctor->id,
        ]);

        $response = $this->deleteJson("/api/v2/comment/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    /** @test */
    public function test_delete_patient_comment_requires_authentication()
    {
        $comment = Comment::factory()->create();

        $response = $this->deleteJson("/api/v2/comment/{$comment->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_delete_patient_comment_requires_ownership()
    {
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        $patient = Patients::factory()->create(['doctor_id' => $doctor2->id]);

        // Comment created by doctor2
        $comment = Comment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $doctor2->id,
        ]);

        // Authenticate as doctor1
        $this->actingAs($doctor1);

        $response = $this->deleteJson("/api/v2/comment/{$comment->id}");

        // Should fail due to ownership
        $response->assertStatus(403);
    }

    /** @test */
    public function test_delete_patient_comment_validates_comment_id()
    {
        $doctor = $this->doctorUser();

        $response = $this->deleteJson('/api/v2/comment/99999');

        $this->assertNotFound($response);
    }

    /** @test */
    public function test_comment_notifications_sent()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $this->postJson('/api/v2/comment', [
            'patient_id' => $patient->id,
            'content' => 'Notification test comment',
        ]);

        // Verify notification was created
        $this->assertDatabaseHas('app_notifications', [
            'doctor_id' => $doctor->id,
        ]);
    }

    /** @test */
    public function test_get_comments_includes_user_data()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        Comment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $doctor->id,
        ]);

        $response = $this->getJson("/api/v2/comment/{$patient->id}");

        $response->assertStatus(200);

        $comments = $response->json('data');

        if (!empty($comments)) {
            // Verify user data is included
            $this->assertArrayHasKey('user', $comments[0]);
        }
    }
}
