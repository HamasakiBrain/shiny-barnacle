<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function byEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function lock(int $id): User
    {
        return User::whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function token(User $user): string
    {
        return $user->createToken('api', ['*'], now()->addDay())->plainTextToken;
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
