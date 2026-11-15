<?php

return [
    'use_alias' => (bool) env('PLAYER_COUNTER_USE_ALIAS', false),
    'query_api_rate_limit' => (int) env('PLAYER_COUNTER_QUERY_API_RATE_LIMIT', 2),
];
