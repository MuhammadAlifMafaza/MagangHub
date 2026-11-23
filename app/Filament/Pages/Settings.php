<?php

namespace App\Filament\Pages;

use App\Concerns\PasswordValidationRules;
use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class Settings extends Page
{
    use PasswordValidationRules;

    protected static ?string $title = 'Pengaturan Administrator';

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Akun';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.settings';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $exception) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $exception;
        }

        auth()->user()->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
        ])->save();

        $this->reset('current_password', 'password', 'password_confirmation');

        Notification::make()->success()->title('Kata sandi berhasil diperbarui.')->send();
    }

    protected function getViewData(): array
    {
        return [
            'accountsUrl' => UserResource::getUrl('index', panel: 'admin'),
            'recapUrl' => AdministrativeRecap::getUrl(panel: 'admin'),
            'dashboardUrl' => route('filament.admin.pages.dashboard'),
        ];
    }
}
