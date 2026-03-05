<?php

namespace App\Http\Controllers\Api\Auth;
use Laravel\Passport\PersonalAccessTokenResult;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        // Attempt to authenticate the user using the provided credentials
        if (Auth::attempt($credentials)) {
            // Generate a personal access token for the authenticated user
            $tokenResult = Auth::user()->createToken('token');
            $accessToken = $tokenResult->accessToken;
            
            return response()->json([
                'status' => [
                    "status" => "true",
                    "message" => "Login successfully",
                    "http_code" => 200
                ],
                "data" => [
                    "token" => $accessToken
                ]
            ], 200);
        }

        return response()->json([
            'status' => [
                "status" => "false",
                "message" => "Unauthorized",
                "http_code" => 401
            ],
            "data" => null
        ], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'status' => [
                "status" => "true",
                "message" => "Logged out successfully",
                "http_code" => 200
            ],
            "data" => null
        ], 200);
    }
}
