<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|email|unique:users|string',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|string|min:8|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "data" => [
                    "errors" => $validator->errors() // Perbaikan di sini
                ]
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken("tokenName")->plainTextToken;
        $expiresAt = date('Y-m-d H:i:s', strtotime('+60 minutes')); // Set expiration time to 60 minutes from now

        return response()->json([
            "data" => [
                "user" => $user,
                "token" => $token,
                "expires_at" => $expiresAt, // Add expiration time to response
                "message" => "Register Success"
            ]
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required', // Bisa username atau email
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "data" => [
                    "errors" => $validator->errors() // Perbaikan di sini
                ]
            ], 422);
        }

        $user = User::where('username', $request->identifier)
            ->orWhere('email', $request->identifier)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $token = $user->createToken("tokenName")->plainTextToken;
        $expiresAt = date('Y-m-d H:i:s', strtotime('+60 minutes')); // Set expiration time to 60 minutes from now

        return response()->json([
            "data" => [
                "user" => $user,
                "token" => $token,
                "expires_at" => $expiresAt, // Add expiration time to response
                "message" => "Login Success"
            ]
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                "data" => [
                    "message" => "Logout Success"
                ]
            ]);
        }

        return response()->json(['message' => 'Unauthorized'], 401); // Tambahkan pengecekan
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|string',
            'about' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "data" => [
                    "errors" => $validator->errors() // Perbaikan di sini
                ]
            ], 422);
        }

        $user = $request->user();
        $user->update([
            'name' => $request->name,
            'about' => $request->about,
        ]);

        return response()->json([
            "data" => [
                "user" => $user,
                "message" => "Profile updated successfully"
            ]
        ]);
    }

    public function updateImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "data" => [
                    "errors" => $validator->errors()
                ]
            ], 422);
        }

        $user = $request->user();

        // Delete old image if it exists  
        if ($user->image) {
            // Ensure the path is correct and delete the old image  
            Storage::disk('public')->delete($user->image);
        }

        // Get the original filename  
        $originalFilename = $request->file('image')->getClientOriginalName();

        // Store new image in the "public/profile_images" folder with the original filename  
        $path = $request->file('image')->storeAs('profile_images', $originalFilename, 'public');

        // Create full URL with the application's base URL for response  
        $fullImageUrl = url('storage/' . $path);

        // Update user's image path with the relative path  
        $user->update([
            'image' => $path, // Save the relative path in the database  
        ]);

        return response()->json([
            "data" => [
                "user" => $user,
                "image_url" => $fullImageUrl, // URL to access the image  
                "message" => "Profile image updated successfully"
            ]
        ]);
    }

    // Change password
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'confirm_password' => 'required|string|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "data" => [
                    "errors" => $validator->errors() // Perbaikan di sini
                ]
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                "message" => "The old password is incorrect."
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            "data" => [
                "message" => "Password updated successfully"
            ]
        ]);
    }

    public function getUserById($id)
    {
        $user = User::findOrFail($id); // Menggunakan findOrFail

        return response()->json($user, 200);
    }
}
