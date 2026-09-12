<?php

namespace Boy132\GenericOIDCProviders\Filament\Admin\Resources\GenericOIDCProviders;

use App\Enums\TablerIcon;
use Boy132\GenericOIDCProviders\Filament\Admin\Resources\GenericOIDCProviders\Pages\CreateGenericOIDCProvider;
use Boy132\GenericOIDCProviders\Filament\Admin\Resources\GenericOIDCProviders\Pages\EditGenericOIDCProvider;
use Boy132\GenericOIDCProviders\Filament\Admin\Resources\GenericOIDCProviders\Pages\ListGenericOIDCProviders;
use Boy132\GenericOIDCProviders\Models\GenericOIDCProvider;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\StateCasts\BooleanStateCast;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class GenericOIDCProviderResource extends Resource
{
    protected static ?string $model = GenericOIDCProvider::class;

    protected static ?string $slug = 'generic-oidc-providers';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-brand-oauth';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationGroup(): ?string
    {
        return trans('admin/dashboard.advanced');
    }

    public static function getNavigationLabel(): string
    {
        return trans_choice('generic-oidc-providers::strings.generic_oidc_provider', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('generic-oidc-providers::strings.generic_oidc_provider', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('generic-oidc-providers::strings.generic_oidc_provider', 2);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count() ?: null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')
                    ->label('ID')
                    ->required()
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, Set $set) {
                        $state = Str::slug($state, '_');

                        $set('id', $state);
                        $set('display_name', Str::studly($state));
                    })
                    ->disabledOn('edit'),
                TextInput::make('display_name')
                    ->label(trans('generic-oidc-providers::strings.display_name'))
                    ->required(),
                ColorPicker::make('display_color')
                    ->label(trans('generic-oidc-providers::strings.display_color'))
                    ->placeholder('Default color')
                    ->hex(),
                Select::make('display_icon')
                    ->label(trans('generic-oidc-providers::strings.display_icon'))
                    ->live()
                    ->options(TablerIcon::class)
                    ->suffixIcon(fn ($state) => $state)
                    ->searchable(),
                TextInput::make('base_url')
                    ->label(trans('generic-oidc-providers::strings.base_url'))
                    ->required()
                    ->url()
                    ->autocomplete(false)
                    ->columnSpan(fn ($operation) => $operation === 'create' ? 2 : 1),
                TextInput::make('redirect_url')
                    ->label(trans('generic-oidc-providers::strings.redirect_url'))
                    ->saved(false)
                    ->formatStateUsing(fn (Get $get) => url('/auth/oauth/callback/' . $get('id')))
                    ->disabled()
                    ->hiddenOn('create'),
                TextInput::make('client_id')
                    ->label(trans('admin/setting.oauth.client_id'))
                    ->required()
                    ->password()
                    ->revealable()
                    ->autocomplete(false),
                TextInput::make('client_secret')
                    ->label(trans('admin/setting.oauth.client_secret'))
                    ->required()
                    ->password()
                    ->revealable()
                    ->autocomplete(false),
                Group::make()
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('create_missing_users')
                            ->label(trans('admin/setting.oauth.create_missing_users'))
                            ->inline(false)
                            ->onIcon('tabler-check')
                            ->offIcon('tabler-x')
                            ->onColor('success')
                            ->offColor('danger')
                            ->stateCast(new BooleanStateCast(false)),
                        Toggle::make('link_missing_users')
                            ->label(trans('admin/setting.oauth.link_missing_users'))
                            ->inline(false)
                            ->onIcon('tabler-check')
                            ->offIcon('tabler-x')
                            ->onColor('success')
                            ->offColor('danger')
                            ->stateCast(new BooleanStateCast(false)),
                        Toggle::make('verify_jwt')
                            ->label(trans('generic-oidc-providers::strings.verify_jwt'))
                            ->inline(false)
                            ->onIcon('tabler-check')
                            ->offIcon('tabler-x')
                            ->onColor('success')
                            ->offColor('danger')
                            ->stateCast(new BooleanStateCast(false))
                            ->live(),
                        Select::make('use_pkce')
                            ->label(trans('generic-oidc-providers::strings.use_pkce'))
                            ->nullable()
                            ->boolean(
                                trans('admin/server.yes'),
                                trans('admin/server.no'),
                            ),
                    ]),
                Textarea::make('jwt_public_key')
                    ->label(trans('generic-oidc-providers::strings.jwt_public_key'))
                    ->visible(fn (Get $get) => $get('verify_jwt'))
                    ->columnSpanFull()
                    ->rows(3)
                    ->autosize()
                    ->placeholder('-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...
-----END PUBLIC KEY-----'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->badge(),
                TextColumn::make('display_name')
                    ->label(trans('generic-oidc-providers::strings.display_name'))
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->icon(fn (GenericOIDCProvider $provider) => $provider->display_icon ?? 'tabler-brand-oauth')
                    ->color(fn (GenericOIDCProvider $provider) => $provider->display_color ? Color::hex($provider->display_color) : null),
                TextColumn::make('base_url')
                    ->label(trans('generic-oidc-providers::strings.base_url'))
                    ->sortable()
                    ->searchable(),
                IconColumn::make('verify_jwt')
                    ->label(trans('generic-oidc-providers::strings.verify_jwt'))
                    ->boolean(),
                IconColumn::make('create_missing_users')
                    ->label(trans('admin/setting.oauth.create_missing_users'))
                    ->boolean(),
                IconColumn::make('link_missing_users')
                    ->label(trans('admin/setting.oauth.link_missing_users'))
                    ->boolean(),
                IconColumn::make('use_pkce')
                    ->label(trans('generic-oidc-providers::strings.use_pkce'))
                    ->boolean()
                    // null is "blank" and would render nothing, so show it as 'auto'
                    ->default('auto')
                    ->icon(fn ($state) => $state === 'auto' ? 'tabler-wand' : null)
                    ->color(fn ($state) => $state === 'auto' ? 'gray' : null),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make('exclude_bulk_delete'),
                ]),
            ])
            ->emptyStateIcon('tabler-brand-oauth')
            ->emptyStateDescription('')
            ->emptyStateHeading(trans('generic-oidc-providers::strings.no_generic_oidc_providers'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGenericOIDCProviders::route('/'),
            'create' => CreateGenericOIDCProvider::route('/create'),
            'edit' => EditGenericOIDCProvider::route('/{record}/edit'),
        ];
    }
}
