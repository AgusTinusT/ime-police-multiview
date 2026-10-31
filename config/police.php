<?php

return [
    // Officer dianggap hiatus bila last_duty_at lebih lama dari ini & tidak sedang live.
    'hiatus_days' => (int) env('POLICE_HIATUS_DAYS', 14),
];

