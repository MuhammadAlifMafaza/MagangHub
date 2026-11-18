<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\CreateAccount;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->icon(Heroicon::OutlinedPlus);
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->icon(Heroicon::OutlinedPlus);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->icon(Heroicon::OutlinedXMark);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateAccount::class)->handle(auth()->user(), $data);
    }
}
