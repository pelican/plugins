<?php

namespace Boy132\GenericOIDCProviders\Extensions\OAuth\Providers;

use Override;
use SocialiteProviders\OIDC\Provider;

final class GenericOIDCProvider extends Provider
{
    /**
     * @return string[]
     */
    public static function additionalConfigKeys(): array
    {
        return array_merge(parent::additionalConfigKeys(), ['use_pkce']);
    }

    #[Override]
    protected function usesPKCE(): bool
    {
        $configured = $this->config['use_pkce'] ?? null;

        if (!is_null($configured)) {
            return $configured;
        }

        $openid_config = $this->getOpenIdConfig();
        if (isset($openid_config['code_challenge_methods_supported']) && in_array('S256', $openid_config['code_challenge_methods_supported'])) {
            return true;
        }

        return parent::usesPKCE();
    }
}
