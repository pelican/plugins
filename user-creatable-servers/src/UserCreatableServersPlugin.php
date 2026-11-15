<?php

namespace Boy132\UserCreatableServers;

use App\Contracts\Plugins\HasPluginSettings;
use App\Enums\PluginStatus;
use App\Models\Egg;
use App\Models\Plugin as PluginModel;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Section;

class UserCreatableServersPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'user-creatable-servers';
    }

    public function register(Panel $panel): void
    {
        $id = str($panel->getId())->title();

        $panel->discoverPages(plugin_path($this->getId(), "src/Filament/$id/Pages"), "Boy132\\UserCreatableServers\\Filament\\$id\\Pages");
        $panel->discoverResources(plugin_path($this->getId(), "src/Filament/$id/Resources"), "Boy132\\UserCreatableServers\\Filament\\$id\\Resources");
        $panel->discoverWidgets(plugin_path($this->getId(), "src/Filament/$id/Widgets"), "Boy132\\UserCreatableServers\\Filament\\$id\\Widgets");
    }

    public function boot(Panel $panel): void {}

    public function getSettingsFormData(): array
    {
        $data = config('user-creatable-servers');

        $data['deployment_tags'] = array_filter(explode(',', $data['deployment_tags']));
        $data['deployment_ports'] = array_filter(explode(',', $data['deployment_ports']));
        $data['allowed_eggs'] = array_filter(explode(',', $data['allowed_eggs']));

        return $data;
    }

    public function getSettingsForm(): array
    {
        return [
            Section::make('Limits')
                ->columns(fn () => $this->hasSubdomainsPlugin() ? 4 : 3)
                ->schema([
                    TextInput::make('database_limit')
                        ->label('Default database limit')
                        ->required()
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('allocation_limit')
                        ->label('Default allocation limit')
                        ->required()
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('backup_limit')
                        ->label('Default backup limit')
                        ->required()
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('subdomain_limit')
                        ->label('Default subdomain limit')
                        ->visible(fn () => $this->hasSubdomainsPlugin())
                        ->required()
                        ->numeric()
                        ->minValue(0),
                ]),
            Section::make('User Settings')
                ->columns()
                ->schema([
                    Toggle::make('can_users_update_servers')
                        ->label('Can users update servers?')
                        ->hintIcon('tabler-question-mark')
                        ->hintIconTooltip('If checked users can update the resource limits of their servers after creation.')
                        ->inline(false),
                    Toggle::make('can_users_delete_servers')
                        ->label('Can users delete servers?')
                        ->hintIcon('tabler-question-mark')
                        ->hintIconTooltip('If checked users can delete their own servers.')
                        ->inline(false),
                ]),
            Section::make('Deployment Settings')
                ->columns()
                ->schema([
                    TagsInput::make('deployment_tags')
                        ->label('Node tags')
                        ->hintIcon('tabler-question-mark')
                        ->hintIconTooltip('Only nodes with these tags will be used for deployment. Leave empty to allow all nodes.'),
                    TagsInput::make('deployment_ports')
                        ->label('Ports')
                        ->placeholder('New port or port range')
                        ->hintIcon('tabler-question-mark')
                        ->hintIconTooltip('These ports will be used for deployment. You can enter individual ports or port ranges. (e.g. 8000-8100) Leave empty to create servers with any allocations.'),
                    Select::make('allowed_eggs')
                        ->label('Allowed eggs')
                        ->options(fn () => Egg::all()->mapWithKeys(fn (Egg $egg) => [$egg->id => $egg->name]))
                        ->searchable()
                        ->preload()
                        ->multiple()
                        ->columnSpanFull()
                        ->placeholder('All eggs'),
                ]),
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment([
            'UCS_DEFAULT_DATABASE_LIMIT' => $data['database_limit'],
            'UCS_DEFAULT_ALLOCATION_LIMIT' => $data['allocation_limit'],
            'UCS_DEFAULT_BACKUP_LIMIT' => $data['backup_limit'],
            'UCS_DEFAULT_SUBDOMAIN_LIMIT' => $data['subdomain_limit'],
            'UCS_CAN_USERS_UPDATE_SERVERS' => $data['can_users_update_servers'] ? 'true' : 'false',
            'UCS_CAN_USERS_DELETE_SERVERS' => $data['can_users_delete_servers'] ? 'true' : 'false',
            'UCS_DEPLOYMENT_TAGS' => implode(',', $data['deployment_tags']),
            'UCS_DEPLOYMENT_PORTS' => implode(',', $data['deployment_ports']),
            'UCS_ALLOWED_EGGS' => implode(',', $data['allowed_eggs']),
        ]);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }

    public function hasSubdomainsPlugin(): bool
    {
        /** @var ?PluginModel $subdomains */
        $subdomains = PluginModel::find('subdomains');

        return $subdomains && $subdomains->status === PluginStatus::Enabled;
    }
}
