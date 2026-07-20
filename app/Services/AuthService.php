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
use Illuminate\Support\Facades\DB;


use App\Mail\SendOtpMail;


class AuthService
{
public function createEmployee(array $data)
{
    $user = null;
    $otp = null;

    DB::transaction(function () use (
        $data,
        &$user,
        &$otp
    ) {

        // البحث عن الرول
        $role = Role::where('name', $data['role'])->first();

        if (!$role) {
            throw new \Exception('Role not found');
        }

        // البحث عن القسم
        $department = Department::where('name', $data['department'])->first();

        if (!$department) {
            throw new \Exception('Department not found');
        }


        // إنشاء المستخدم
        $user = User::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'role_id' => $role->id,
            'department_id' => $department->id,
            'status' => false,
        ]);


        // إنشاء OTP
        $otp = rand(100000, 999999);


        // حذف أي OTP قديم
        UserOtp::where('user_id', $user->id)->delete();


        // حفظ OTP جديد
        UserOtp::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'expires_at' => now()->addHours(24),
        ]);

    });


    // إرسال الإيميل بعد نجاح الـ Transaction
    


    if (!$user || !$otp) {
        throw new \Exception('Failed to create employee OTP.');
    }


    Mail::to($user->email)
        ->send(new SendOtpMail((string) $otp, $user));


    return [
        'user' => $user,
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
       // 'name' => $data['name'],
        'phone' => $data['phone'],
        'password' => Hash::make($data['password']),
        'status' => true,
        'verification_token' => null,
    ]);

    return [
        'message' => 'Account activated successfully'
    ];
}




public function resendOtp(string $email)
{
    $user = User::where('email', $email)->first();

    if (!$user) {
        throw new \Exception('User not found.');
    }

    $otp = null;

   DB::transaction(function () use (
    $user,
    &$otp
) {

    $otp = (string) rand(100000, 999999);

    UserOtp::where('user_id', $user->id)->delete();

    UserOtp::create([
        'user_id' => $user->id,
        'otp' => $otp,
        'expires_at' => now()->addHours(24),
    ]);

});


if ($otp === null) {
    throw new \Exception('Failed to generate OTP.');
}


Mail::to($user->email)
    ->send(new SendOtpMail($otp, $user));


    return [
        'message' => 'OTP sent successfully.',
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


//المستخدم الحالي 




public function getCurrentUser()
{
    /** @var User $user */
    $user = User::with(['role', 'department'])
        ->findOrFail(Auth::id());

    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
        'role' => $user->role->name,
        'department' => $user->department?->name,
    ];
}

}