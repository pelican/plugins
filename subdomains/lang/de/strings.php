<?php

return [
    'no_domains' => 'Keine Domains',
    'domain' => 'Domain|Domains',

    'no_subdomains' => 'Keine Subdomains',
    'subdomain' => 'Subdomain|Subdomains',
    'limit' => 'Limit',
    'change_limit' => 'Limit anpassen',
    'limit_changed' => 'Limit angepasst',
    'limit_reached' => 'Subdomain Limit erreicht',
    'create_subdomain' => 'Subdomain erstellen',

    'name' => 'Name',
    'prefix' => 'Präfix',
    'record_type' => 'Record Typ',
    'allowed_record_types' => 'Zulässige Recordtypen',
    'allowed_nodes' => 'Zulässige Nodes',
    'is_synced' => 'Ist synchronisiert?',
    'subdomain_target' => 'Subdomain Ziel',
    'no_subdomain_target' => 'Kein Subdomain Ziel',

    'sync' => 'Synchronisieren',

    'api_token' => 'Cloudflare API Token',
    'api_token_help' => 'Der Token benötigt Leseberechtigung für Zone.Zone und Schreibberechtigung für Zone.Dns. Für eine verbesserte Sicherheit können mit "Zone Resources" bestimmte Domains ausgeschlossen werden und die Panel-IP zum "Client IP Adress Filtering" hinzugefügt werden.',

    'subdomain_blacklist' => 'Subdomain Blacklist',
    'subdomain_blacklist_help' => "Mit diesen Einträgen können Benutzer keine Subdomains erstellen. Es werden Pattern mit '*' unterstützt, z.B. 'test*' blockiert alle Subdomains, die mit 'test' beginnen.",

    'notifications' => [
        'synced' => 'Domain wurde erfolgreich mit Cloudflare synchronisiert',
        'not_synced' => 'Domain konnte nicht mit Cloudflare synchronisiert werden',
    ],

    'validation' => [
        'on_blacklist' => ':attribute ist nicht erlaubt.',
    ],
];
