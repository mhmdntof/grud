<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmailService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('RESEND_API_KEY');
    }

    public function sendOtp(string $email, string $otp): bool
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.resend.com/emails', [
            'from' => 'Hospital System <onboarding@resend.dev>',
            'to' => $email,
            'subject' => 'OTP Verification Code',
            'html' => "
                <div style='font-size:18px'>
                    <h2>Your OTP Code</h2>
                    <p style='font-size:24px'><b>{$otp}</b></p>
                    <p>This code is valid for 24 hours.</p>
                </div>
            ",
        ]);

        return $response->successful();
    }
}