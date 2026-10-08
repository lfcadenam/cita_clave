<?php

namespace App\Filament\SuperAdmin\Resources\SuperAdminUserResource\Pages;

use App\Filament\SuperAdmin\Resources\SuperAdminUserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdminUser extends CreateRecord
{
    protected static string $resource = SuperAdminUserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (filled($data['password'] ?? null)) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}