<?php

namespace App\Http\Controllers;

use App\Models\PatientHistory;
use App\Http\Requests\StorePatientHistoryRequest;
use App\Http\Requests\UpdatePatientHistoryRequest;

class PatientHistoryController extends Controller
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
    public function store(StorePatientHistoryRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PatientHistory $patientHistory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PatientHistory $patientHistory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatientHistoryRequest $request, PatientHistory $patientHistory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PatientHistory $patientHistory)
    {
        //
    }
}
