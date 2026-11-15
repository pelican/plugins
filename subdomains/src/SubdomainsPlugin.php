<?php

namespace Boy132\Subdomains;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Panel;

class SubdomainsPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'subdomains';
    }

    public function register(Panel $panel): void
    {
        $id = str($panel->getId())->title();

        $panel->discoverResources(plugin_path($this->getId(), "src/Filament/$id/Resources"), "Boy132\\Subdomains\\Filament\\$id\\Resources");
        $panel->discoverPages(plugin_path($this->getId(), "src/Filament/$id/Pages"), "Boy132\\Subdomains\\Filament\\$id\\Pages");
    }

    public function boot(Panel $panel): void {}

    public function getSettingsFormData(): array
    {
        $data = config('subdomains');

        $data['blacklist'] = array_filter(explode(',', $data['blacklist']));

        return $data;
    }

    public function getSettingsForm(): array
    {
        return [
            TextInput::make('token')
                ->label(trans('subdomains::strings.api_token'))
                ->required()
                ->hintIcon('tabler-question-mark')
                ->hintIconTooltip(trans('subdomains::strings.api_token_help')),
            TagsInput::make('blacklist')
                ->label(trans('subdomains::strings.subdomain_blacklist'))
                ->hintIcon('tabler-question-mark')
                ->hintIconTooltip(trans('subdomains::strings.subdomain_blacklist_help')),
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment([
            'SUBDOMAINS_CLOUDFLARE_TOKEN' => $data['token'],
            'SUBDOMAINS_BLACKLIST' => implode(',', $data['blacklist']),
        ]);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
