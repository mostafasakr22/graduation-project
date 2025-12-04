<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OTPMail;
use App\Models\User;
use App\Models\PasswordOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    // Send OTP
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'data'   => $validator->errors()
            ], 422);
        }

        $email = $request->email;
        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(10);

        // Store OTP in DB
        PasswordOtp::updateOrCreate(
            ['email' => $email],
            [
                'otp' => $otp,
                'expires_at' => $expiresAt,
            ]
        );

        // Send OTP Email
        Mail::to($email)->send(new OTPMail($otp));

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'OTP sent successfully'
            ]
        ]);
    }


    // Verify OTP
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp'   => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'data'   => $validator->errors()
            ], 422);
        }

        $otpData = PasswordOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>=', Carbon::now())
            ->first();

        if (!$otpData) {
            return response()->json([
                'status' => 'fail',
                'data'   => ['message' => 'Invalid or expired OTP']
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'OTP verified successfully'
            ]
        ]);
    }


    // Reset Password
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:users,email',
            'otp'      => 'required|numeric',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'data'   => $validator->errors()
            ], 422);
        }

        $otpData = PasswordOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>=', Carbon::now())
            ->first();

        if (!$otpData) {
            return response()->json([
                'status' => 'fail',
                'data'   => ['message' => 'Invalid or expired OTP']
            ], 400);
        }

        // Update Password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete OTP
        $otpData->delete();

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'Password reset successfully'
            ]
        ]);
    }
}
