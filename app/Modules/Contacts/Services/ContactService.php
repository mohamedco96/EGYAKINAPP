<?php

namespace App\Modules\Contacts\Services;

use App\Modules\Contacts\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ContactService
{
    /**
     * Get all contacts with doctor relationships
     */
    public function getAllContacts(): Collection
    {
        return Contact::with('doctor:id,name,lname')->latest()->get();
    }

    /**
     * Create a new contact
     */
    public function createContact(array $data): Contact
    {
        return Contact::create([
            'doctor_id' => Auth::id(),
            'message' => $data['message'],
        ]);
    }

    /**
     * Get contacts by doctor ID
     */
    public function getContactsByDoctorId(int $doctorId): Collection
    {
        return Contact::where('doctor_id', $doctorId)
            ->select('id', 'message', 'updated_at')
            ->latest('updated_at')
            ->get();
    }

    /**
     * Update a contact by ID
     */
    public function updateContact(int $contactId, array $data): ?Contact
    {
        $contact = Contact::where('id', $contactId)->first();
        
        if ($contact) {
            $contact->update($data);
            return $contact;
        }
        
        return null;
    }

    /**
     * Delete a contact by ID
     */
    public function deleteContact(int $contactId): bool
    {
        $contact = Contact::where('id', $contactId)->first();
        
        if ($contact) {
            return DB::table('contacts')->where('id', $contactId)->delete() > 0;
        }
        
        return false;
    }

    /**
     * Check if contact exists
     */
    public function contactExists(int $contactId): bool
    {
        return Contact::where('id', $contactId)->exists();
    }
}
