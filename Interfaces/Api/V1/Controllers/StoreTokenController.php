<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Controllers;

use Domain\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Interfaces\Api\Contracts\StoreTokenControllerContract;
use Interfaces\Api\V1\Requests\StoreTokenRequest;

class StoreTokenController implements StoreTokenControllerContract
{
    public function __invoke(StoreTokenRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check((string) $request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }
}
