<?php

namespace App\Filament\Resources\BlockedSlotResource\Pages;

use App\Filament\Resources\BlockedSlotResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlockedSlot extends EditRecord
{
    protected static string $resource = BlockedSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
