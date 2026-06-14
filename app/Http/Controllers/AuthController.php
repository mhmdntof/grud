<?php

namespace App\Http\Controllers;
use App\Http\Requests\CreateHospitalManagerRequest;
use App\Services\AuthService;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\CreateEmployeeRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\SetPasswordRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Request;
use App\Services\OtpService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;



use App\Http\Requests\StoreProductRequest;

class AuthController extends Controller
{
    private AuthService $authService;
 private ProductService $productService;

    public function __construct(AuthService $authService,ProductService $productService)
    {
        $this->authService = $authService;
        $this->productService = $productService;
    }

    public function createUser(CreateUserRequest $request)
    {
        $this->authService->createUser(
            $request->validated()
        );

        return response()->json([
            'message' => 'User created, OTP sent to email'
        ]);
    }



   




public function createHospitalManager(CreateHospitalManagerRequest $request)
{
    return response()->json(
        $this->authService->createHospitalManager(
            $request->validated()
        )
    );

}

public function createEmployee(CreateEmployeeRequest $request)
{
    return response()->json(

        $this->authService->createEmployee(
            $request->validated()
        )

    );
}

public function verifyOtp(
    VerifyOtpRequest $request
)
{
    $result = $this->authService->verifyOtp(

        $request->validated()

    );

    // فشل التحقق
    if (!$result['success']) {

        return response()->json([

            'message' => $result['message']

        ], 400);
    }

    // نجاح التحقق
    return response()->json([

        'message' => $result['message'],

        'verification_token' => $result['token']

    ]);
}


public function setPassword(
    SetPasswordRequest $request
)
{
    $result = $this->authService->setPassword(

        $request->validated()

    );

    // فشل العملية
    if (!$result['success']) {

        return response()->json([

            'message' => $result['message']

        ], 400);
    }

    // نجاح العملية
    return response()->json([

        'message' => $result['message'],

        'token' => $result['token'],

        'user' => $result['user']

    ]);
}

public function resendOtp(
    ResendOtpRequest $request
)
{
    $result = $this->authService->resendOtp(

        $request->validated()

    );

    if (!$result['success']) {

        return response()->json([

            'message' => $result['message']

        ], 400);
    }

    return response()->json([

        'message' => $result['message']

    ]);
}







public function login(LoginRequest $request)
{
    $result = $this->authService->login(
        $request->validated()
    );

    if (isset($result['error'])) {
        return response()->json([
            'message' => $result['error']
        ], 401);
    }

    return response()->json([
        'token' => $result['token'],
        'user' => [
            'id' => $result['user']->id,
            'name' => $result['user']->name,
            'email' => $result['user']->email,
            'role_id' => $result['user']->role_id,
        ]
    ]);
}



public function sendOtp(Request $request, OtpService $otpService)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    // 1. نجيب المستخدم أو ننشئه
    $user = User::firstOrCreate(
        ['email' => $request->email],
        [
            'name' => 'employee',
            'status' => false
        ]
    );

    // 2. توليد OTP (Core Layer)
    $otp = $otpService->generate($user);

    // 3. إرسال الإيميل (Delivery Layer)
    try {
        Mail::raw("Your verification code is: $otp", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Verification Code');
        });
    } catch (\Exception $e) {
        // ما بنوقف النظام إذا فشل الإيميل
        Log::error('Email failed: ' . $e->getMessage());
    }

    return response()->json([
        'message' => 'OTP sent successfully'
    ]);
}


}