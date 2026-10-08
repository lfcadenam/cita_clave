<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Enums\UserRole;
use App\Filament\SuperAdmin\Resources\SuperAdminUserResource\Pages;
use App\Models\Tenant;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class SuperAdminUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static string | UnitEnum | null $navigationGroup = 'SISTEMA & PLATAFORMA';
    protected static ?string $modelLabel = 'Usuario de Plataforma';
    protected static ?string $pluralModelLabel = 'Usuarios & Administradores';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de Acceso')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(50),

                        Forms\Components\Select::make('role')
                            ->label('Rol en Plataforma')
                            ->options([
                                UserRole::SUPER_ADMIN->value => 'Super Administrador Nuvex',
                                UserRole::ADMIN->value => 'Administradora / Especialista de Salón',
                                UserRole::CLIENT->value => 'Clienta',
                            ])
                            ->required(),

                        Forms\Components\Select::make('tenant_id')
                            ->label('Salón / Empresa Asignada')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Opcional para Super Admin. Obligatorio para Administradora de Salón.'),

                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->placeholder(fn (string $operation): ?string => $operation === 'edit' ? 'Dejar en blanco para mantener la actual' : 'Asigna una contraseña segura')
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Ingresa una nueva contraseña únicamente si deseas modificarla.' : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Usuario Activo')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->colors([
                        'danger' => UserRole::SUPER_ADMIN->value,
                        'success' => UserRole::ADMIN->value,
                        'gray' => UserRole::CLIENT->value,
                    ]),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Salón Asignado')
                    ->placeholder('Nuvex Central (Global)')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Creación')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Filtrar por Rol')
                    ->options([
                        UserRole::SUPER_ADMIN->value => 'Super Admin',
                        UserRole::ADMIN->value => 'Admin de Salón',
                        UserRole::CLIENT->value => 'Clienta',
                    ]),
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Filtrar por Salón')
                    ->relationship('tenant', 'name'),
            ])
            ->actions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data): array {
                        $data['password'] = null;
                        return $data;
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        if (blank($data['password'] ?? null)) {
                            unset($data['password']);
                        } else {
                            $data['password'] = Hash::make($data['password']);
                        }
                        return $data;
                    }),
                DeleteAction::make()
                    ->modalHeading(fn (User $record): string => "Eliminar Usuario: {$record->name}")
                    ->modalDescription('¿Estás seguro de que deseas eliminar este usuario de la plataforma? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar usuario')
                    ->modalCancelActionLabel('Cancelar')
                    ->hidden(fn (User $record): bool => $record->id === auth()->id()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuperAdminUsers::route('/'),
            'create' => Pages\CreateSuperAdminUser::route('/create'),
            'edit' => Pages\EditSuperAdminUser::route('/{record}/edit'),
        ];
    }
}