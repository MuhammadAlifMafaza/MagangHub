<?php

namespace App\Filament\Dosen\Pages;

use App\Enums\UserRole;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;

class Profile extends Page
{
    protected static ?string $title = 'Profil Saya';

    protected static ?string $navigationLabel = 'Profil Saya';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|\UnitEnum|null $navigationGroup = 'Akun';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.dosen.pages.profile';

    public string $name = '';

    public string $email = '';

    public string $department = '';

    public string $phone = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === UserRole::Lecturer, 403);

        $user = auth()->user()->loadMissing('lecturer');
        abort_if($user->lecturer === null, 403);

        $this->name = $user->name;
        $this->email = $user->email;
        $this->department = $user->lecturer->department ?? '';
        $this->phone = $user->lecturer->phone ?? '';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->role === UserRole::Lecturer && auth()->user()->lecturer !== null;
    }

    public function save(): void
    {
        $user = auth()->user();
        abort_unless($user->role === UserRole::Lecturer, 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'department' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->loadMissing('lecturer');
        abort_if($user->lecturer === null, 403);

        if ($user->email !== $validated['email']) {
            $user->email_verified_at = null;
        }

        $user->fill(['name' => $validated['name'], 'email' => $validated['email']])->save();
        $user->lecturer->fill([
            'department' => $validated['department'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ])->save();

        Notification::make()->success()->title('Profil dosen berhasil diperbarui.')->send();
    }
}
