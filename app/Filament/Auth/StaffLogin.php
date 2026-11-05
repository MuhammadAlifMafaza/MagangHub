<?php

namespace App\Filament\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class StaffLogin extends BaseLogin
{
    protected string $view = 'filament.auth.staff-login';

    protected function getEmailFormComponent(): Component
    {
        $isLecturerPanel = Filament::getCurrentOrDefaultPanel()->getId() === 'dosen';

        return TextInput::make('email')
            ->label($isLecturerPanel ? 'Email atau NIDN' : 'Alamat email')
            ->placeholder($isLecturerPanel ? 'nama@kampus.ac.id atau NIDN' : 'nama@kampus.ac.id')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    public function hasLogo(): bool
    {
        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[\SensitiveParameter] array $data): array
    {
        $identifier = Str::lower(trim((string) ($data['email'] ?? '')));
        $panelId = Filament::getCurrentOrDefaultPanel()->getId();
        $role = match ($panelId) {
            'admin' => UserRole::Admin,
            'dosen' => UserRole::Lecturer,
            default => null,
        };

        $users = User::query()
            ->where('role', $role)
            ->where(function (Builder $query) use ($identifier, $panelId): void {
                $query->whereRaw('LOWER(email) = ?', [$identifier]);

                if ($panelId === 'dosen') {
                    $query->orWhereHas('lecturer', fn (Builder $lecturerQuery): Builder => $lecturerQuery->where('nidn', $identifier));
                }
            })
            ->limit(2)
            ->get();

        $email = $users->count() === 1 ? $users->first()->email : $identifier;

        return parent::getCredentialsFromFormData([...$data, 'email' => $email]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Masuk Staff';
    }

    public function getHeading(): string|Htmlable|null
    {
        return Filament::getCurrentOrDefaultPanel()->getId() === 'dosen'
            ? 'Masuk Panel Dosen'
            : 'Masuk Panel Admin';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return Filament::getCurrentOrDefaultPanel()->getId() === 'dosen'
            ? 'Gunakan email atau NIDN dan kata sandi akun dosen Anda.'
            : 'Gunakan email dan kata sandi akun administrator Anda.';
    }
}
