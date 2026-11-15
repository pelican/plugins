<?php

namespace Boy132\PterodactylTheme;

use App\Contracts\Plugins\HasTheme;
use Filament\Panel;
use Filament\Support\Colors\Color;

class PterodactylThemePlugin implements HasTheme
{
    public function getId(): string
    {
        return 'pterodactyl-theme';
    }

    public const PTERO_GRAY = [
        50 => 'oklch(0.975 0.0046 258.32)',
        100 => 'oklch(0.9286 0.00618 254.9897)',
        200 => 'oklch(0.8575 0.013 247.98)',
        300 => 'oklch(0.718 0.0216 249.92)',
        400 => 'oklch(0.6173 0.0232 249.98)',
        500 => 'oklch(0.5297 0.0264 250.09)',
        600 => 'oklch(0.4779 0.0267 246.6)',
        700 => 'oklch(0.413 0.0288 246.77)',
        800 => 'oklch(0.3656 0.027449 246.8348)',
        900 => 'oklch(0.2753 0.0228 248.67)',
        950 => 'oklch(0.2484 0.0128 248.51)',
    ];

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}

    public function getThemeColors(): array
    {
        return [
            'gray' => self::PTERO_GRAY,
            'primary' => Color::Blue,
        ];
    }

    public function getThemeFont(): ?string
    {
        return 'IBM Plex Sans';
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
