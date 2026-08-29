<?php

namespace Boy132\Subdomains\Filament\Admin\Resources\CloudflareDomains;

use Boy132\Subdomains\Filament\Admin\Resources\CloudflareDomains\Pages\ManageCloudflareDomains;
use Boy132\Subdomains\Models\CloudflareDomain;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CloudflareDomainResource extends Resource
{
    protected static ?string $model = CloudflareDomain::class;

    protected static ?string $slug = 'domains';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-world-www';

    public static function getNavigationLabel(): string
    {
        return trans_choice('subdomains::strings.domain', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('subdomains::strings.domain', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('subdomains::strings.domain', 2);
    }

    public static function getNavigationGroup(): ?string
    {
        return trans_choice('subdomains::strings.subdomain', 2);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count() ?: null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(trans('subdomains::strings.name')),
                TextColumn::make('prefix')
                    ->label(trans('subdomains::strings.prefix')),
                TextColumn::make('subdomains_count')
                    ->label(trans_choice('subdomains::strings.subdomain', 2))
                    ->counts('subdomains'),
                IconColumn::make('is_synced')
                    ->label(trans('subdomains::strings.is_synced'))
                    ->state(fn (CloudflareDomain $domain) => !is_null($domain->cloudflare_id))
                    ->boolean()
                    ->trueIcon('tabler-refresh')
                    ->falseIcon('tabler-refresh-off')
                    ->tooltip(fn (CloudflareDomain $domain) => $domain->cloudflare_id),
            ])
            ->recordActions([
                Action::make('sync')
                    ->tooltip(trans('subdomains::strings.sync'))
                    ->icon('tabler-refresh')
                    ->visible(fn (CloudflareDomain $domain) => is_null($domain->cloudflare_id))
                    ->action(function (CloudflareDomain $domain) {
                        try {
                            $domain->fetchCloudflareId();

                            Notification::make()
                                ->title(trans('subdomains::strings.notifications.synced'))
                                ->success()
                                ->send();
                        } catch (Exception $exception) {
                            Notification::make()
                                ->title(trans('subdomains::strings.notifications.not_synced'))
                                ->body($exception->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->createAnother(false)
                    ->hidden(fn () => is_null(config('subdomains.token')))
                    ->using(function (array $data) {
                        try {
                            return CloudflareDomain::create($data);
                        } catch (Exception $exception) {
                            Notification::make()
                                ->title(trans('subdomains::strings.notifications.not_synced'))
                                ->body($exception->getMessage())
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->emptyStateIcon('tabler-world-www')
            ->emptyStateDescription('')
            ->emptyStateHeading(trans('subdomains::strings.no_domains'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label(trans('subdomains::strings.name'))
                    ->required()
                    ->unique(),
                TextInput::make('prefix')
                    ->label(trans('subdomains::strings.prefix')),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label(trans('subdomains::strings.name')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCloudflareDomains::route('/'),
        ];
    }
}
