<?php

return [
    'no_domains' => 'No Domains',
    'domain' => 'Domain|Domains',

    'no_subdomains' => 'No Subdomains',
    'subdomain' => 'Subdomain|Subdomains',
    'limit' => 'Limit',
    'change_limit' => 'Change limit',
    'limit_changed' => 'Limit changed',
    'limit_reached' => 'Subdomain limit reached',
    'create_subdomain' => 'Create Subdomain',

    'name' => 'Name',
    'prefix' => 'Prefix',
    'record_type' => 'Record type',
    'allowed_record_types' => 'Allowed Record types',
    'allowed_nodes' => 'Allowed Nodes',
    'is_synced' => 'Is Synced?',
    'subdomain_target' => 'Subdomain target',
    'no_subdomain_target' => 'No Subdomain target',

    'sync' => 'Sync',

    'api_token' => 'Cloudflare API Token',
    'api_token_help' => 'The token needs to have read permissions for Zone.Zone and write for Zone.Dns. For better security you can also set the "Zone Resources" to exclude certain domains and add the panel ip to the "Client IP Address Filtering".',

    'subdomain_blacklist' => 'Subdomain Blacklist',
    'subdomain_blacklist_help' => "Users won't be able to create subdomains with these entries. Patterns with '*' are supported, e.g. 'test*' will block all subdomains that start with 'test'.",

    'notifications' => [
        'synced' => 'Domain synced with cloudflare',
        'not_synced' => 'Could not sync domain with cloudflare',
    ],

    'validation' => [
        'on_blacklist' => 'The :attribute is not allowed.',
    ],
];
