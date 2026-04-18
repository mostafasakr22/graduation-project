<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
{
    // 1. التحقق من البيانات
    $validator = \Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|unique:users',
        'password' => 'required|string|min:6|confirmed',
        'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'fail',
            'data' => $validator->errors()
        ], 422);
    }

    // 2. معالجة رفع الصورة
    $imagePath = null;
    if ($request->hasFile('profile_image')) {
        // سيتم تخزينها في storage/app/public/profile_images
        // وترجع المسار: "profile_images/filename.jpg"
        $imagePath = $request->file('profile_image')->store('profile_images', 'public');
    }

    // 3. إنشاء المستخدم
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => \Hash::make($request->password),
        'profile_image' => $imagePath,
    ]);

    // 4. إنشاء التوكن
    $token = $user->createToken('api_token')->plainTextToken;

    // 5. الرد (Response) مع الرابط الكامل للصورة
    return response()->json([
        'status' => 'success',
        'data' => [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $user->profile_image ? url('storage/' . $user->profile_image) : null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'token' => $token,
        ]
    ], 201);
}

    // Login
    public function login(Request $request)
{
    // 1. التحقق من البيانات
    $validator = \Validator::make($request->all(), [
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'fail',
            'data' => $validator->errors()
        ], 422);
    }

    // 2. البحث عن المستخدم بالبريد الإلكتروني
    $user = User::where('email', $request->email)->first();

    // 3. التأكد من وجود المستخدم وصحة كلمة المرور
    if (!$user || !\Hash::check($request->password, $user->password)) {
        return response()->json([
            'status' => 'fail',
            'data' => [
                'credentials' => ['The provided credentials are incorrect.']
            ]
        ], 401);
    }

    // 4. إنشاء التوكن
    $token = $user->createToken('api_token')->plainTextToken;

    // 5. الرد (Response) مع رابط الصورة الكامل
    return response()->json([
        'status' => 'success',
        'data' => [
            'user' => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'role'          => $user->role, 
                'profile_image' => $user->profile_image ? url('storage/' . $user->profile_image) : null,
                'created_at'    => $user->created_at,
            ],
            'token' => $token,
        ]
    ]);
}

    // Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'Logged out successfully'
            ]
        ]);
    }
}
