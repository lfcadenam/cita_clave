<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\TenantResource\Pages;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';
    protected static string | UnitEnum | null $navigationGroup = 'EMPRESAS & SALONES';
    protected static ?string $modelLabel = 'Salón / Empresa';
    protected static ?string $pluralModelLabel = 'Salones & Empresas SaaS';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('ConfiguracionTenant')
                    ->persistTab()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Identidad & Marca')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Section::make('Información Comercial')
                                    ->description('Datos principales y nombre comercial que identificarán al salón.')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre Oficial / Razón Social')
                                            ->placeholder('Ej: La Belle Nails Bogotá')
                                            ->prefixIcon('heroicon-o-building-office-2')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('short_name')
                                            ->label('Nombre Comercial Corto')
                                            ->placeholder('Ej: Paola Aguilera')
                                            ->prefixIcon('heroicon-o-identification')
                                            ->maxLength(100)
                                            ->helperText('Visible en la cabecera del sitio y en notificaciones breves.'),

                                        Forms\Components\TextInput::make('business_type')
                                            ->label('Tipo de Negocio')
                                            ->default('ESTUDIO DE BELLEZA')
                                            ->placeholder('Ej: ESTUDIO DE BELLEZA, SPA & ESTÉTICA')
                                            ->prefixIcon('heroicon-o-sparkles')
                                            ->maxLength(100)
                                            ->helperText('Aparece como subtítulo debajo del nombre principal.'),
                                    ])->columns(3),

                                Section::make('Presencia Web & Enlaces')
                                    ->description('Configuración de URL pública y dominio personalizado para reservas.')
                                    ->schema([
                                        Forms\Components\TextInput::make('slug')
                                            ->label('Slug / Identificador URL')
                                            ->prefix('salonesgo.com/')
                                            ->prefixIcon('heroicon-o-link')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(100)
                                            ->helperText('Subruta web única para el agendamiento del salón.'),

                                        Forms\Components\TextInput::make('domain')
                                            ->label('Dominio Personalizado (Opcional)')
                                            ->prefix('https://')
                                            ->prefixIcon('heroicon-o-globe-alt')
                                            ->placeholder('citas.labellenailsbog.com')
                                            ->maxLength(255)
                                            ->helperText('Dominio propio del cliente apuntado a la plataforma.'),
                                    ])->columns(2),

                                Section::make('Branding & Estilo Visual')
                                    ->description('Personalización visual del portal de reservas para esta empresa.')
                                    ->schema([
                                        Forms\Components\FileUpload::make('logo_path')
                                            ->label('Logo Oficial del Salón')
                                            ->image()
                                            ->imageEditor()
                                            ->directory('tenants/logos')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->helperText('Formato PNG, SVG o JPG (se recomienda fondo transparente).'),

                                        Forms\Components\ColorPicker::make('primary_color')
                                            ->label('Color Primario de Marca')
                                            ->default('#0d9488')
                                            ->helperText('Color principal para botones, destaques y acentos en la web.'),
                                    ])->columns(2),
                            ]),

                        Tab::make('Ubicación & Contacto')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Section::make('Canales de Atención')
                                    ->description('Números y correo visibles para contacto directo con el cliente.')
                                    ->schema([
                                        Forms\Components\TextInput::make('whatsapp_number')
                                            ->label('WhatsApp Oficial de Atención')
                                            ->tel()
                                            ->prefixIcon('heroicon-o-chat-bubble-oval-left-ellipsis')
                                            ->placeholder('Ej: 3103248385')
                                            ->maxLength(50)
                                            ->helperText('Número para enlace directo de WhatsApp en la cabecera y pie.'),

                                        Forms\Components\TextInput::make('phone')
                                            ->label('Teléfono Fijo / Secundario')
                                            ->tel()
                                            ->prefixIcon('heroicon-o-phone')
                                            ->placeholder('Ej: 601 234 5678')
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('email')
                                            ->label('Correo Electrónico')
                                            ->email()
                                            ->prefixIcon('heroicon-o-envelope')
                                            ->placeholder('contacto@labellenailsbog.com')
                                            ->maxLength(255),
                                    ])->columns(3),

                                Section::make('Sede Física')
                                    ->description('Dirección física donde se prestan los servicios del salón.')
                                    ->schema([
                                        Forms\Components\TextInput::make('city')
                                            ->label('Ciudad')
                                            ->default('Bogotá')
                                            ->prefixIcon('heroicon-o-map')
                                            ->maxLength(100),

                                        Forms\Components\TextInput::make('address')
                                            ->label('Dirección del Estudio')
                                            ->prefixIcon('heroicon-o-map-pin')
                                            ->placeholder('Ej: Cra 15 # 85-30, Local 201')
                                            ->maxLength(255)
                                            ->columnSpan(2),
                                    ])->columns(3),

                                Section::make('Información Pública en Web')
                                    ->description('Información visible para los clientes en el pie de página de reservas.')
                                    ->schema([
                                        Forms\Components\TextInput::make('schedule_summary')
                                            ->label('Resumen de Horarios de Atención')
                                            ->default('Lunes a Sábado 8:00 AM - 6:00 PM | Almuerzo 1:00 PM - 2:00 PM')
                                            ->prefixIcon('heroicon-o-clock')
                                            ->maxLength(255)
                                            ->helperText('Horario visible para los usuarios en la web.'),

                                        Forms\Components\TextInput::make('specialties')
                                            ->label('Especialidades Destacadas')
                                            ->default('Pestañas · Cejas · Faciales · Micropigmentación')
                                            ->prefixIcon('heroicon-o-tag')
                                            ->maxLength(255)
                                            ->helperText('Servicios insignia destacados en el pie de página.'),
                                    ])->columns(2),
                            ]),

                        Tab::make('Cobros & Pagos')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make('Cobros Nequi Directo (Transferencias)')
                                    ->description('Configuración de la cuenta Nequi para recibir abonos directos y validar comprobantes.')
                                    ->schema([
                                        Forms\Components\TextInput::make('nequi_phone')
                                            ->label('Celular Nequi')
                                            ->tel()
                                            ->prefixIcon('heroicon-o-device-phone-mobile')
                                            ->placeholder('Ej: 3103248385')
                                            ->maxLength(50)
                                            ->helperText('Número Nequi al que los clientes deben transferir el anticipo.'),

                                        Forms\Components\TextInput::make('nequi_account_holder')
                                            ->label('Titular de la Cuenta Nequi')
                                            ->prefixIcon('heroicon-o-user')
                                            ->placeholder('Ej: Paola Andrea Aguilera Camacho')
                                            ->maxLength(255)
                                            ->helperText('Nombre completo registrado en la cuenta Nequi receptora.'),

                                        Forms\Components\TextInput::make('nequi_account_type')
                                            ->label('Tipo de Cuenta')
                                            ->default('Personal')
                                            ->prefixIcon('heroicon-o-credit-card')
                                            ->maxLength(50),
                                    ])->columns(3),

                                Section::make('Pasarela Bold Online')
                                    ->description('Credenciales de integración con Bold para procesar pagos con tarjeta y PSE.')
                                    ->schema([
                                        Forms\Components\TextInput::make('bold_api_key')
                                            ->label('Bold API Key')
                                            ->password()
                                            ->revealable()
                                            ->prefixIcon('heroicon-o-key')
                                            ->maxLength(255)
                                            ->helperText('Llave API provista por el portal de comercios Bold.'),

                                        Forms\Components\TextInput::make('bold_secret_key')
                                            ->label('Bold Secret Key')
                                            ->password()
                                            ->revealable()
                                            ->prefixIcon('heroicon-o-lock-closed')
                                            ->maxLength(255)
                                            ->helperText('Llave secreta de autenticación en Bold.'),
                                    ])->columns(2),
                            ]),

                        Tab::make('WhatsApp Cloud API')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Section::make('Proveedor de Notificaciones')
                                    ->description('Canal para el envío automático de confirmaciones y recordatorios.')
                                    ->schema([
                                        Forms\Components\Select::make('whatsapp_driver')
                                            ->label('Proveedor de WhatsApp')
                                            ->options([
                                                'meta' => 'Meta WhatsApp Cloud API (Oficial y Recomendado)',
                                                'evolution' => 'Evolution API (Gateway QR / Baileys)',
                                                'log' => 'Solo Registro en Log (Modo Pruebas / Desarrollo)',
                                            ])
                                            ->placeholder('Usar configuración global (.env)')
                                            ->helperText('Meta WhatsApp Cloud API ofrece la mayor estabilidad y aprobación oficial de plantillas.'),
                                    ]),

                                Section::make('Credenciales Meta Cloud API Oficial')
                                    ->description('Parámetros de conexión con la aplicación en Meta for Developers.')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_phone_number_id')
                                            ->label('Meta Phone Number ID')
                                            ->prefixIcon('heroicon-o-hashtag')
                                            ->placeholder('Ej: 105928374650192')
                                            ->maxLength(100)
                                            ->helperText('Identificador en Meta Dashboard > WhatsApp > Configuración de la API.'),

                                        Forms\Components\TextInput::make('meta_waba_id')
                                            ->label('Meta WhatsApp Business Account ID (WABA ID)')
                                            ->prefixIcon('heroicon-o-identification')
                                            ->placeholder('Ej: 109482736451920')
                                            ->maxLength(100)
                                            ->helperText('Identificador de la cuenta comercial en Meta Business Suite.'),

                                        Forms\Components\TextInput::make('meta_verify_token')
                                            ->label('Webhook Verify Token')
                                            ->prefixIcon('heroicon-o-shield-check')
                                            ->placeholder('nuvex_meta_verify_2026')
                                            ->maxLength(150)
                                            ->helperText('Token secreto para validar el Webhook en Meta (GET /api/v1/webhooks/whatsapp).')
                                            ->columnSpan(2),

                                        Forms\Components\Textarea::make('meta_access_token')
                                            ->label('Meta Permanent Access Token (System User Token)')
                                            ->placeholder('EAAG...')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->helperText('Token permanente generado desde Meta Business Suite (Configuración del negocio > Usuarios del sistema).'),
                                    ])->columns(2),
                            ]),

                        Tab::make('Suscripción SaaS & Límites')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('Control de Membresía Nuvex')
                                    ->description('Gestión de plan comercial, estado de servicio y cuota de citas.')
                                    ->schema([
                                        Forms\Components\Select::make('plan_name')
                                            ->label('Plan de Suscripción')
                                            ->options([
                                                'Starter Salon' => 'Starter Salon (Hasta 100 citas/mes)',
                                                'Pro Salon' => 'Pro Salon (Hasta 500 citas/mes)',
                                                'SaaS Enterprise Nuvex' => 'SaaS Enterprise Nuvex (Ilimitado)',
                                            ])
                                            ->default('Pro Salon')
                                            ->required(),

                                        Forms\Components\Select::make('subscription_status')
                                            ->label('Estado de Suscripción')
                                            ->options([
                                                'active' => 'Activo',
                                                'trial' => 'Prueba Gratuita',
                                                'suspended' => 'Suspendido',
                                                'cancelled' => 'Cancelado',
                                            ])
                                            ->default('active')
                                            ->required(),

                                        Forms\Components\TextInput::make('max_appointments_per_month')
                                            ->label('Límite de Citas Mensuales')
                                            ->numeric()
                                            ->prefixIcon('heroicon-o-calendar')
                                            ->default(500)
                                            ->required()
                                            ->helperText('Tope mensual de reservas activas permitidas para este salón.'),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Salón Activo en Plataforma')
                                            ->helperText('Si se desactiva, se inhabilita el acceso público de reservas.')
                                            ->default(true),
                                    ])->columns(2),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Salón')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('city')
                    ->label('Ciudad')
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono'),

                Tables\Columns\TextColumn::make('plan_name')
                    ->label('Plan SaaS')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('subscription_status')
                    ->label('Suscripción')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'trial',
                        'danger' => ['suspended', 'cancelled'],
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_status')
                    ->label('Estado Suscripción')
                    ->options([
                        'active' => 'Activo',
                        'trial' => 'Prueba',
                        'suspended' => 'Suspendido',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Salones Activos'),
            ])
            ->actions([
                EditAction::make(),
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}