<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function create(array $data): User;

    public function byEmail(string $email): ?User;

    public function lock(int $id): User;

    public function token(User $user): string;

    public function logout(User $user): void;
}
