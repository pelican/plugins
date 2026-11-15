<?php

return [
    'use_alias' => (bool) env('PLAYER_COUNTER_USE_ALIAS', false),
    'query_rate_limit' => (int) env('PLAYER_COUNTER_QUERY_RATE_LIMIT', 2),
];
