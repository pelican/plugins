<?php

namespace Boy132\TawktoWidget;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Panel;

class TawktoWidgetPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'tawkto-widget';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}

    public function getSettingsFormData(): array
    {
        return config('tawkto-widget');
    }

    public function getSettingsForm(): array
    {
        return [
            TextInput::make('provider_id')
                ->label('Provider ID')
                ->required(),
            TextInput::make('widget_id')
                ->label('Widget ID')
                ->required(),
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment([
            'TAWKTO_PROVIDER_ID' => $data['provider_id'],
            'TAWKTO_WIDGET_ID' => $data['widget_id'],
        ]);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
