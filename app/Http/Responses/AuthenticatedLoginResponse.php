<?php

namespace App\Http\Responses;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse;

class AuthenticatedLoginResponse implements LoginResponse
{
    public function toResponse(mixed $request): RedirectResponse
    {
        $destination = match (request()->user()->role) {
            UserRole::Admin => '/admin',
            UserRole::Lecturer => '/dosen',
            UserRole::Student => route('dashboard', absolute: false),
        };

        return redirect()->intended($destination);
    }
}
