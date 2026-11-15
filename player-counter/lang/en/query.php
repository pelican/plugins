<?php

return [
    'no_queries' => 'No Game Queries',
    'query' => 'Game Query|Game Queries',
    'type' => 'Type',
    'port_offset' => 'Query Port Offset',
    'no_offset' => 'No Offset',
    'port_offset_hint' => 'The offset that will be added to the allocation port. Usually empty/0 or 1.',
    'port_variable' => 'Query Port Variable',
    'no_variable' => 'No Variable',
    'port_variable_hint' => 'The env name of the startup variable that will be used to get the query port, e.g. "QUERY_PORT". When a value is set the query port offset will be ignored! Leave empty to use the allocation port and offset.',
    'eggs' => 'Eggs',
    'no_eggs' => 'No Eggs',
    'hostname' => 'Hostname',
    'players' => 'Players',
    'map' => 'Map',
    'unknown' => 'Unknown',

    'kick' => 'Kick',
    'ban' => 'Ban',

    'whitelisted' => 'Whitelisted',
    'add_to_whitelist' => 'Add to whitelist',
    'remove_from_whitelist' => 'Remove from whitelist',

    'op' => 'OP',
    'add_to_ops' => 'Add to OPs',
    'remove_from_ops' => 'Remove from OPs',

    'use_alias' => 'Use allocation alias?',
    'use_alias_hint' => 'If checked, queries will use the allocation alias instead of the ip',

    'query_rate_limit' => 'Query rate limit',
    'query_rate_limit_hint' => 'How many query requests the client API allows per server. Both query endpoints share this limit. Set to 0 to disable this limit. The panel-wide client API rate limit still applies.',
    'query_rate_limit_suffix' => 'per minute',

    'table' => [
        'no_players' => 'No players found',
        'no_players_description' => 'Either no players are online or query is disabled on this server',
        'server_not_running' => 'Server not running',
    ],

    'notifications' => [
        'settings_saved' => 'Setting saved',

        'player_kicked' => 'Player kicked from server',
        'player_kick_failed' => 'Could not kick player',

        'player_banned' => 'Player banned from server',
        'player_ban_failed' => 'Could not ban player',

        'player_whitelist_add' => 'Player added to whitelist',
        'player_whitelist_remove' => 'Player removed from whitelist',
        'player_whitelist_failed' => 'Could not change whitelist',

        'player_op' => 'Player added to OPs',
        'player_deop' => 'Player removed from OPs',
        'player_op_failed' => 'Could not change OPs',
    ],
];
