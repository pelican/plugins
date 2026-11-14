<?php

namespace Boy132\NeobrutalismTheme;

use App\Contracts\Plugins\HasTheme;
use Filament\Panel;

class NeobrutalismThemePlugin implements HasTheme
{
    public function getId(): string
    {
        return 'neobrutalism-theme';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}

    public function getThemeColors(): array
    {
        return [];
    }

    public function getThemeFont(): ?string
    {
        return null;
    }

    public function getThemeViteEntry(): string|array|null
    {
        return 'plugins/neobrutalism-theme/resources/css/theme.css';
    }

    public function getThemeRenderHooks(): array
    {
        return [];
    }
}
