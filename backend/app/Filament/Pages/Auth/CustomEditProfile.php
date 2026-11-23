<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class CustomEditProfile extends EditProfile
{
    protected static ?string $title = 'Mi Perfil de Usuario';
    protected static ?string $slug = 'perfil';

    public function getHeading(): string | Htmlable
    {
        return 'Mi Perfil de Usuario';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Administra tus datos personales, medios de contacto y credenciales de acceso a la plataforma.';
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label('Teléfono Móvil / WhatsApp')
            ->tel()
            ->prefixIcon('heroicon-o-phone')
            ->placeholder('Ej: 3103248385')
            ->maxLength(50);
    }

    protected function getRoleFormComponent(): Component
    {
        return TextInput::make('role_display')
            ->label('Rol en Plataforma')
            ->default(fn () => auth()->user()?->role?->label() ?? 'Administradora')
            ->disabled()
            ->dehydrated(false)
            ->prefixIcon('heroicon-o-shield-check');
    }

    protected function getTenantFormComponent(): Component
    {
        return TextInput::make('tenant_display')
            ->label('Salón / Empresa Asignada')
            ->default(fn () => auth()->user()?->tenant?->name ?? 'Nuvex Global SaaS')
            ->disabled()
            ->dehydrated(false)
            ->prefixIcon('heroicon-o-building-storefront');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identidad & Información de Contacto')
                    ->description('Datos personales utilizados para notificaciones y contacto directo.')
                    ->schema([
                        $this->getNameFormComponent()
                            ->label('Nombre Completo')
                            ->prefixIcon('heroicon-o-user'),
                        $this->getEmailFormComponent()
                            ->label('Correo Electrónico')
                            ->prefixIcon('heroicon-o-envelope'),
                        $this->getPhoneFormComponent(),
                        $this->getRoleFormComponent(),
                        $this->getTenantFormComponent()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Seguridad & Clave de Acceso')
                    ->description('Actualiza tu contraseña de ingreso únicamente si deseas modificarla.')
                    ->schema([
                        $this->getPasswordFormComponent()
                            ->label('Nueva Contraseña (Opcional)')
                            ->prefixIcon('heroicon-o-lock-closed'),
                        $this->getPasswordConfirmationFormComponent()
                            ->label('Confirmar Nueva Contraseña')
                            ->prefixIcon('heroicon-o-lock-closed'),
                        $this->getCurrentPasswordFormComponent()
                            ->label('Contraseña Actual')
                            ->prefixIcon('heroicon-o-key')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
