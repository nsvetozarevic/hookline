<?php

declare(strict_types=1);

namespace Interfaces\Panel\Fortify;

use Domain\User\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ])->validate();

        $user = new User();
        $user->name = (string) $input['name'];
        $user->email = (string) $input['email'];
        $user->password = (string) $input['password'];
        $user->save();

        return $user;
    }
}
