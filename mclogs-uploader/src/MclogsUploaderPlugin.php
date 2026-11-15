<?php

namespace Boy132\MclogsUploader;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Panel;

class MclogsUploaderPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'mclogs-uploader';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}

    public function getSettingsFormData(): array
    {
        return config('mclogs-uploader');
    }

    public function getSettingsForm(): array
    {
        return [
            Toggle::make('only_minecraft_eggs')
                ->label(trans('mclogs-uploader::upload.only_minecraft_eggs'))
                ->hintIcon('tabler-question-mark')
                ->hintIconTooltip(trans('mclogs-uploader::upload.only_minecraft_eggs_hint'))
                ->inline(false),
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment([
            'MCLOGS_UPLOADER_ONLY_MINECRAFT_EGGS' => $data['only_minecraft_eggs'],
        ]);

        Notification::make()
            ->title(trans('mclogs-uploader::upload.notifications.settings_saved'))
            ->success()
            ->send();
    }
}
