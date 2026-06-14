<?php

namespace App\Services;

use App\Models\UserOtp;

class OtpService
{
    public function generate($user)
    {
        $otp = rand(100000, 999999);

        // حذف أي OTP قديم للمستخدم
        UserOtp::where('user_id', $user->id)->delete();

        UserOtp::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        return $otp;
    }

    public function verify($userId, $otp)
    {
        return UserOtp::where('user_id', $userId)
            ->where('otp', $otp)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function deleteOtp($userId)
    {
        UserOtp::where('user_id', $userId)->delete();
    }
}