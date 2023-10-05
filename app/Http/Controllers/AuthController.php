<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request) {
        $fields = $request->validate([
            'name' => 'required|string',
            'lname' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed',
            'age' => 'integer',
            'specialty' => 'required|string',
            'workingplace' => 'required|string',
            'phone' => 'required|string',
            'job' => 'required|string',
            'highestdegree' => 'required|string'
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'lname' => $fields['lname'],
            'email' => $fields['email'],
            'password' => bcrypt( $fields['password']),
            'age' => $fields['age'],
            'specialty' => $fields['specialty'],
            'workingplace' => $fields['workingplace'],
            'phone' => $fields['phone'],
            'job' => $fields['job'],
            'highestdegree' => $fields['highestdegree']
        ]);

        $token = $user->createToken('apptoken')->plainTextToken;

        $response = [
            'value' => true,
            'data' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }

    public function login(Request $request) {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $fields['email'])->first();

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            $response = [
                'value' => false,
                'message' => 'wrong data'
            ];
            return response($response, 404);
        } else{
            $token = $user->createToken('apptoken')->plainTextToken;
            $response = [
                'value' => true,
                'data' => $user,
                'token' => $token
            ];
            return response($response, 201);
        }
    }

    public function logout(Request $request) {
        auth()->user()->tokens()->delete();
        return [
            'value' => true,
            'message' => 'Logged out'
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        $fields = $request->validate([
            'name' => 'string',
            'lname' => 'string',
            'email' => 'string|email',
            'password' => 'string',
            'age' => 'integer',
            'specialty' => 'string',
            'workingplace' => 'string',
            'phone' => 'string',
            'job' => 'string',
            'highestdegree' => 'string'
        ]);

        if($user!=null){
            $user->update($request->all());
            $response = [
                'value' => true,
                'data' => $user,
                'message' => 'User Updated Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No User was found'
            ];
            return response($response, 404);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::all();

        if($user!=null){
            $response = [
                'value' => true,
                'data' => $user
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No user was found'
            ];
            return response($response, 404);
        }

    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::find($id);

        if($user!=null){
            $patientCount = $user->patients->count();
            if($patientCount!=null){
                $count = $patientCount;
            }else{
                $count = 0;
            }

            $response = [
                'value' => true,
                'patientCount' => $count,
                'data' => $user
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No user was found'
            ];
            return response($response, 404);
        }
    }

    public function userPatient(){
        $user = User::find(1);
        $patientCount = $user->patients->count();
        return $patientCount;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if($user!=null){
            User::destroy($id);

            DB::table('patient_histories')->where('owner_id', '=', $id)->delete();
            DB::table('sections')->where('owner_id', '=', $id)->delete();
            DB::table('complaints')->where('owner_id', '=', $id)->delete();
            DB::table('causes')->where('owner_id', '=', $id)->delete();
            DB::table('risks')->where('owner_id', '=', $id)->delete();
            DB::table('assessments')->where('owner_id', '=', $id)->delete();

            $response = [
                'value' => true,
                'message' => 'User Deleted Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No User was found'
            ];
            return response($response, 404);
        }
    }


}
