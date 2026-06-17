<?php

namespace App\Services;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\UserOtp;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Department;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

class AuthService
{
 public function createEmployee(array $data)
{
    // البحث عن الرول
    $role = Role::where('name', $data['role'])->first();

    if (!$role) {
        return [
            'error' => 'Role not found'
        ];
    }

    // البحث عن القسم
    $department = Department::where('name', $data['department'])->first();

    if (!$department) {
        return [
            'error' => 'Department not found'
        ];
    }

    // إنشاء المستخدم
    $user = User::create([
        'name' => $data['name'] ?? null,   // 👈 حماية إضافية
        'email' => $data['email'],
        'role_id' => $role->id,
        'department_id' => $department->id,
        'status' => false,
    ]);

    // إنشاء OTP
    $otp = rand(100000, 999999);

    // حذف أي OTP قديم
    UserOtp::where('user_id', $user->id)->delete();

    // إنشاء OTP جديد
    UserOtp::create([
        'user_id' => $user->id,
        'otp' => $otp,
        'expires_at' => now()->addHours(24),
    ]);

    return [
        'user' => $user,
        'otp' => $otp,
    ];
}
  public function login(array $data)
{
    // 1. البحث عن المستخدم
    $user = User::where('email', $data['email'])->first();

    if (!$user) {
        return ['error' => 'Invalid credentials'];
    }

    // 2. تحقق من كلمة المرور
    if (!Hash::check($data['password'], $user->password)) {
        return ['error' => 'Invalid credentials'];
    }

    // 3. تحقق من تفعيل الحساب
    if (!$user->status) {
        return ['error' => 'Account not activated'];
    }

    // 4. إنشاء token
    $token = $user->createToken('auth_token')->plainTextToken;

    return [
        'token' => $token,
        'user' => $user
    ];
}


public function createHospitalManager(array $data)
{
    // 1. جلب رول hospital_manager
    $role = Role::where('name', 'hospital_manager')->firstOrFail();

    // 2. إنشاء المستخدم (مفعل مباشرة)
    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
        'role_id' => $role->id,
        'status' => true,
    ]);

    return [
        'message' => 'Hospital manager created successfully',
        'user' => $user
    ];
}





public function verifyOtp(array $data)
{
    // 1. جلب المستخدم
    $user = User::where(
        'email',
        $data['email']
    )->first();

    // 2. تحقق من وجود المستخدم
    if (!$user) {

        return [
            'success' => false,
            'message' => 'User not found'
        ];
    }

    // 3. جلب سجل OTP
    $otpRecord = UserOtp::where(
        'user_id',
        $user->id
    )
    ->where(
        'otp',
        $data['otp']
    )
    ->first();

    // 4. تحقق من صحة OTP
    if (!$otpRecord) {

        return [
            'success' => false,
            'message' => 'Invalid OTP'
        ];
    }

    // 5. تحقق من انتهاء الصلاحية
    if ($otpRecord->expires_at < now()) {

        return [
            'success' => false,
            'message' => 'OTP expired'
        ];
    }

    // 6. إنشاء verification token
    $token = bin2hex(
        random_bytes(32)
    );

    // 7. تخزين التوكن
    $user->update([

        'verification_token' => $token

    ]);

    // 8. نجاح العملية
    return [

        'success' => true,

        'message' => 'OTP verified',

        'token' => $token
    ];
}


public function setPassword(array $data)
{
    $user = User::where('verification_token', $data['verification_token'])->first();

    if (!$user) {
        return ['error' => 'Invalid token'];
    }

    $user->update([
        'name' => $data['name'],
        'phone' => $data['phone'],
        'password' => Hash::make($data['password']),
        'status' => true,
        'verification_token' => null,
    ]);

    return [
        'message' => 'Account activated successfully'
    ];
}

public function resendOtp(array $data)
{
    // 1. جلب المستخدم
    $user = User::where(
        'email',
        $data['email']
    )->first();

    // 2. تحقق من وجود المستخدم
    if (!$user) {

        return [

            'success' => false,

            'message' => 'User not found'
        ];
    }

    // 3. حذف OTP القديم
    UserOtp::where(
        'user_id',
        $user->id
    )->delete();

    // 4. إنشاء OTP جديد
    $otp = rand(100000, 999999);

    // 5. تخزين OTP الجديد
    UserOtp::create([

        'user_id' => $user->id,

        'otp' => $otp,

       'expires_at' => now()->addHours(24),
    ]);

    // 6. إرسال الإيميل
    Mail::to($user->email)->send(

        new OtpMail($otp)

    );

    return [

        'success' => true,

        'message' => 'OTP resent successfully'
    ];
}



// تسجيل دخول الويب 

public function loginWeb(array $data)
{
    $user = User::where('email', $data['email'])->first();

    if (!$user || !Hash::check($data['password'], $user->password)) {
        return ['error' => 'Invalid credentials'];
    }

    if (!$user->status) {
        return ['error' => 'Account not activated'];
    }

    Auth::login($user);

    return [
        'user' => $user
    ];
}




}