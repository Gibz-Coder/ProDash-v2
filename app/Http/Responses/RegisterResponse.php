<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): Response
    {
        // Log out the user immediately after registration
        auth()->logout();

        // Redirect to login with a success message
        return $request->wantsJson()
            ? new JsonResponse(['message' => 'Registration successful. Please wait for admin verification.'], 201)
            : redirect()->route('login')->with('status', 'Registration successful! Your account is pending verification. Please contact Sir Gibz to activate your account.');
    }
}
