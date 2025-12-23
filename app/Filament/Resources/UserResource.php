<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'pengguna';

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Manajemen Pengguna';
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $pluralModelLabel = 'Pengguna';
    protected static ?string $navigationGroup = 'Manajemen Sistem';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pengguna')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->minLength(3)
                            ->validationMessages([
                                'required' => 'Nama wajib diisi.',
                                'min' => 'Nama minimal 3 karakter.',
                            ]),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'required' => 'Email wajib diisi.',
                                'email' => 'Format email tidak valid.',
                                'unique' => 'Email sudah terdaftar.',
                            ]),

                        Forms\Components\TextInput::make('noHP')
                            ->label('No HP')
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Nomor HP sudah terdaftar.',
                            ]),

                        Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options([
                                'customer' => 'Customer',
                                'admin' => 'Admin',
                            ])
                            ->required()
                            ->default('customer')
                            ->validationMessages([
                                'required' => 'Role wajib diisi.',
                            ]),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->minLength(8)
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->validationMessages([
                                'min' => 'Password minimal 8 karakter.',
                            ])
                            ->helperText('Kosongkan jika tidak ingin mengubah password.'),

                        Forms\Components\Toggle::make('email_verified_at')
                            ->label('Email Terverifikasi')
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, Forms\Set $set, $record) {
                                if ($state && $record) {
                                    $record->email_verified_at = now();
                                    $record->save();
                                } elseif (!$state && $record) {
                                    $record->email_verified_at = null;
                                    $record->save();
                                }
                            })
                            ->default(fn ($record) => $record?->email_verified_at !== null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('noHP')
                    ->label('No HP')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'success',
                        'customer' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Admin',
                        'customer' => 'Customer',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Admin',
                        'customer' => 'Customer',
                    ]),

                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Terverifikasi')
                    ->placeholder('Semua')
                    ->trueLabel('Terverifikasi')
                    ->falseLabel('Belum Terverifikasi'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        // Remove password from data if empty
                        if (empty($data['password'])) {
                            unset($data['password']);
                        }
                        return $data;
                    })
                    ->after(function ($record) {
                        Log::info('User updated via Filament', [
                            'user_id' => auth()->id(),
                            'target_user_id' => $record->id,
                            'correlation_id' => request()->get('correlation_id'),
                        ]);
                    }),

                Tables\Actions\DeleteAction::make()
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->id === auth()->id()) {
                                    throw new \Exception('Anda tidak dapat menghapus akun sendiri.');
                                }
                            }
                        })
                        ->after(function ($records) {
                            Log::info('Users deleted via Filament (bulk)', [
                                'user_id' => auth()->id(),
                                'deleted_count' => $records->count(),
                                'deleted_ids' => $records->pluck('id')->toArray(),
                                'correlation_id' => request()->get('correlation_id'),
                            ]);
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(!auth()->user()?->isAdmin(), function ($query) {
                // Non-admin users can only see themselves
                return $query->where('id', auth()->id());
            });
    }

    public static function canCreate(): bool
    {
        // Tidak ada create karena sudah ada fitur register
        return false;
    }
}

