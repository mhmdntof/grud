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


public function logina(LoginRequest $request)
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
public function logout(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json([
        'message' => 'Logged out'
    ]);
}



//تسجيل دخول الويب 

public function loginWeb(LoginRequest $request)
{
    $result = $this->authService->loginWeb(
        $request->validated()
    );

    if (isset($result['error'])) {
        return response()->json([
            'message' => $result['error']
        ], 401);
    }

    $request->session()->regenerate();

    return response()->json([
        'message' => 'Login successful'
    ]);
}



public function me()
{
    return response()->json([
        'auth_check' => Auth::check(),
        'user' => Auth::user(),
    ]);
}

// 

//
  // public function me(Request $request)
//{
// /** @var \App\Models\User $user */
//$user = $request->user();

//return response()->json([
  //  'id' => $user->id,
    //'name' => $user->name,
    //'email' => $user->email,
//]);
//}


}