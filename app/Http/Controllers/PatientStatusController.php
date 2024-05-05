<?php

namespace App\Http\Controllers;

use App\Models\PatientStatus;
use App\Http\Requests\StorePatientStatusRequest;
use App\Http\Requests\UpdatePatientStatusRequest;

class PatientStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePatientStatusRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PatientStatus $patientStatus)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PatientStatus $patientStatus)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatientStatusRequest $request, PatientStatus $patientStatus)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PatientStatus $patientStatus)
    {
        //
    }
}
