<?php

namespace Boy132\PlayerCounter;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Panel;

class PlayerCounterPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'player-counter';
    }

    public function register(Panel $panel): void
    {
        $id = str($panel->getId())->title();

        $panel->discoverResources(plugin_path($this->getId(), "src/Filament/$id/Resources"), "Boy132\\PlayerCounter\\Filament\\$id\\Resources");
        $panel->discoverPages(plugin_path($this->getId(), "src/Filament/$id/Pages"), "Boy132\\PlayerCounter\\Filament\\$id\\Pages");
        $panel->discoverWidgets(plugin_path($this->getId(), "src/Filament/$id/Widgets"), "Boy132\\PlayerCounter\\Filament\\$id\\Widgets");
    }

    public function boot(Panel $panel): void {}

    public function getSettingsFormData(): array
    {
        return config('player-counter');
    }

    public function getSettingsForm(): array
    {
        return [
            Toggle::make('use_alias')
                ->label(trans('player-counter::query.use_alias'))
                ->hintIcon('tabler-question-mark')
                ->hintIconTooltip(trans('player-counter::query.use_alias_hint'))
                ->inline(false),
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment([
            'PLAYER_COUNTER_USE_ALIAS' => $data['use_alias'],
        ]);

        Notification::make()
            ->title(trans('player-counter::query.notifications.settings_saved'))
            ->success()
            ->send();
    }
}
