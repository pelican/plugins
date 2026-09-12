<?php

namespace Boy132\GenericOIDCProviders\Extensions\OAuth\Schemas;

use App\Extensions\OAuth\Schemas\OAuthSchema;
use App\Models\User;
use Boy132\GenericOIDCProviders\Extensions\OAuth\Providers\GenericOIDCProvider as Provider;
use Boy132\GenericOIDCProviders\Filament\Admin\Resources\GenericOIDCProviders\Pages\EditGenericOIDCProvider;
use Boy132\GenericOIDCProviders\Models\GenericOIDCProvider;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as OAuthUser;

final class GenericOIDCProviderSchema extends OAuthSchema
{
    public function __construct(private readonly GenericOIDCProvider $model) {}

    public function getId(): string
    {
        return $this->model->id;
    }

    public function getSocialiteProvider(): string
    {
        return Provider::class;
    }

    public function getServiceConfig(): array
    {
        return [
            'client_id' => $this->model->client_id,
            'client_secret' => $this->model->client_secret,
            'base_url' => $this->model->base_url,
            'verify_jwt' => $this->model->verify_jwt,
            'jwt_public_key' => $this->model->jwt_public_key,
            'use_pkce' => $this->model->use_pkce,
        ];
    }

    public function getSetupSteps(): array
    {
        return [
            Step::make('Generic OIDC Provider')
                ->schema([
                    TextEntry::make('info')
                        ->hiddenLabel()
                        ->state('This a generic OIDC provider and doesn\'t require any setup!'),
                ]),
        ];
    }

    public function getSettingsForm(): array
    {
        return [
            TextEntry::make('info')
                ->label('Generic OIDC Provider')
                ->state('Click here to configure this generic OIDC provider.')
                ->url(EditGenericOIDCProvider::getUrl(['record' => $this->model], panel: 'admin'))
                ->columnSpanFull(),
        ];
    }

    public function getName(): string
    {
        return $this->model->display_name;
    }

    public function getIcon(): ?string
    {
        return $this->model->display_icon;
    }

    public function getHexColor(): ?string
    {
        return $this->model->display_color;
    }

    public function isEnabled(): bool
    {
        $id = Str::upper($this->getId());

        return (bool) env("OAUTH_{$id}_ENABLED", true);
    }

    public function shouldCreateMissingUser(OAuthUser $user): bool
    {
        return $this->model->create_missing_users;
    }

    public function shouldLinkMissingUser(User $user, OAuthUser $oauthUser): bool
    {
        return $this->model->link_missing_users;
    }
}
