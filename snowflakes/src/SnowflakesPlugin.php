<?php

namespace Boy132\Snowflakes;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Slider;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Group;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SnowflakesPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'snowflakes';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}

    public function getSettingsFormData(): array
    {
        return config('snowflakes');
    }

    public function getSettingsForm(): array
    {
        $schema = [
            Slider::make('size')
                ->label(trans('snowflakes::strings.size'))
                ->range(minValue: 0.5, maxValue: 4)
                ->decimalPlaces(1)
                ->step(0.1)
                ->tooltips()
                ->hintIcon('tabler-question-mark')
                ->hintIconTooltip(trans('snowflakes::strings.size_help')),
            Slider::make('speed')
                ->label(trans('snowflakes::strings.speed'))
                ->range(minValue: 0.5, maxValue: 3)
                ->decimalPlaces(1)
                ->step(0.1)
                ->tooltips()
                ->hintIcon('tabler-question-mark')
                ->hintIconTooltip(trans('snowflakes::strings.speed_help')),
            Slider::make('opacity')
                ->label(trans('snowflakes::strings.opacity'))
                ->range(minValue: 0.1, maxValue: 1)
                ->decimalPlaces(1)
                ->step(0.1)
                ->tooltips()
                ->hintIcon('tabler-question-mark')
                ->hintIconTooltip(trans('snowflakes::strings.opacity_help')),
            Slider::make('density')
                ->label(trans('snowflakes::strings.density'))
                ->range(minValue: 0.5, maxValue: 10)
                ->decimalPlaces(1)
                ->step(0.1)
                ->tooltips()
                ->hintIcon('tabler-question-mark')
                ->hintIconTooltip(trans('snowflakes::strings.density_help')),
            Slider::make('quality')
                ->label(trans('snowflakes::strings.quality'))
                ->range(minValue: 0.1, maxValue: 1)
                ->decimalPlaces(1)
                ->step(0.1)
                ->tooltips()
                ->hintIcon('tabler-question-mark')
                ->hintIconTooltip(trans('snowflakes::strings.quality_help')),
        ];

        return [
            Group::make()
                ->schema($schema)
                ->columns(),
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment(Arr::mapWithKeys($data, fn ($value, $key) => [Str::upper("SNOWFLAKES_$key") => $value]));

        Notification::make()
            ->title(trans('admin/setting.save_success'))
            ->success()
            ->send();
    }
}
