<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Notifications\WelcomeMailNotification;
use Illuminate\Support\Facades\Storage;


class AuthController extends Controller
{
    public function register(Request $request)
    {
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
            'highestdegree' => 'required|string',
            'registration_number' => 'required|string',
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'lname' => $fields['lname'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'age' => $fields['age'],
            'specialty' => $fields['specialty'],
            'workingplace' => $fields['workingplace'],
            'phone' => $fields['phone'],
            'job' => $fields['job'],
            'highestdegree' => $fields['highestdegree'],
            'registration_number' => $fields['registration_number'],
        ]);

        $token = $user->createToken('apptoken')->plainTextToken;
        $user->notify(new WelcomeMailNotification());
        $response = [
            'value' => true,
            'data' => $user,
            'token' => $token,
        ];

        return response($response, 200);
    }

    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $fields['email'])->first();

        if (! $user || ! Hash::check($fields['password'], $user->password)) {
            $response = [
                'value' => false,
                'message' => 'wrong data',
            ];

            return response($response, 404);
        }elseif ($user->blocked){
            $response = [
                'value' => false,
                'message' => 'User is Blocked',
            ];
            return response($response, 404);
        }
        else {
            $token = $user->createToken('apptoken')->plainTextToken;
            $response = [
                'value' => true,
                'data' => $user,
                'token' => $token,
            ];

            return response($response, 200);
        }
    }

    public function logout(Request $request)
    {
        //auth()->user()->tokens()->delete();
        $request->user()->currentAccessToken()->delete();
        return [
            'value' => true,
            'message' => 'Logged out',
        ];
    }

    public function uploadProfileImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // max 2MB
        ]);

        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');

            // Generate a unique file name using the original file name and a timestamp
            $fileName = time() . '_' . $image->getClientOriginalName();

            // Store the image in the specified directory with the generated file name
            $path = $image->storeAs('profile_images', $fileName, 'public');

            // Get the absolute URL of the uploaded image
            //$absolutePath = url(Storage::url($path));

            // Get the relative path of the uploaded image (without the storage folder prefix)
            $relativePath = 'storage/' . $path;

            // Update user's profile image path in the database
            auth()->user()->update(['image' => $path]);

            // Construct the full URL by appending the relative path to the APP_URL
            $imageUrl = config('app.url') . '/' . 'storage/app/public/' . $path;

            return response()->json([
                'success' => true,
                'message' => 'Profile image uploaded successfully.',
                'image_path' => $imageUrl,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Please choose an image file.',
        ], 400);
    }


    public function uploadProfileImagebkp(Request $request)
    {
        $request->validate([
            'profile_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // max 2MB
        ]);

        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');

            $path = $image->store('profile_images', 'public');

            // Get the absolute URL of the uploaded image
            $absolutePath = url(Storage::url($path));

            // Update user's profile image path in the database
            auth()->user()->update(['image' => $absolutePath]);

            return response()->json([
                'success' => true,
                'message' => 'Profile image uploaded successfully.',
                'image_path' => $absolutePath,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Please choose an image file.',
        ], 400);
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
            'highestdegree' => 'string',
            'registration_number' => 'string',
        ]);

        if ($user != null) {
            $user->update($request->all());
            $response = [
                'value' => true,
                'data' => $user,
                'message' => 'User Updated Successfully',
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No User was found',
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

        if ($user != null) {
            $response = [
                'value' => true,
                'data' => $user,
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No user was found',
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

        if ($user != null) {
            $patientCount = $user->patients->count();
            if ($patientCount != null) {
                $count = $patientCount;
            } else {
                $count = 0;
            }

            $scoreValue = $user->score->score;
            if ($scoreValue != null) {
                $score = $scoreValue;
            } else {
                $score = 0;
            }
            $count = strval($count); // Convert count to a string
            $score = strval($score); // Convert count to a string
            $response = [
                'value' => true,
                'patient_count' => $count,
                'score_value' => $score,
                'data' => $user,
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No user was found',
            ];

            return response($response, 404);
        }
    }

    public function userPatient()
    {
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

        if ($user != null) {
            User::destroy($id);

            DB::table('patient_histories')->where('doctor_id', '=', $id)->delete();
            DB::table('sections')->where('doctor_id', '=', $id)->delete();
            DB::table('complaints')->where('doctor_id', '=', $id)->delete();
            DB::table('causes')->where('doctor_id', '=', $id)->delete();
            DB::table('risks')->where('doctor_id', '=', $id)->delete();
            DB::table('assessments')->where('doctor_id', '=', $id)->delete();
            DB::table('examinations')->where('doctor_id', '=', $id)->delete();

            $response = [
                'value' => true,
                'message' => 'User Deleted Successfully',
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No User was found',
            ];

            return response($response, 404);
        }
    }
}
