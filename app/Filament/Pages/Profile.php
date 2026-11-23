<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;

class Profile extends Page
{
    protected static ?string $title = 'Profil Administrator';

    protected static ?string $navigationLabel = 'Profil Administrator';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|\UnitEnum|null $navigationGroup = 'Akun';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.profile';

    public string $name = '';

    public string $email = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === UserRole::Admin, 403);

        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public function save(): void
    {
        $user = auth()->user();
        abort_unless($user->role === UserRole::Admin, 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        if ($user->email !== $validated['email']) {
            $user->email_verified_at = null;
        }

        $user->fill($validated)->save();

        Notification::make()->success()->title('Profil administrator berhasil diperbarui.')->send();
    }
}
