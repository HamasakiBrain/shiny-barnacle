<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = $this->users->create($data);

            return [
                'user' => $user->refresh(),
                'token' => $this->users->token($user),
            ];
        });
    }

    public function login(array $data): array
    {
        $user = $this->users->byEmail($data['email']);

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        return [
            'user' => $user,
            'token' => $this->users->token($user),
        ];
    }

    public function logout(User $user): void
    {
        $this->users->logout($user);
    }
}
