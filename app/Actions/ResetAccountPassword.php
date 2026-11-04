<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ResetAccountPassword
{
    public function handle(User $admin, User $user, string $temporaryPassword): void
    {
        Gate::forUser($admin)->authorize('resetPassword', $user);

        $user->forceFill([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ])->save();
    }
}
