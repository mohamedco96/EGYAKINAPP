<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\ContactController as V1ContactController;
use App\Modules\Contacts\Requests\StoreContactRequest;
use App\Modules\Contacts\Requests\UpdateContactRequest;

class ContactController extends Controller
{
    protected $contactController;

    public function __construct(V1ContactController $contactController)
    {
        $this->contactController = $contactController;
    }

    public function index()
    {
        return $this->contactController->index();
    }

    public function store(StoreContactRequest $request)
    {
        return $this->contactController->store($request);
    }

    public function show($id)
    {
        return $this->contactController->show($id);
    }

    public function update(UpdateContactRequest $request, $id)
    {
        return $this->contactController->update($request, $id);
    }

    public function destroy($id)
    {
        return $this->contactController->destroy($id);
    }
}
