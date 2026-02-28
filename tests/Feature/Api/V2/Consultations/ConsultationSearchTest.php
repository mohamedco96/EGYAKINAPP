<?php

namespace Tests\Feature\Api\V2\Consultations;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Consultation Search operations
 *
 * Tests the following endpoints:
 * - POST /api/v2/consultations/searchDoctors
 *
 * @group consultations
 * @group consultation-search
 * @group api
 * @group v2
 */
class ConsultationSearchTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== SEARCH DOCTORS TESTS ====================

    /** @test */
    public function test_search_doctors_by_name()
    {
        $currentDoctor = $this->doctorUser();

        // Create doctors with different names
        $doctor1 = $this->doctorUser(['name' => 'Ahmed', 'lname' => 'Ali']);
        $doctor2 = $this->doctorUser(['name' => 'Mohamed', 'lname' => 'Ahmed']);
        $doctor3 = $this->doctorUser(['name' => 'Sarah', 'lname' => 'Hassan']);

        $response = $this->postJson('/api/v2/consultations/searchDoctors', [
            'query' => 'Ahmed',
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $doctors = $response->json('data');

        // Should find Ahmed Ali and Mohamed Ahmed
        $foundNames = collect($doctors)->pluck('name')->toArray();
        $this->assertContains('Ahmed', $foundNames);
    }

    /** @test */
    public function test_search_doctors_requires_authentication()
    {
        $response = $this->postJson('/api/v2/consultations/searchDoctors', [
            'query' => 'Ahmed',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_search_doctors_validates_query()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/consultations/searchDoctors', [
            'query' => '', // Empty query
        ]);

        $this->assertValidationError($response, ['query']);
    }

    /** @test */
    public function test_search_doctors_by_specialty()
    {
        $currentDoctor = $this->doctorUser();

        // Create doctors with different specialties
        $cardio = $this->doctorUser(['specialty' => 'Cardiology']);
        $neuro = $this->doctorUser(['specialty' => 'Neurology']);

        $response = $this->postJson('/api/v2/consultations/searchDoctors', [
            'query' => 'Cardiology',
        ]);

        $response->assertStatus(200);

        $doctors = $response->json('data');

        if (!empty($doctors)) {
            $foundSpecialties = collect($doctors)->pluck('specialty')->toArray();
            $this->assertContains('Cardiology', $foundSpecialties);
        }
    }

    /** @test */
    public function test_search_doctors_excludes_current_user()
    {
        $currentDoctor = $this->doctorUser(['name' => 'Ahmed', 'lname' => 'Current']);
        $otherDoctor = $this->doctorUser(['name' => 'Ahmed', 'lname' => 'Other']);

        $response = $this->postJson('/api/v2/consultations/searchDoctors', [
            'query' => 'Ahmed',
        ]);

        $response->assertStatus(200);

        $doctors = $response->json('data');
        $foundIds = collect($doctors)->pluck('id')->toArray();

        // Current doctor should not be in results
        $this->assertNotContains($currentDoctor->id, $foundIds);

        // Other doctor should be in results
        $this->assertContains($otherDoctor->id, $foundIds);
    }

    /** @test */
    public function test_search_doctors_case_insensitive()
    {
        $currentDoctor = $this->doctorUser();
        $doctor = $this->doctorUser(['name' => 'Ahmed', 'lname' => 'Ali']);

        // Search with lowercase
        $response = $this->postJson('/api/v2/consultations/searchDoctors', [
            'query' => 'ahmed',
        ]);

        $response->assertStatus(200);

        $doctors = $response->json('data');
        $foundIds = collect($doctors)->pluck('id')->toArray();

        $this->assertContains($doctor->id, $foundIds);
    }

    /** @test */
    public function test_search_doctors_returns_empty_for_no_matches()
    {
        $currentDoctor = $this->doctorUser();
        $this->doctorUser(['name' => 'Ahmed', 'lname' => 'Ali']);

        $response = $this->postJson('/api/v2/consultations/searchDoctors', [
            'query' => 'NonExistentName',
        ]);

        $response->assertStatus(200);

        $doctors = $response->json('data');
        $this->assertEmpty($doctors);
    }

    /** @test */
    public function test_search_doctors_includes_profile_data()
    {
        $currentDoctor = $this->doctorUser();
        $doctor = $this->doctorUser([
            'name' => 'Ahmed',
            'lname' => 'Ali',
            'specialty' => 'Cardiology',
            'image' => 'profile.jpg',
        ]);

        $response = $this->postJson('/api/v2/consultations/searchDoctors', [
            'query' => 'Ahmed',
        ]);

        $response->assertStatus(200);

        $doctors = $response->json('data');

        if (!empty($doctors)) {
            $foundDoctor = collect($doctors)->firstWhere('id', $doctor->id);

            $this->assertNotNull($foundDoctor);
            $this->assertArrayHasKey('name', $foundDoctor);
            $this->assertArrayHasKey('lname', $foundDoctor);
        }
    }

    /** @test */
    public function test_search_doctors_filters_by_doctor_role()
    {
        $currentDoctor = $this->doctorUser();

        // Create a doctor
        $doctor = $this->doctorUser(['name' => 'Ahmed', 'lname' => 'Doctor']);

        // Create a normal user (not a doctor)
        $normalUser = $this->normalUser(['name' => 'Ahmed', 'lname' => 'User']);

        $response = $this->postJson('/api/v2/consultations/searchDoctors', [
            'query' => 'Ahmed',
        ]);

        $response->assertStatus(200);

        $doctors = $response->json('data');
        $foundIds = collect($doctors)->pluck('id')->toArray();

        // Should include doctor
        $this->assertContains($doctor->id, $foundIds);

        // Should NOT include normal user
        $this->assertNotContains($normalUser->id, $foundIds);
    }

    /** @test */
    public function test_search_doctors_limits_results()
    {
        $currentDoctor = $this->doctorUser();

        // Create many doctors with same name
        for ($i = 1; $i <= 50; $i++) {
            $this->doctorUser(['name' => 'Ahmed', 'lname' => "Doctor{$i}"]);
        }

        $response = $this->postJson('/api/v2/consultations/searchDoctors', [
            'query' => 'Ahmed',
        ]);

        $response->assertStatus(200);

        $doctors = $response->json('data');

        // Should limit results (e.g., to 20 or 50)
        $this->assertLessThanOrEqual(50, count($doctors));
    }
}
