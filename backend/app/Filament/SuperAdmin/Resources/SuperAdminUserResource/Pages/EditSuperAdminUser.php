<?php

namespace App\Filament\SuperAdmin\Resources\SuperAdminUserResource\Pages;

use App\Filament\SuperAdmin\Resources\SuperAdminUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSuperAdminUser extends EditRecord
{
    protected static string $resource = SuperAdminUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}