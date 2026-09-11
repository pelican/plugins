<?php

namespace Boy132\Subdomains\Filament\Admin\Resources\Servers\RelationManagers;

use App\Models\Server;
use Boy132\Subdomains\Models\CloudflareDomain;
use Boy132\Subdomains\Models\Subdomain;
use Boy132\Subdomains\Rules\NotOnBlacklist;
use Boy132\Subdomains\Services\SubdomainService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * @method Server getOwnerRecord()
 */
class SubdomainRelationManager extends RelationManager
{
    protected static string $relationship = 'subdomains';

    public function table(Table $table): Table
    {
        return $table
            ->heading(fn () => trans_choice('subdomains::strings.subdomain', 2) . ' (' . trans('subdomains::strings.limit') .': ' . ($this->getOwnerRecord()->subdomain_limit ?? 0) . ')')
            ->columns([
                TextColumn::make('label')
                    ->label(trans('subdomains::strings.name'))
                    ->state(fn (Subdomain $subdomain) => $subdomain->getLabel()),
                TextColumn::make('record_type')
                    ->label(trans('subdomains::strings.record_type')),
            ])
            ->recordActions([
                EditAction::make()
                    ->action(function (array $data, Subdomain $subdomain, SubdomainService $service) {
                        try {
                            return $service->handle($data, $subdomain);
                        } catch (Exception $exception) {
                            Notification::make()
                                ->title(trans('subdomains::strings.notifications.not_synced'))
                                ->body($exception->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            throw new Halt();
                        }
                    }),
                DeleteAction::make(),
            ])
            ->headerActions([
                Action::make('change_limit')
                    ->tooltip(trans('subdomains::strings.change_limit'))
                    ->icon('tabler-filter-2-edit')
                    ->schema([
                        TextInput::make('limit')
                            ->label(trans('subdomains::strings.limit'))
                            ->numeric()
                            ->required()
                            ->default($this->getOwnerRecord()->subdomain_limit ?? 0)
                            ->minValue(0),
                    ])
                    ->action(function ($data) {
                        $oldLimit = $this->getOwnerRecord()->subdomain_limit ?? 0;
                        $newLimit = $data['limit'];

                        $this->getOwnerRecord()->update(['subdomain_limit' => $newLimit]);

                        Notification::make()
                            ->title(trans('subdomains::strings.limit_changed'))
                            ->body($oldLimit . ' -> ' . $newLimit)
                            ->success()
                            ->send();
                    }),
                CreateAction::make()
                    ->visible(fn () => count(CloudflareDomain::availableDomains($this->getOwnerRecord())) > 0)
                    ->createAnother(false)
                    ->action(function (array $data, SubdomainService $service) {
                        try {
                            $data['server_id'] = $this->getOwnerRecord()->id;

                            return $service->handle($data);
                        } catch (Exception $exception) {
                            Notification::make()
                                ->title(trans('subdomains::strings.notifications.not_synced'))
                                ->body($exception->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            throw new Halt();
                        }
                    }),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(trans('subdomains::strings.name'))
                    ->required()
                    ->unique()
                    ->alphaDash()
                    ->rule(new NotOnBlacklist())
                    ->columnSpanFull()
                    ->suffix(fn (Get $get) => '.' . CloudflareDomain::find($get('domain_id'))?->nameWithPrefix()),
                Select::make('domain_id')
                    ->label(trans_choice('subdomains::strings.domain', 1))
                    ->disabledOn('edit')
                    ->disabled(fn () => CloudflareDomain::availableDomains($this->getOwnerRecord())->count() <= 1)
                    ->saved()
                    ->required()
                    ->selectablePlaceholder(false)
                    ->relationship('domain', 'name')
                    ->options(CloudflareDomain::availableDomains($this->getOwnerRecord())->mapWithKeys(fn ($domain) => [$domain->id => $domain->nameWithPrefix()]))
                    ->default(CloudflareDomain::availableDomains($this->getOwnerRecord())->first()->id)
                    ->preload()
                    ->searchable()
                    ->afterStateUpdated(fn (Get $get, Set $set) => $set('record_type', CloudflareDomain::find($get('domain_id'))?->availableRecordTypes($this->getOwnerRecord())->first()))
                    ->live(),
                Select::make('record_type')
                    ->label(trans('subdomains::strings.record_type'))
                    ->disabledOn('edit')
                    ->disabled(fn (Get $get) => CloudflareDomain::find($get('domain_id'))?->availableRecordTypes($this->getOwnerRecord())->count() <= 1)
                    ->saved()
                    ->required()
                    ->selectablePlaceholder(false)
                    ->options(fn (Get $get) => CloudflareDomain::find($get('domain_id'))?->availableRecordTypes($this->getOwnerRecord())->pluck('name', 'value'))
                    ->default(fn (Get $get) => CloudflareDomain::find($get('domain_id'))?->availableRecordTypes($this->getOwnerRecord())->first()),
            ]);
    }
}
