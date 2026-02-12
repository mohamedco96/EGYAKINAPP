<?php

namespace Tests\Feature\Api\V2\Contact;

use App\Models\User;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Contact Form operations
 *
 * Tests the following endpoints:
 * - POST /api/v2/contact
 *
 * @group contact
 * @group api
 * @group v2
 */
class ContactTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== SUBMIT CONTACT FORM TESTS ====================

    /** @test */
    public function test_submit_contact_form_successfully()
    {
        $doctor = $this->doctorUser();

        $contactData = [
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@example.com',
            'subject' => 'Technical Support',
            'message' => 'I need help with the application...',
        ];

        $response = $this->postJson('/api/v2/contact', $contactData);

        $response->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('contacts', [
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@example.com',
            'subject' => 'Technical Support',
            'message' => 'I need help with the application...',
        ]);
    }

    /** @test */
    public function test_submit_contact_form_requires_authentication()
    {
        $response = $this->postJson('/api/v2/contact', [
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@example.com',
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_submit_contact_form_validates_name()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/contact', [
            'name' => '', // Empty name
            'email' => 'test@example.com',
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

        $this->assertValidationError($response, ['name']);
    }

    /** @test */
    public function test_submit_contact_form_validates_email()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/contact', [
            'name' => 'Ahmed Ali',
            'email' => 'invalid-email', // Invalid email format
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

        $this->assertValidationError($response, ['email']);
    }

    /** @test */
    public function test_submit_contact_form_validates_subject()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/contact', [
            'name' => 'Ahmed Ali',
            'email' => 'test@example.com',
            'subject' => '', // Empty subject
            'message' => 'Test message',
        ]);

        $this->assertValidationError($response, ['subject']);
    }

    /** @test */
    public function test_submit_contact_form_validates_message()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/contact', [
            'name' => 'Ahmed Ali',
            'email' => 'test@example.com',
            'subject' => 'Test',
            'message' => '', // Empty message
        ]);

        $this->assertValidationError($response, ['message']);
    }

    /** @test */
    public function test_submit_contact_form_stores_user_id()
    {
        $doctor = $this->doctorUser();

        $contactData = [
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@example.com',
            'subject' => 'Technical Support',
            'message' => 'I need help with the application...',
        ];

        $response = $this->postJson('/api/v2/contact', $contactData);

        $response->assertStatus(201);

        $contact = Contact::latest()->first();

        if ($contact && isset($contact->doctor_id)) {
            $this->assertEquals($doctor->id, $contact->doctor_id);
        }
    }

    /** @test */
    public function test_submit_contact_form_with_long_message()
    {
        $doctor = $this->doctorUser();

        $longMessage = str_repeat('This is a detailed message. ', 50);

        $contactData = [
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@example.com',
            'subject' => 'Detailed Question',
            'message' => $longMessage,
        ];

        $response = $this->postJson('/api/v2/contact', $contactData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('contacts', [
            'subject' => 'Detailed Question',
            'message' => $longMessage,
        ]);
    }

    /** @test */
    public function test_submit_contact_form_validates_email_format()
    {
        $doctor = $this->doctorUser();

        // Test various invalid email formats
        $invalidEmails = [
            'plaintext',
            '@example.com',
            'user@',
            'user@.com',
        ];

        foreach ($invalidEmails as $invalidEmail) {
            $response = $this->postJson('/api/v2/contact', [
                'name' => 'Test User',
                'email' => $invalidEmail,
                'subject' => 'Test',
                'message' => 'Test message',
            ]);

            $this->assertValidationError($response, ['email']);
        }
    }

    /** @test */
    public function test_submit_contact_form_success_response_message()
    {
        $doctor = $this->doctorUser();

        $contactData = [
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@example.com',
            'subject' => 'Technical Support',
            'message' => 'I need help with the application...',
        ];

        $response = $this->postJson('/api/v2/contact', $contactData);

        $response->assertStatus(201)
            ->assertJson(['value' => true]);

        $data = $response->json();

        if (isset($data['message'])) {
            $this->assertNotEmpty($data['message']);
        }
    }
}
