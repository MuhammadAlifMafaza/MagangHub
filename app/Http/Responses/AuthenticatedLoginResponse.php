<?php

namespace App\Http\Responses;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse;

class AuthenticatedLoginResponse implements LoginResponse
{
    public function toResponse(mixed $request): RedirectResponse
    {
        abort_unless($request instanceof Request, 500);

        $destination = match ($request->user()->role) {
            UserRole::Admin => '/admin',
            UserRole::Lecturer => '/dosen',
            UserRole::Student => route('dashboard', absolute: false),
        };

        return redirect()->intended($destination);
    }
}
