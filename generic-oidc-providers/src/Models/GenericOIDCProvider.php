<?php

namespace Boy132\GenericOIDCProviders\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property bool $create_missing_users
 * @property bool $link_missing_users
 * @property string $display_name
 * @property ?string $display_icon
 * @property ?string $display_color
 * @property string $base_url
 * @property string $client_id
 * @property string $client_secret
 * @property bool $verify_jwt
 * @property ?string $jwt_public_key
 * @property ?bool $use_pkce
 */
class GenericOIDCProvider extends Model
{
    public $incrementing = false;

    protected $table = 'generic_oidc_providers';

    protected $fillable = [
        'id',
        'create_missing_users',
        'link_missing_users',
        'display_name',
        'display_icon',
        'display_color',
        'base_url',
        'client_id',
        'client_secret',
        'verify_jwt',
        'jwt_public_key',
        'use_pkce',
    ];

    protected function casts(): array
    {
        return [
            'create_missing_users' => 'bool',
            'link_missing_users' => 'bool',
            'client_id' => 'encrypted',
            'client_secret' => 'encrypted',
            'verify_jwt' => 'bool',
            'use_pkce' => 'bool',
        ];
    }
}
