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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;



use App\Http\Requests\StoreProductRequest;

class AuthController extends Controller
{
    private AuthService $authService;
 private ProductService $productService;
 private OtpService $otpService;

    public function __construct(AuthService $authService,ProductService $productService, OtpService $otpService)
    {
        $this->authService = $authService;
        $this->productService = $productService;
         $this->otpService = $otpService;

    }

   public function createEmployee(CreateEmployeeRequest $request)
{
    $result = $this->authService->createEmployee(
        $request->validated()
    );

    if (isset($result['error'])) {
        return response()->json([
            'message' => $result['error']
        ], 400);
    }

    return response()->json([
        'message' => 'Employee created successfully',
       
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





public function setPassword(SetPasswordRequest $request)
{
    $result = $this->authService->setPassword(
        $request->validated()
    );

    if (isset($result['error'])) {
        return response()->json([
            'message' => $result['error']
        ], 400);
    }

    return response()->json($result);
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

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    $otp = $otpService->generate($user);

    return response()->json([
        'message' => 'OTP generated successfully',
        'otp' => $otp,
    ]);
}


public function verifyOtp(VerifyOtpRequest $request)
{
    $result = $this->otpService->verifyOtp(
        $request->validated()
    );

    if (isset($result['error'])) {
        return response()->json([
            'message' => $result['error']
        ], 400);
    }

    return response()->json($result);
}

}