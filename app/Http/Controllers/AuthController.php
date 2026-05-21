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


public function login(LoginRequest $request)
{
    $result = $this->authService->login(
        $request->validated()
    );

    if (isset($result['error'])) {
        return response()->json([
            'message' => $result['error']
        ], 400);
    }

    return response()->json($result);
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


public function loginWeb(LoginRequest $request): JsonResponse
    {
        // 1. استدعاء السيرفيس للتحقق من الحساب عبر البيانات التي تم التحقق منها (validated)
        $this->authService->attemptLogin($request->validated());

        // 2. تحديث الجلسة بعد نجاح الدخول (خطوة أمنية إجبارية في الـ SPA)
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Authenticated successfully',
            'user'    => Auth::user()
        ], 200);
    }

public function me()
{
    return response()->json(
        $this->authService->me()
    );
}


}