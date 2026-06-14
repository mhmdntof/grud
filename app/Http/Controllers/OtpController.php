<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;


use App\Models\User;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    private OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    // 1️⃣ إرسال OTP
    public function sendOtp(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $otp = $this->otpService->generate($user);

        // طبقة الإرسال (Email) - optional
        try {
            Mail::raw("Your OTP is: $otp", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('OTP Code');
            });
        } catch (\Exception $e) {
            // ما منوقف النظام
        }

        return response()->json([
            'message' => 'OTP sent'
        ]);
    }

    // 2️⃣ التحقق من OTP
    public function verify(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $otp = $this->otpService->verify($user->id, $request->otp);

        if (!$otp) {
            return response()->json(['error' => 'Invalid OTP'], 400);
        }

        $this->otpService->markUsed($otp);

        // تسجيل دخول بعد التحقق
       Auth::login($user);

        return response()->json([
            'message' => 'OTP verified & logged in'
        ]);
    }
}