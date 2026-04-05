<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginUserRequest;
use App\Models\User;

class AuthController extends Controller
{
    use ApiResponses;

    // public function login(LoginUserRequest $request){


    //     $validated = $request->validated();

    //     if (!Auth::attempt($request->only('login', 'password'))){
            
    //         return $this->error('Login yoki parol xato!', 401);
    //     }
        
    //     $user = User::where('login', $validated['login'])->first();

    //     $tokenName = 'API Token for ' . $user->login;

    //     $token = $user->createToken($tokenName, ['*'])->plainTextToken;

    //     return response()->json([
    //         'message' => 'Authenticated',
    //         'token' => $token,
    //         'role' => $user->role
            
    //     ], 200);
        
        
        
    // }
    
    public function login(LoginUserRequest $request)
    {
        $validated = $request->validated();

        if (!Auth::attempt($request->only('login', 'password'))) {
            return $this->error('Login yoki parol xato!', 401);
        }

        $user = User::where('login', $validated['login'])->first();

        // 1. Удаляем все предыдущие токены этого пользователя перед созданием нового
        $user->tokens()->delete();

        $tokenName = 'API Token for ' . $user->login;

        // 2. Создаем новый чистый токен
        $token = $user->createToken($tokenName, ['*'])->plainTextToken;

        return response()->json([
            'message' => 'Authenticated',
            'token' => $token,
            'role' => $user->role
        ], 200);
    }


    public function logout(Request $request){
        $request -> user()->currentAccessToken()->delete();

        return $this->ok('Muvofaqiyatli chiqildi');
        
    }
}
