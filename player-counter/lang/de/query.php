<?php

return [
    'no_queries' => 'Keine Game Queries',
    'query' => 'Game Query|Game Queries',
    'type' => 'Typ',
    'port_offset' => 'Query Port Offset',
    'no_offset' => 'Kein Offset',
    'port_offset_hint' => 'Der Offset wird zum Allocation Port addiert. Normalerweise leer/0 oder 1.',
    'port_variable' => 'Query Port Variable',
    'no_variable' => 'Keine Variable',
    'port_variable_hint' => 'Der Env Name der Startup Variable, die für den Query Port verwendet werden soll, z.B. "QUERY_PORT". Wenn ein Wert gesetzt wurde wird der Query Port Offset ignoriert! Lasse das Feld leer, um den Allocation Port und Offset zu verwenden.',
    'eggs' => 'Eggs',
    'no_eggs' => 'Keine Eggs',
    'hostname' => 'Hostname',
    'players' => 'Spieler',
    'map' => 'Karte',
    'unknown' => 'Unbekannt',

    'kick' => 'Kicken',
    'ban' => 'Bannen',

    'whitelisted' => 'Auf der Whitelist',
    'add_to_whitelist' => 'Zur Whitelist hinzufügen',
    'remove_from_whitelist' => 'Von der Whitelist entfernen',

    'op' => 'OP',
    'add_to_ops' => 'Zu den OPs hinzufügen',
    'remove_from_ops' => 'Von den OPs entfernen',

    'use_alias' => 'Allocation Alias verwenden?',
    'use_alias_hint' => 'Wenn aktiviert, wird für Queries der Allocation Alias anstelle der IP verwendet',

    'query_rate_limit' => 'Query Rate Limit',
    'query_rate_limit_hint' => 'Wie viele Query Anfragen die Client API pro Server erlaubt. Beide Query Endpunkte teilen sich dieses Limit. Auf 0 setzen, um dieses Limit zu deaktivieren. Das panelweite Rate Limit der Client API gilt weiterhin.',
    'query_rate_limit_suffix' => 'pro Minute',

    'table' => [
        'no_players' => ' Keine Spieler gefunden',
        'no_players_description' => 'Entweder sind keine Spieler online oder die Query ist auf diesem Server deaktiviert',
        'server_not_running' => 'Server ist nicht an',
    ],

    'notifications' => [
        'settings_saved' => 'Einstellungen gespeichert',

        'player_kicked' => 'Spieler vom Server gekickt',
        'player_kick_failed' => 'Konnte Spieler nicht kicken',

        'player_banned' => 'Spieler vom Server gebannt',
        'player_ban_failed' => 'Konnte Spieler nicht bannen',

        'player_whitelist_add' => 'Spieler zur Whitelist hinzugefügt',
        'player_whitelist_remove' => 'Spieler von Whitelist entfernt',
        'player_whitelist_failed' => 'Konnte die Whitelist nicht ändern',

        'player_op' => 'Spieler zu den OPs hinzugefügt',
        'player_deop' => 'Spieler von den OPs entfernt',
        'player_op_failed' => 'Konnte die OPs nicht ändern',
    ],
];
