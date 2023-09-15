<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request) {
        $fields = $request->validate([
            'fname' => 'required|string',
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
            'fname' => $fields['fname'],
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
        $patientCount = $user->patients->count();

        if($user!=null){
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

}
