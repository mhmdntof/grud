<?php

namespace App\Services;

use App\Models\UserOtp;

class OtpService
{
    public function generate($user)
    {
        $otp = rand(100000, 999999);

        UserOtp::create([
            'user_id' => $user->id,
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes(10),
        
        ]);

        return $otp;
    }
}