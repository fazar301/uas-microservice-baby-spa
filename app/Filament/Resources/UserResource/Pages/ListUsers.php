<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tidak ada create action karena sudah ada fitur register
        ];
    }

    public function mount(): void
    {
        parent::mount();
        
        Log::info('User list accessed via Filament', [
            'user_id' => auth()->id(),
            'correlation_id' => request()->get('correlation_id'),
        ]);
    }
}

