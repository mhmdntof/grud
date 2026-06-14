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
use App\Services\OtpService;
use Illuminate\Http\Request;
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



public function sendOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    try {

        Log::info('Before Mail');

        Mail::raw('Test Email From Render', function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Mail Test');
        });

        Log::info('After Mail');

        return response()->json([
            'message' => 'Mail sent successfully'
        ]);

    } catch (\Throwable $e) {

        Log::error($e->getMessage());

        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

}