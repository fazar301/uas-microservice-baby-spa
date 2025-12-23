<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function ($record) {
                    if ($record->id === auth()->id()) {
                        throw new \Exception('Anda tidak dapat menghapus akun sendiri.');
                    }
                })
                ->after(function ($record) {
                    Log::info('User deleted via Filament', [
                        'user_id' => auth()->id(),
                        'deleted_user_id' => $record->id,
                        'correlation_id' => request()->get('correlation_id'),
                    ]);
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove password from data if empty
        if (empty($data['password'])) {
            unset($data['password']);
        }

        Log::info('User update attempt via Filament', [
            'user_id' => auth()->id(),
            'target_user_id' => $this->record->id,
            'correlation_id' => request()->get('correlation_id'),
        ]);

        return $data;
    }

    protected function afterSave(): void
    {
        Log::info('User updated successfully via Filament', [
            'user_id' => auth()->id(),
            'target_user_id' => $this->record->id,
            'correlation_id' => request()->get('correlation_id'),
        ]);
    }
}

