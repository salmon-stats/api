<?php

namespace App\Constants;

class SalmonStatsConst {
    const SALMON_EVENTS = [
        [0, 'no_event', 'water-levels'],
        [1, 'cohock_charge', 'cohock-charge'],
        [2, 'fog', 'fog'],
        [3, 'goldie_seeking', 'goldie-seeking'],
        [4, 'griller', 'griller'],
        [5, 'mothership', 'the-mothership'],
        [6, 'rush', 'rush'],
    ];

    const SALMON_WATER_LEVELS = [
        [1, 'low', 'low'],
        [2, 'normal', 'normal'],
        [3, 'high', 'high'],
    ];

    const SALMON_FAIL_REASONS = [
        [1, 'wipe_out'],
        [2, 'time_limit'],
    ];
}
