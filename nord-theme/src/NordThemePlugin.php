<?php

namespace Boy132\NordTheme;

use App\Contracts\Plugins\HasTheme;
use Filament\Panel;
use Filament\Support\Colors\Color;

class NordThemePlugin implements HasTheme
{
    public function getId(): string
    {
        return 'nord-theme';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}

    public function getThemeColors(): array
    {
        return [
            'gray' => [
                50 => '#eceff4',
                100 => '#e5e9f0',
                200 => '#d8dee9',
                300 => '#a7b1c5',
                400 => '#8c9ab3',
                500 => '#71829b',
                600 => '#4c566a',
                700 => '#434c5e',
                800 => '#3b4252',
                900 => '#2e3440',
                950 => '#232831',
            ],
            'primary' => [
                50 => '#FAFCFD',
                100 => '#F3F9FA',
                200 => '#E3EFF2',
                300 => '#CFE6EC',
                400 => '#ACD4DD',
                500 => '#88C0D0',
                600 => '#7AADBB',
                700 => '#66909B',
                800 => '#52737D',
                900 => '#445E66',
                950 => '#293A3D',
            ],
            'secondary' => Color::hex('#5e81ac'),
            'info' => Color::hex('#81a1c1'),
            'success' => Color::hex('#a3be8c'),
            'warning' => Color::hex('#ebcb8b'),
            'danger' => Color::hex('#bf616a'),
        ];
    }

    public function getThemeFont(): ?string
    {
        return null;
    }

    public function getThemeViteEntry(): string|array|null
    {
        return 'plugins/nord-theme/resources/css/theme.css';
    }

    public function getThemeRenderHooks(): array
    {
        return [];
    }
}
