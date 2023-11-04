<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Contact = Contact::with('doctor:id,name,lname')->latest()->get();

        if($Contact->isNotEmpty()){
            $response = [
                'value' => true,
                'data' => $Contact
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Contact was found'
            ];
            return response($response, 404);
        }
    }

    //@param \Illuminate\Http\Request $request
    // @return \Illuminate\Http\Response
    public function store(StoreContactRequest $request)
    {
        $Contact = Contact::create([
            'doctor_id' => Auth::id(),
            'message' => $request->message
        ]);

        if($Contact!=null){
            $response = [
                'value' => true,
                'message' => 'Contact Created Successfully'
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Contact was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $Contact = Contact::where('doctor_id', $id)
        ->select('id','message','updated_at')
        ->latest('updated_at')->get();

        if($Contact->isNotEmpty()){
            $response = [
                'value' => true,
                'data' => $Contact
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Contact was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContactRequest $request, $id)
    {
        $Contact = Contact::where('id', $id)->first();

        if($Contact!=null){
            $Contact->update($request->all());
            $response = [
                'value' => true,
                'data' => $Contact,
                'message' => 'Contact Updated Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Contact was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Contact = Contact::where('id', $id)->first();

        if($Contact!=null){
            DB::table('contacts')->where('id', $id)->delete();
            $response = [
                'value' => true,
                'message' => 'Contact Deleted Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Contact was found'
            ];
            return response($response, 404);
        }
    }
}
