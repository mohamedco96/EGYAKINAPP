<?php

namespace Tests\Feature\Modules\Contacts;

use Tests\TestCase;
use App\Modules\Contacts\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_can_list_all_contacts()
    {
        // Create some test contacts
        Contact::factory()->count(3)->create();

        $response = $this->getJson('/api/contact');

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'value',
                     'data' => [
                         '*' => [
                             'id',
                             'doctor_id',
                             'message',
                             'created_at',
                             'updated_at',
                             'doctor' => [
                                 'id',
                                 'name',
                                 'lname'
                             ]
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function it_can_create_a_contact()
    {
        $contactData = [
            'message' => $this->faker->text(200)
        ];

        $response = $this->postJson('/api/contact', $contactData);

        $response->assertStatus(200)
                 ->assertJson([
                     'value' => true,
                     'message' => 'Contact Created Successfully'
                 ]);

        $this->assertDatabaseHas('contacts', [
            'doctor_id' => $this->user->id,
            'message' => $contactData['message']
        ]);
    }

    /** @test */
    public function it_can_show_contacts_by_doctor_id()
    {
        $contact = Contact::factory()->create(['doctor_id' => $this->user->id]);

        $response = $this->getJson("/api/contact/{$this->user->id}");

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'value',
                     'data' => [
                         '*' => [
                             'id',
                             'message',
                             'updated_at'
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function it_can_update_a_contact()
    {
        $contact = Contact::factory()->create(['doctor_id' => $this->user->id]);
        
        $updateData = [
            'message' => 'Updated message'
        ];

        $response = $this->putJson("/api/contact/{$contact->id}", $updateData);

        $response->assertStatus(201)
                 ->assertJson([
                     'value' => true,
                     'message' => 'Contact Updated Successfully'
                 ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'message' => 'Updated message'
        ]);
    }

    /** @test */
    public function it_can_delete_a_contact()
    {
        $contact = Contact::factory()->create(['doctor_id' => $this->user->id]);

        $response = $this->deleteJson("/api/contact/{$contact->id}");

        $response->assertStatus(201)
                 ->assertJson([
                     'value' => true,
                     'message' => 'Contact Deleted Successfully'
                 ]);

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id
        ]);
    }

    /** @test */
    public function it_returns_404_when_no_contacts_found()
    {
        $response = $this->getJson('/api/contact');

        $response->assertStatus(404)
                 ->assertJson([
                     'value' => false,
                     'message' => 'No Contact was found'
                 ]);
    }

    /** @test */
    public function it_validates_required_message_field()
    {
        $response = $this->postJson('/api/contact', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['message']);
    }
}
