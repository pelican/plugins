<?php

namespace Boy132\FluffyTheme;

use App\Contracts\Plugins\HasTheme;
use Filament\Panel;
use Filament\Support\Colors\Color;

class FluffyThemePlugin implements HasTheme
{
    public function getId(): string
    {
        return 'fluffy-theme';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}

    public function getThemeColors(): array
    {
        return [
            'danger' => Color::Rose,
            'gray' => Color::generateV3Palette('#b974c3'), // Fuchsia but desaturated
            'info' => Color::Violet,
            'primary' => Color::Indigo,
            'success' => Color::Teal,
            'warning' => Color::Pink,
        ];
    }

    public function getThemeFont(): ?string
    {
        return 'Finger Paint';
    }

    public function getThemeViteEntry(): string|array|null
    {
        return null;
    }

    public function getThemeRenderHooks(): array
    {
        return [];
    }
}
