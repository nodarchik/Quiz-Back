<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class   AuthController extends Controller
{
    public function __construct(private AuthService $authService){}

    public function getUser(): JsonResponse
    {
        return response()->json(auth()->user());
    }
    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->authService->register($request->validated());
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return $this->authService->login($request->validated());
    }
    public function logout(): JsonResponse
    {
        return $this->authService->logout();
    }
}
