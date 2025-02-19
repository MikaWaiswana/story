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
                    "errors" => $validator->errors()
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
        $expiresAt = date('Y-m-d H:i:s', strtotime('+60 minutes'));
        $ngrokUrl = env('NGROK_URL');
        $imageUrl = $ngrokUrl . '/storage/' . $user->image;

        return response()->json([
            "data" => [
                "id" => $user->id,
                "name" => $user->name,
                "username" => $user->username,
                "email" => $user->email,
                "image" => $imageUrl,
                "about" => $user->about,
                "token" => $token,
                "expires_at" => $expiresAt,
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
                "errors" => $validator->errors()
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
        $expiresAt = date('Y-m-d H:i:s', strtotime('+60 minutes'));
        $ngrokUrl = env('NGROK_URL');
        $imageUrl = $ngrokUrl . '/storage/' . $user->image;

        return response()->json([
            "id" => $user->id,
            "name" => $user->name,
            "username" => $user->username,
            "email" => $user->email,
            "image" => $imageUrl, // Menggunakan URL gambar yang telah dibentuk
            "about" => $user->about,
            "token" => $token,
            "expires_at" => $expiresAt,
            "message" => "Login Success"
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

        return response()->json(['message' => 'Unauthorized'], 401);
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
                    "errors" => $validator->errors()
                ]
            ], 422);
        }

        $user = $request->user();
        $user->update([
            'name' => $request->name,
            'about' => $request->about,
        ]);
        $ngrokUrl = env('NGROK_URL');

        $imageUrl = $ngrokUrl . '/storage/' . $user->image;

        return response()->json([
            "id" => $user->id,
            "name" => $user->name,
            "username" => $user->username,
            "email" => $user->email,
            "image" => $imageUrl,
            "about" => $user->about,
            "message" => "Profile updated successfully"
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

        if ($user->image) {
            Storage::disk('public')->delete($user->image);
        }

        $originalFilename = $request->file('image')->getClientOriginalName();

        $path = $request->file('image')->storeAs('profile_images', $originalFilename, 'public');
  
        $user->update([
            'image' => $path,
        ]);

        $ngrokUrl = env('NGROK_URL');

        $imageUrl = $ngrokUrl . '/storage/' . $user->image;

        return response()->json([
            "id" => $user->id,
            "name" => $user->name,
            "username" => $user->username,
            "email" => $user->email,
            "image" => $imageUrl,
            "about" => $user->about,
            "message" => "Profile image updated successfully"
        ]);
    }

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
                    "errors" => $validator->errors()
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
        $user = User::findOrFail($id);
        
        return response()->json($user, 200);
    }
}
