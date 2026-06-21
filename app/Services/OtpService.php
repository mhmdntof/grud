<?php

namespace App\Services;

use App\Models\UserOtp;
use App\Models\User;
use Illuminate\Support\Str;

class OtpService
{
   public function generate(User $user)
{
    $otp = rand(100000, 999999);

    UserOtp::where('user_id', $user->id)->delete();

    UserOtp::create([
        'user_id' => $user->id,
        'otp' => $otp,
        'expires_at' => now()->addMinutes(10),
    ]);

    return $otp;
}
  public function verifyOtp(array $data)
{
    $user = User::where('email', $data['email'])->first();

    if (!$user) {
        return ['error' => 'User not found'];
    }

    $otp = UserOtp::where('user_id', $user->id)
        ->where('otp', $data['otp'])
        ->first();

    if (!$otp) {
        return ['error' => 'Invalid OTP'];
    }

    if ($otp->expires_at < now()) {
        $otp->delete();
        return ['error' => 'OTP expired'];
    }

    // حذف OTP
    $otp->delete();

    // إنشاء verification token
    $token = Str::uuid();

    $user->update([
        'verification_token' => $token
    ]);

    return [
        'message' => 'OTP verified',
        'verification_token' => $token
    ];
}
}