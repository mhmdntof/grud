<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use App\Services\AuthService;

class AuthWebController extends Controller
{

 private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

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
    $user = Auth::user();

    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role->name,
    ]);
}


public function logoutWeb(Request $request)
{
    Auth::logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();

    return response()->json([
        'message' => 'Logged out successfully'
    ]);
}

}
