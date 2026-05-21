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


class AuthService
{
    public function createUser(array $data)
    {
        // 1. جلب الرول
        $role = Role::where('name', $data['role'])->firstOrFail();

        // 2. إنشاء المستخدم (inactive)
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $role->id,
            'status' => false,
            'password' => null,
        ]);

        // 3. توليد OTP
        $otp = rand(100000, 999999);

        // 4. تخزين OTP
        UserOtp::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
        ]);

        // 5. إرسال الإيميل
        Mail::to($user->email)->send(new OtpMail($otp));

        return true;
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



public function createEmployee(array $data)
{
    // 1. جلب الرول
    $role = Role::where('name', $data['role'])->firstOrFail();

    // 2. جلب القسم
    $department = Department::where(
        'name',
        $data['department']
    )->firstOrFail();

    // 3. إنشاء المستخدم
    $user = User::create([

        'name' => $data['name'],

        'email' => $data['email'],

        'role_id' => $role->id,

        'department_id' => $department->id,

        'status' => false,

        'password' => null,
    ]);

    // 4. توليد OTP
    $otp = rand(100000, 999999);

    // 5. تخزين OTP
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
        'message' => 'Employee created and OTP sent'
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
    // 1. جلب المستخدم عبر verification token
    $user = User::where(
        'verification_token',
        $data['verification_token']
    )->first();

    // 2. تحقق من وجود المستخدم
    if (!$user) {

        return [

            'success' => false,

            'message' => 'Invalid verification token'
        ];
    }

    // 3. تحديث كلمة المرور وتفعيل الحساب
    $user->update([

        'password' => Hash::make($data['password']),

        'status' => true,

        'verification_token' => null,
    ]);

    // 4. حذف OTP
    UserOtp::where(
        'user_id',
        $user->id
    )->delete();

    // 5. إنشاء access token (تسجيل دخول مباشر)
    $token = $user->createToken(
        'auth_token'
    )->plainTextToken;

    return [

        'success' => true,

        'message' => 'Password created successfully',

        'token' => $token,

        'user' => $user,
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


public function attemptLogin(array $credentials): bool
    {
        // محاولة تسجيل الدخول وتفعيل ميزة "تذكرني" (Remember Me) لتبقى الجلسة طويلة
        if (!Auth::attempt($credentials, true)) {
            throw ValidationException::withMessages([
                'email' => ['البيانات المدخلة غير صحيحة.'],
            ]);
        }

        return true;
    }



public function me()
{
    /** @var User $user */
    $user = Auth::user();

    return [
        'user' => $user,

        'roles' => $user->getRoleNames(),

        'permissions' => $user->getAllPermissions()
            ->pluck('name'),
    ];
}

}