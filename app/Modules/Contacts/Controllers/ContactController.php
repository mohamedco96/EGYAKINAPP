<?php

namespace App\Modules\Contacts\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Requests\StoreContactRequest;
use App\Modules\Contacts\Requests\UpdateContactRequest;
use App\Modules\Contacts\Services\ContactNotificationService;
use App\Modules\Contacts\Services\ContactService;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    protected $contactService;

    protected $notificationService;

    public function __construct(
        ContactService $contactService,
        ContactNotificationService $notificationService
    ) {
        $this->contactService = $contactService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $contacts = $this->contactService->getAllContacts();

        if ($contacts->isNotEmpty()) {
            $response = [
                'value' => true,
                'data' => $contacts,
            ];

            return response()->json($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => __('api.no_contact_found'),
            ];

            return response()->json($response, 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = $this->contactService->createContact($request->validated());

        if ($contact) {
            // Send notification
            $this->notificationService->sendContactNotification($request->message);

            $response = [
                'value' => true,
                'message' => __('api.contact_created_successfully'),
            ];

            return response()->json($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => __('api.no_contact_found'),
            ];

            return response()->json($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $contacts = $this->contactService->getContactsByDoctorId($id);

        if ($contacts->isNotEmpty()) {
            $response = [
                'value' => true,
                'data' => $contacts,
            ];

            return response()->json($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => __('api.no_contact_found'),
            ];

            return response()->json($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContactRequest $request, int $id): JsonResponse
    {
        $contact = $this->contactService->updateContact($id, $request->validated());

        if ($contact) {
            $response = [
                'value' => true,
                'data' => $contact,
                'message' => __('api.contact_updated_successfully'),
            ];

            return response()->json($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => __('api.no_contact_found'),
            ];

            return response()->json($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->contactService->deleteContact($id);

        if ($deleted) {
            $response = [
                'value' => true,
                'message' => 'Contact Deleted Successfully',
            ];

            return response()->json($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => __('api.no_contact_found'),
            ];

            return response()->json($response, 404);
        }
    }
}
